<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Delete product if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['delete_product'];
    try {
        $pdo->beginTransaction();
        
        // Delete product images
        $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
        $stmt->execute([$product_id]);
        
        // Delete product variants
        $stmt = $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?');
        $stmt->execute([$product_id]);
        
        // Delete product
        $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
        $stmt->execute([$product_id]);
        
        $pdo->commit();
        $_SESSION['success_message'] = 'Product deleted successfully';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error deleting product';
    }
    header('Location: products.php');
    exit();
}

// Toggle best seller status if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_best_seller'])) {
    $product_id = $_POST['toggle_best_seller'];
    $current_status = $_POST['current_status'] ?? 0;

    try {
        if ($current_status) {
            // Remove from best sellers
            $stmt = $pdo->prepare('DELETE FROM best_sellers WHERE product_id = ?');
            $stmt->execute([$product_id]);
            $status_text = 'removed from best sellers';
        } else {
            // Add to best sellers
            $stmt = $pdo->prepare('INSERT INTO best_sellers (product_id, sales_count) VALUES (?, 0) ON DUPLICATE KEY UPDATE sales_count = sales_count');
            $stmt->execute([$product_id]);
            $status_text = 'marked as best seller';
        }

        $_SESSION['success_message'] = "Product {$status_text} successfully";
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating best seller status: ' . $e->getMessage();
    }
    header('Location: products.php');
    exit();
}

// Toggle featured status if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_featured'])) {
    $product_id = $_POST['toggle_featured'];
    $current_status = $_POST['current_featured_status'] ?? 0;

    try {
        $new_status = $current_status ? 0 : 1;
        $stmt = $pdo->prepare('UPDATE products SET is_featured = ? WHERE product_id = ?');
        $stmt->execute([$new_status, $product_id]);

        $status_text = $new_status ? 'marked as featured' : 'removed from featured';
        $_SESSION['success_message'] = "Product {$status_text} successfully";
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating featured status: ' . $e->getMessage();
    }
    header('Location: products.php');
    exit();
}

// Get all categories for filter
$categories = $pdo->query('SELECT * FROM sub_category ORDER BY name')->fetchAll();

// Build query based on filters
$where = [];
$params = [];

if (!empty($_GET['category'])) {
    $where[] = 'p.category_id = ?';
    $params[] = $_GET['category'];
}

// Enhanced fuzzy search functionality - search only in product fields, not categories
if (!empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $search_terms = explode(' ', $search_query);
    $search_conditions = [];

    // Build fuzzy search conditions for each term
    foreach ($search_terms as $term) {
        if (strlen($term) >= 2) {
            $fuzzy_term = '%' . $term . '%';
            $exact_term = $term . '%';

            $search_conditions[] = "(
                p.name LIKE ? OR
                p.name LIKE ? OR
                COALESCE(p.short_description, '') LIKE ? OR
                COALESCE(p.long_description, '') LIKE ? OR
                COALESCE(p.key_benefits, '') LIKE ? OR
                COALESCE(p.how_to_use, '') LIKE ? OR
                COALESCE(p.ingredients, '') LIKE ? OR
                SOUNDEX(p.name) = SOUNDEX(?) OR
                p.product_id IN (
                    SELECT DISTINCT pv.product_id
                    FROM product_variants pv
                    WHERE pv.size LIKE ? OR COALESCE(pv.color, '') LIKE ?
                )
            )";

            // Add parameters for each condition
            $params[] = $exact_term;      // Exact start match (highest priority)
            $params[] = $fuzzy_term;      // Contains match
            $params[] = $fuzzy_term;      // Short description
            $params[] = $fuzzy_term;      // Long description
            $params[] = $fuzzy_term;      // Key benefits
            $params[] = $fuzzy_term;      // How to use
            $params[] = $fuzzy_term;      // Ingredients
            $params[] = $term;            // SOUNDEX match
            $params[] = $fuzzy_term;      // Variant size
            $params[] = $fuzzy_term;      // Variant color
        }
    }

    if (!empty($search_conditions)) {
        $where[] = '(' . implode(' OR ', $search_conditions) . ')';
    }
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Determine sort order based on search
$orderClause = "ORDER BY p.sr_no ASC";
$selectClause = "SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url,
           CASE WHEN bs.product_id IS NOT NULL THEN 1 ELSE 0 END as is_best_seller";

// Add relevance scoring if searching
if (!empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $selectClause .= ",
           (
               CASE WHEN p.name LIKE ? THEN 100 ELSE 0 END +
               CASE WHEN p.name LIKE ? THEN 50 ELSE 0 END +
               CASE WHEN COALESCE(p.short_description, '') LIKE ? THEN 30 ELSE 0 END +
               CASE WHEN COALESCE(p.long_description, '') LIKE ? THEN 20 ELSE 0 END +
               CASE WHEN COALESCE(p.key_benefits, '') LIKE ? THEN 25 ELSE 0 END +
               CASE WHEN COALESCE(p.ingredients, '') LIKE ? THEN 15 ELSE 0 END +
               CASE WHEN SOUNDEX(p.name) = SOUNDEX(?) THEN 35 ELSE 0 END
           ) as relevance_score";

    $orderClause = "ORDER BY relevance_score DESC, p.sr_no ASC";

    // Add relevance scoring parameters
    $relevance_params = [
        $search_query . '%',           // Exact start match
        '%' . $search_query . '%',     // Contains match
        '%' . $search_query . '%',     // Short description
        '%' . $search_query . '%',     // Long description
        '%' . $search_query . '%',     // Key benefits
        '%' . $search_query . '%',     // Ingredients
        $search_query                  // SOUNDEX match
    ];

    $params = array_merge($relevance_params, $params);
}

// Get products with their categories, primary images, and best seller status
$query = "
    $selectClause
    FROM products p
    LEFT JOIN sub_category c ON p.category_id = c.category_id
    LEFT JOIN best_sellers bs ON p.product_id = bs.product_id
    $whereClause
    $orderClause
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .sr-no-cell {
            text-align: center;
            font-weight: bold;
            width: 60px;
            background-color: #f8f9fa;
            color: #495057;
        }
        .admin-table th:first-child {
            width: 60px;
            text-align: center;
            background-color: #e9ecef;
        }
        .featured-toggle {
            text-align: center;
            width: 100px;
        }

        /* Search and Filter Styles */
        .filter-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        .filter-form .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1.5rem;
            align-items: end;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            position: relative;
        }

        .filter-group:first-child::after {
            content: '\f002';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            margin-top: 0.75rem;
            z-index: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        /* Unified Input Styling */
        .search-input,
        .filter-group select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            color: #374151;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .search-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        /* Select specific styling */
        .filter-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }

        /* Search input specific styling */
        .search-input {
            padding-right: 3rem;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-self: end;
        }

        .filter-actions .button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 2px solid transparent;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .filter-actions .button:first-child {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-actions .button:first-child:hover {
            background: #2563eb;
            border-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .filter-actions .button-secondary {
            background: white;
            color: #6b7280;
            border-color: #d1d5db;
        }

        .filter-actions .button-secondary:hover {
            background: #f9fafb;
            color: #374151;
            border-color: #9ca3af;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Search Results Info */
        .search-results-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #0369a1;
        }

        .search-results-info i {
            margin-right: 0.5rem;
        }

        /* No Results Styling */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .no-results-icon {
            font-size: 4rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .no-results h3 {
            color: #374151;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .no-results p {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-form .filter-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filter-actions {
                justify-self: stretch;
                justify-content: center;
            }

            .filter-section {
                padding: 1.5rem;
                margin: 0 -1rem 2rem -1rem;
                border-radius: 0;
            }
        }

        @media (max-width: 480px) {
            .filter-actions {
                flex-direction: column;
                gap: 0.75rem;
            }

            .filter-actions .button {
                width: 100%;
                justify-content: center;
            }
        }
        .toggle-btn.featured-active {
            background-color: #17a2b8;
            color: white;
            border-color: #17a2b8;
        }
        .toggle-btn.featured-active:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        .featured-toggle .fas.fa-gem {
            color: #17a2b8;
        }
        .featured-active .fas.fa-gem {
            color: white;
        }
    </style>
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="admin-content-header">
                <h1>Products</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="manage-usage-images.php" class="button" style="background: #28a745;">
                        <i class="fas fa-images"></i> Manage Usage Images
                    </a>
                    <a href="product-edit.php" class="button">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Products</label>
                            <input type="text"
                                   name="search"
                                   id="search"
                                   placeholder="Search by product name, description, ingredients, benefits..."
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                                   class="search-input">
                        </div>
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select name="category" id="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_id'] ?? ''); ?>"
                                            <?php echo isset($_GET['category']) && $_GET['category'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name'] ?? 'Unnamed Category'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="button">
                            <i class="fas fa-search"></i> Search & Filter
                        </button>
                        <a href="products.php" class="button button-secondary">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>

            <!-- Search Results Info -->
            <?php if (!empty($_GET['search']) || !empty($_GET['category'])): ?>
                <div class="search-results-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Search Results:</strong>
                    <?php if (!empty($_GET['search'])): ?>
                        Showing products with content matching "<?php echo htmlspecialchars($_GET['search']); ?>"
                    <?php endif; ?>
                    <?php if (!empty($_GET['category'])): ?>
                        <?php
                        $selectedCategory = '';
                        foreach ($categories as $cat) {
                            if ($cat['category_id'] == $_GET['category']) {
                                $selectedCategory = $cat['name'];
                                break;
                            }
                        }
                        ?>
                        <?php if (!empty($_GET['search'])): ?> in <?php endif; ?>
                        <?php if (!empty($selectedCategory)): ?>
                            Category: "<?php echo htmlspecialchars($selectedCategory); ?>"
                        <?php endif; ?>
                    <?php endif; ?>
                    | Found <?php echo count($products); ?> product(s)
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <!-- Products Table -->
            <?php if (empty($products)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No Products Found</h3>
                    <?php if (!empty($_GET['search']) || !empty($_GET['category'])): ?>
                        <p>No products match your search criteria. Try adjusting your search terms or filters.</p>
                        <a href="products.php" class="button">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    <?php else: ?>
                        <p>No products have been added yet.</p>
                        <a href="product-edit.php" class="button">
                            <i class="fas fa-plus"></i> Add First Product
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Best Seller</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="sr-no-cell">
                                    <?php echo htmlspecialchars($product['sr_no'] ?? ''); ?>
                                </td>
                                <td>
                                    <?php
                                    $imageUrl = $product['image_url'] ?? '';
                                    $imagePath = '../' . $imageUrl;
                                    ?>
                                    <?php if (!empty($imageUrl) && file_exists($imagePath)): ?>
                                        <img src="../<?php echo htmlspecialchars($imageUrl); ?>"
                                             alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>"
                                             class="product-thumbnail">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-image"></i>
                                            <span>No Image</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['name'] ?? 'Unnamed Product'); ?>
                                    <?php
                                    // Get variants for this product
                                    $variantStmt = $pdo->prepare('SELECT DISTINCT size FROM product_variants WHERE product_id = ?');
                                    $variantStmt->execute([$product['product_id']]);
                                    $variants = $variantStmt->fetchAll(PDO::FETCH_COLUMN);
                                    if (!empty($variants)): ?>
                                        <div class="variants-list">
                                            <small>Variants: <?php echo htmlspecialchars(implode(', ', $variants)); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                <td>
                                    <?php
                                    // Show base price, then check for variant prices
                                    $basePrice = $product['price'] ?? 0;
                                    echo '₹' . number_format($basePrice, 2);

                                    // Check if there are variants with different prices
                                    $priceStmt = $pdo->prepare('SELECT MIN(price_modifier) as min_price, MAX(price_modifier) as max_price FROM product_variants WHERE product_id = ?');
                                    $priceStmt->execute([$product['product_id']]);
                                    $priceData = $priceStmt->fetch();

                                    if ($priceData['min_price'] !== null && $priceData['min_price'] != $basePrice) {
                                        echo '<br><small>Variants: ₹' . number_format($priceData['min_price'], 2);
                                        if ($priceData['max_price'] != $priceData['min_price']) {
                                            echo ' - ₹' . number_format($priceData['max_price'], 2);
                                        }
                                        echo '</small>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Get total stock from variants
                                    $stockStmt = $pdo->prepare('SELECT SUM(stock) as total_stock FROM product_variants WHERE product_id = ?');
                                    $stockStmt->execute([$product['product_id']]);
                                    $stockData = $stockStmt->fetch();
                                    echo $stockData['total_stock'] ?? $product['stock_quantity'] ?? '0';
                                    ?>
                                </td>
                                <td class="best-seller-toggle">
                                    <form method="POST" class="toggle-form">
                                        <input type="hidden" name="toggle_best_seller" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $product['is_best_seller']; ?>">
                                        <button type="submit" class="toggle-btn <?php echo $product['is_best_seller'] ? 'active' : ''; ?>"
                                                title="<?php echo $product['is_best_seller'] ? 'Remove from Best Sellers' : 'Mark as Best Seller'; ?>">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo $product['is_best_seller'] ? 'Best Seller' : 'Mark Best'; ?></span>
                                        </button>
                                    </form>
                                </td>
                                <td class="featured-toggle">
                                    <form method="POST" class="toggle-form">
                                        <input type="hidden" name="toggle_featured" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="current_featured_status" value="<?php echo $product['is_featured'] ?? 0; ?>">
                                        <button type="submit" class="toggle-btn <?php echo ($product['is_featured'] ?? 0) ? 'featured-active' : ''; ?>"
                                                title="<?php echo ($product['is_featured'] ?? 0) ? 'Remove from Featured' : 'Mark as Featured'; ?>">
                                            <i class="fas fa-gem"></i>
                                            <span><?php echo ($product['is_featured'] ?? 0) ? 'Featured' : 'Mark Featured'; ?></span>
                                        </button>
                                    </form>
                                </td>
                                <td class="actions">
                                    <a href="product-edit.php?id=<?php echo $product['product_id']; ?>" 
                                       class="edit-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="delete-form" 
                                          onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="delete_product" 
                                               value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" class="delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Enhanced fuzzy search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const categorySelect = document.getElementById('category');
        const filterForm = document.querySelector('.filter-form');

        // Auto-submit form on category change
        categorySelect.addEventListener('change', function() {
            filterForm.submit();
        });

        // Enhanced search with fuzzy matching feedback
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchTerm = this.value.trim();

                if (searchTerm.length >= 2) {
                    // Visual feedback for search
                    this.style.background = '#e8f5e8';
                    this.style.borderColor = '#28a745';

                    // Show search indicator
                    const searchIndicator = document.createElement('div');
                    searchIndicator.innerHTML = '<i class="fas fa-search fa-spin"></i> Searching...';
                    searchIndicator.style.cssText = 'position: absolute; top: 100%; left: 0; background: #fff; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; color: #666; z-index: 1000;';
                    this.parentElement.style.position = 'relative';
                    this.parentElement.appendChild(searchIndicator);

                    setTimeout(() => {
                        filterForm.submit();
                    }, 300);
                } else if (searchTerm.length > 0) {
                    // Show minimum length warning
                    this.style.background = '#fff3cd';
                    this.style.borderColor = '#ffc107';

                    const warning = document.createElement('div');
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Enter at least 2 characters for better search results';
                    warning.style.cssText = 'position: absolute; top: 100%; left: 0; background: #fff3cd; padding: 0.5rem; border: 1px solid #ffc107; border-radius: 4px; font-size: 0.9rem; color: #856404; z-index: 1000;';
                    this.parentElement.style.position = 'relative';
                    this.parentElement.appendChild(warning);

                    setTimeout(() => {
                        warning.remove();
                        this.style.background = '';
                        this.style.borderColor = '';
                    }, 2000);
                } else {
                    filterForm.submit();
                }
            }
        });

        // Real-time search feedback with autocomplete
        let searchTimeout;
        let currentSuggestionIndex = -1;
        let suggestionsContainer = null;

        // Create suggestions container
        function createSuggestionsContainer() {
            if (!suggestionsContainer) {
                suggestionsContainer = document.createElement('div');
                suggestionsContainer.className = 'admin-search-suggestions';
                suggestionsContainer.style.cssText = `
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                    z-index: 1000;
                    max-height: 400px;
                    overflow-y: auto;
                    margin-top: 4px;
                    display: none;
                `;
                searchInput.parentElement.style.position = 'relative';
                searchInput.parentElement.appendChild(suggestionsContainer);
            }
            return suggestionsContainer;
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            // Remove any existing indicators
            const existingIndicators = this.parentElement.querySelectorAll('div');
            existingIndicators.forEach(indicator => {
                if (indicator !== this && indicator !== suggestionsContainer && indicator.style.position === 'absolute') {
                    indicator.remove();
                }
            });

            if (query.length >= 2) {
                this.style.background = '#f8f9fa';
                this.style.borderColor = '#007bff';

                searchTimeout = setTimeout(() => {
                    fetchAdminSearchSuggestions(query);
                }, 300);
            } else {
                this.style.background = '';
                this.style.borderColor = '';
                hideAdminSuggestions();
            }
        });

        // Handle keyboard navigation for admin suggestions
        searchInput.addEventListener('keydown', function(e) {
            const container = createSuggestionsContainer();
            const suggestions = container.querySelectorAll('.admin-suggestion-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                updateAdminSuggestionHighlight(suggestions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                updateAdminSuggestionHighlight(suggestions);
            } else if (e.key === 'Enter' && currentSuggestionIndex >= 0) {
                e.preventDefault();
                const selectedSuggestion = suggestions[currentSuggestionIndex];
                if (selectedSuggestion && selectedSuggestion.dataset.action === 'search') {
                    this.value = selectedSuggestion.dataset.query;
                    filterForm.submit();
                } else if (selectedSuggestion && selectedSuggestion.dataset.url) {
                    window.location.href = selectedSuggestion.dataset.url;
                }
            } else if (e.key === 'Escape') {
                hideAdminSuggestions();
                currentSuggestionIndex = -1;
            }
        });

        function fetchAdminSearchSuggestions(query) {
            const container = createSuggestionsContainer();
            container.innerHTML = '<div style="padding: 16px; text-align: center; color: #666;"><i class="fas fa-spinner fa-spin"></i> Loading suggestions...</div>';
            container.style.display = 'block';

            fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}&limit=10`)
                .then(response => response.json())
                .then(data => {
                    displayAdminSuggestions(data.suggestions);
                })
                .catch(error => {
                    console.error('Error fetching admin suggestions:', error);
                    hideAdminSuggestions();
                });
        }

        function displayAdminSuggestions(suggestions) {
            const container = createSuggestionsContainer();

            if (suggestions.length === 0) {
                container.innerHTML = '<div style="padding: 16px; text-align: center; color: #999;">No suggestions found</div>';
                container.style.display = 'block';
                return;
            }

            const html = suggestions.map((suggestion, index) => {
                let iconClass, actionUrl, actionType;

                if (suggestion.type === 'product') {
                    iconClass = 'fas fa-box';
                    actionUrl = suggestion.edit_url;
                    actionType = 'edit';
                } else if (suggestion.type === 'category') {
                    iconClass = 'fas fa-tags';
                    actionUrl = suggestion.filter_url;
                    actionType = 'filter';
                } else {
                    iconClass = 'fas fa-search';
                    actionUrl = suggestion.search_url;
                    actionType = 'search';
                }

                const statusBadges = suggestion.type === 'product' ?
                    (suggestion.is_best_seller ? '<span style="background: #ffd700; color: #000; padding: 2px 6px; border-radius: 12px; font-size: 10px; margin-left: 8px;">★ Best Seller</span>' : '') +
                    (!suggestion.is_active ? '<span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 12px; font-size: 10px; margin-left: 8px;">Inactive</span>' : '') +
                    (suggestion.stock <= 0 ? '<span style="background: #fd7e14; color: white; padding: 2px 6px; border-radius: 12px; font-size: 10px; margin-left: 8px;">Out of Stock</span>' : '')
                    : '';

                return `
                    <div class="admin-suggestion-item" data-url="${actionUrl}" data-action="${actionType}" data-query="${suggestion.title}" data-index="${index}"
                         style="padding: 12px 16px; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 12px;">
                        <div style="width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 12px; flex-shrink: 0; background: #e3f2fd; color: #1976d2;">
                            <i class="${iconClass}"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 500; color: #333; font-size: 14px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${suggestion.title}${statusBadges}
                            </div>
                            <div style="font-size: 12px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${suggestion.subtitle}${suggestion.price ? ' • ' + suggestion.price : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
            container.style.display = 'block';
            currentSuggestionIndex = -1;

            // Add click handlers
            container.querySelectorAll('.admin-suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (this.dataset.action === 'search') {
                        searchInput.value = this.dataset.query;
                        filterForm.submit();
                    } else {
                        window.location.href = this.dataset.url;
                    }
                });

                item.addEventListener('mouseenter', function() {
                    currentSuggestionIndex = parseInt(this.dataset.index);
                    updateAdminSuggestionHighlight(container.querySelectorAll('.admin-suggestion-item'));
                });
            });
        }

        function updateAdminSuggestionHighlight(suggestions) {
            suggestions.forEach((item, index) => {
                if (index === currentSuggestionIndex) {
                    item.style.background = '#f8f9fa';
                    item.style.transform = 'translateX(2px)';
                } else {
                    item.style.background = '';
                    item.style.transform = '';
                }
            });
        }

        function hideAdminSuggestions() {
            if (suggestionsContainer) {
                suggestionsContainer.style.display = 'none';
            }
            currentSuggestionIndex = -1;
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.parentElement.contains(e.target)) {
                hideAdminSuggestions();
            }
        });

        // Clear search functionality
        const clearButton = document.querySelector('.button-secondary');
        if (clearButton) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                categorySelect.value = '';
                searchInput.style.background = '';
                searchInput.style.borderColor = '';
                window.location.href = 'products.php';
            });
        }

        // Enhanced highlight search terms in results
        const searchTerm = '<?php echo addslashes($_GET['search'] ?? ''); ?>';
        if (searchTerm) {
            highlightSearchTerms(searchTerm);

            // Show search stats
            const resultCount = document.querySelectorAll('.admin-table tbody tr').length;
            const searchInfo = document.querySelector('.search-results-info');
            if (searchInfo && resultCount > 0) {
                searchInfo.innerHTML += ` (${resultCount} result${resultCount !== 1 ? 's' : ''} found with fuzzy matching)`;
            }
        }

        // Focus search input on page load if there's a search
        if (searchInput.value) {
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }

        // Keyboard shortcut to focus search (Ctrl+F or Cmd+F)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    });

    function highlightSearchTerms(term) {
        if (!term) return;

        const productRows = document.querySelectorAll('.admin-table tbody tr');
        productRows.forEach(row => {
            const nameCell = row.querySelector('td:nth-child(3)');
            const categoryCell = row.querySelector('td:nth-child(4)');

            if (nameCell) {
                highlightText(nameCell, term);
            }
            if (categoryCell) {
                highlightText(categoryCell, term);
            }
        });
    }

    function highlightText(element, term) {
        const text = element.textContent;
        const regex = new RegExp(`(${term})`, 'gi');
        const highlightedText = text.replace(regex, '<mark style="background: #fef3c7; padding: 2px 4px; border-radius: 3px;">$1</mark>');

        if (highlightedText !== text) {
            element.innerHTML = highlightedText;
        }
    }

    // Add loading state to search button
    document.querySelector('.filter-form').addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        submitBtn.disabled = true;

        // Re-enable after a short delay (in case of quick results)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
    </script>
</body>
</html>
