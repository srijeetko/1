<?php
include 'includes/header.php';
include 'includes/db_connection.php';

// Get search query
$search_query = $_GET['q'] ?? $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'relevance';
$page = max(1, $_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Initialize results
$products = [];
$total_results = 0;
$search_suggestions = [];

if (!empty($search_query)) {
    // Fuzzy search implementation
    $search_terms = explode(' ', trim($search_query));
    $search_conditions = [];
    $params = [];
    
    // Build fuzzy search conditions
    foreach ($search_terms as $term) {
        if (strlen($term) >= 2) {
            $fuzzy_term = '%' . $term . '%';
            $search_conditions[] = "(
                p.name LIKE ? OR
                COALESCE(p.short_description, '') LIKE ? OR
                COALESCE(p.long_description, '') LIKE ? OR
                COALESCE(p.key_benefits, '') LIKE ? OR
                COALESCE(p.ingredients, '') LIKE ? OR
                sc.name LIKE ? OR
                SOUNDEX(p.name) = SOUNDEX(?) OR
                SOUNDEX(sc.name) = SOUNDEX(?)
            )";
            // Add parameters for each condition
            for ($i = 0; $i < 8; $i++) {
                $params[] = ($i < 6) ? $fuzzy_term : $term;
            }
        }
    }
    
    $search_condition = !empty($search_conditions) ? '(' . implode(' OR ', $search_conditions) . ')' : '1=0';
    
    // Add category filter if specified
    $where_clause = "WHERE p.is_active = 1 AND ($search_condition)";
    if (!empty($category_filter)) {
        $where_clause .= " AND p.category_id = ?";
        $params[] = $category_filter;
    }
    
    // Determine sort order
    $order_clause = match($sort_by) {
        'price_low' => 'ORDER BY min_price ASC',
        'price_high' => 'ORDER BY min_price DESC',
        'name' => 'ORDER BY p.name ASC',
        'newest' => 'ORDER BY p.created_at DESC',
        default => 'ORDER BY relevance_score DESC, p.name ASC'
    };
    
    // Count total results
    $count_query = "
        SELECT COUNT(DISTINCT p.product_id) as total
        FROM products p
        LEFT JOIN sub_category sc ON p.category_id = sc.category_id
        $where_clause
    ";
    
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_results = $count_stmt->fetchColumn();
    
    // Get products with relevance scoring
    $products_query = "
        SELECT p.*, 
               sc.name as category_name,
               COALESCE(
                   (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
               ) AS image_url,
               MIN(pv.price_modifier) as min_price,
               MAX(pv.price_modifier) as max_price,
               (
                   CASE WHEN p.name LIKE ? THEN 100 ELSE 0 END +
                   CASE WHEN p.name LIKE ? THEN 50 ELSE 0 END +
                   CASE WHEN COALESCE(p.short_description, '') LIKE ? THEN 30 ELSE 0 END +
                   CASE WHEN COALESCE(p.long_description, '') LIKE ? THEN 20 ELSE 0 END +
                   CASE WHEN sc.name LIKE ? THEN 40 ELSE 0 END +
                   CASE WHEN SOUNDEX(p.name) = SOUNDEX(?) THEN 25 ELSE 0 END
               ) as relevance_score
        FROM products p
        LEFT JOIN sub_category sc ON p.category_id = sc.category_id
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id
        $where_clause
        GROUP BY p.product_id
        HAVING relevance_score > 0
        $order_clause
        LIMIT $limit OFFSET $offset
    ";
    
    // Add relevance scoring parameters
    $relevance_params = [
        $search_query . '%',  // Exact start match
        '%' . $search_query . '%',  // Contains match
        '%' . $search_query . '%',  // Short description
        '%' . $search_query . '%',  // Long description
        '%' . $search_query . '%',  // Category
        $search_query  // SOUNDEX match
    ];
    
    $final_params = array_merge($relevance_params, $params);
    
    $products_stmt = $pdo->prepare($products_query);
    $products_stmt->execute($final_params);
    $products = $products_stmt->fetchAll();
    
    // Generate search suggestions if no results found
    if (empty($products) && !empty($search_query)) {
        $suggestion_query = "
            SELECT DISTINCT p.name
            FROM products p
            WHERE p.is_active = 1 AND SOUNDEX(p.name) = SOUNDEX(?)
            LIMIT 5
        ";
        
        $suggestion_stmt = $pdo->prepare($suggestion_query);
        $suggestion_stmt->execute([$search_query]);
        $search_suggestions = $suggestion_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Get categories for filter
$categories_query = "SELECT * FROM sub_category ORDER BY name ASC";
$categories = $pdo->query($categories_query)->fetchAll();

// Calculate pagination
$total_pages = ceil($total_results / $limit);
?>

<style>
.search-results-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.search-header {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.search-title {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
}

.search-info {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

.search-filters {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.filter-select, .sort-select {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    min-width: 150px;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.product-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    background: #f8f9fa;
}

.product-info {
    padding: 1.5rem;
}

.product-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.product-category {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.product-description {
    color: #777;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #000;
    margin-bottom: 1rem;
}

.view-product-btn {
    display: inline-block;
    background: #000;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    text-align: center;
    width: 100%;
}

.view-product-btn:hover {
    background: #333;
    transform: translateY(-2px);
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.no-results-icon {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-results-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.no-results-text {
    color: #666;
    margin-bottom: 2rem;
}

.search-suggestions {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1.5rem;
}

.suggestions-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.suggestion-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.suggestion-link {
    background: white;
    color: #333;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9rem;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.suggestion-link:hover {
    background: #000;
    color: white;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 3rem;
}

.pagination a, .pagination span {
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #000;
    color: white;
}

.pagination .current {
    background: #000;
    color: white;
}

.highlight {
    background: yellow;
    font-weight: bold;
}

@media (max-width: 768px) {
    .search-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select, .sort-select {
        min-width: auto;
        width: 100%;
    }
    
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
}
</style>

<div class="search-results-container">
    <div class="search-header">
        <h1 class="search-title">
            <?php if (!empty($search_query)): ?>
                Search Results for "<?php echo htmlspecialchars($search_query); ?>"
            <?php else: ?>
                All Products
            <?php endif; ?>
        </h1>

        <div class="search-info">
            <?php if (!empty($search_query)): ?>
                Found <?php echo $total_results; ?> product<?php echo $total_results !== 1 ? 's' : ''; ?>
            <?php else: ?>
                Browse all our products
            <?php endif; ?>
        </div>

        <form method="GET" class="search-filters">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">

            <div class="filter-group">
                <label for="category">Category</label>
                <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_id']); ?>"
                                <?php echo $category_filter === $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="sort">Sort By</label>
                <select name="sort" id="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="relevance" <?php echo $sort_by === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                    <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                </select>
            </div>
        </form>
    </div>

    <?php if (!empty($products)): ?>
        <div class="results-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                    <?php else: ?>
                        <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                            No Image Available
                        </div>
                    <?php endif; ?>

                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>

                        <?php if (!empty($product['category_name'])): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($product['short_description'])): ?>
                            <div class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></div>
                        <?php endif; ?>

                        <div class="product-price">
                            <?php if ($product['min_price'] && $product['max_price']): ?>
                                <?php if ($product['min_price'] == $product['max_price']): ?>
                                    ₹<?php echo number_format($product['min_price'], 2); ?>
                                <?php else: ?>
                                    ₹<?php echo number_format($product['min_price'], 2); ?> - ₹<?php echo number_format($product['max_price'], 2); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                ₹<?php echo number_format($product['price'], 2); ?>
                            <?php endif; ?>
                        </div>

                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="view-product-btn">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo $page - 1; ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo $page + 1; ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results">
            <div class="no-results-icon">🔍</div>
            <h2 class="no-results-title">
                <?php if (!empty($search_query)): ?>
                    No products found for "<?php echo htmlspecialchars($search_query); ?>"
                <?php else: ?>
                    No products available
                <?php endif; ?>
            </h2>
            <p class="no-results-text">
                <?php if (!empty($search_query)): ?>
                    Try adjusting your search terms or browse our categories below.
                <?php else: ?>
                    Please check back later for new products.
                <?php endif; ?>
            </p>

            <?php if (!empty($search_suggestions)): ?>
                <div class="search-suggestions">
                    <div class="suggestions-title">Did you mean:</div>
                    <div class="suggestion-links">
                        <?php foreach ($search_suggestions as $suggestion): ?>
                            <a href="?q=<?php echo urlencode($suggestion); ?>" class="suggestion-link">
                                <?php echo htmlspecialchars($suggestion); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-top: 2rem;">
                <a href="products.php" class="view-product-btn" style="display: inline-block; width: auto;">
                    Browse All Products
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Highlight search terms in results
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?php echo addslashes($search_query); ?>';
    if (searchTerm) {
        highlightSearchTerms(searchTerm);
    }
});

function highlightSearchTerms(term) {
    if (!term) return;

    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        const nameElement = card.querySelector('.product-name');
        const descElement = card.querySelector('.product-description');
        const categoryElement = card.querySelector('.product-category');

        if (nameElement) highlightText(nameElement, term);
        if (descElement) highlightText(descElement, term);
        if (categoryElement) highlightText(categoryElement, term);
    });
}

function highlightText(element, term) {
    const text = element.textContent;
    const regex = new RegExp(`(${term})`, 'gi');
    const highlightedText = text.replace(regex, '<span class="highlight">$1</span>');
    element.innerHTML = highlightedText;
}
</script>

<?php include 'includes/footer.php'; ?>
