<?php
// Test page for search functionality
include 'includes/header.php';
include 'includes/db_connection.php';

// Test queries to verify fuzzy search functionality
$test_queries = [
    'protein',      // Exact match
    'protien',      // Misspelled
    'gainer',       // Category
    'mass',         // Partial word
    'pre work',     // Partial phrase
    'creatine',     // Ingredient
    'muscle',       // Benefit
    'whey',         // Common term
    'bcaa',         // Abbreviation
    'vitamin'       // General term
];

$search_results = [];
$performance_stats = [];

foreach ($test_queries as $query) {
    $start_time = microtime(true);
    
    // Test frontend search
    $frontend_query = "
        SELECT COUNT(*) as count
        FROM products p
        LEFT JOIN sub_category sc ON p.category_id = sc.category_id
        WHERE p.is_active = 1 AND (
            p.name LIKE ? OR
            COALESCE(p.short_description, '') LIKE ? OR
            COALESCE(p.long_description, '') LIKE ? OR
            COALESCE(p.key_benefits, '') LIKE ? OR
            COALESCE(p.ingredients, '') LIKE ? OR
            sc.name LIKE ? OR
            SOUNDEX(p.name) = SOUNDEX(?) OR
            SOUNDEX(sc.name) = SOUNDEX(?)
        )
    ";
    
    $fuzzy_term = '%' . $query . '%';
    $stmt = $pdo->prepare($frontend_query);
    $stmt->execute([$fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $query, $query]);
    $frontend_count = $stmt->fetchColumn();
    
    $frontend_time = microtime(true) - $start_time;
    
    // Test admin search
    $start_time = microtime(true);
    
    $admin_query = "
        SELECT COUNT(DISTINCT p.product_id) as count
        FROM products p
        LEFT JOIN sub_category sc ON p.category_id = sc.category_id
        WHERE (
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
        )
    ";
    
    $exact_term = $query . '%';
    $stmt = $pdo->prepare($admin_query);
    $stmt->execute([$exact_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $fuzzy_term, $query, $fuzzy_term, $fuzzy_term]);
    $admin_count = $stmt->fetchColumn();
    
    $admin_time = microtime(true) - $start_time;
    
    $search_results[] = [
        'query' => $query,
        'frontend_count' => $frontend_count,
        'frontend_time' => round($frontend_time * 1000, 2),
        'admin_count' => $admin_count,
        'admin_time' => round($admin_time * 1000, 2)
    ];
}

// Get database statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM products WHERE is_active = 1) as active_products,
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM sub_category) as categories,
        (SELECT COUNT(*) FROM product_variants) as variants,
        (SELECT COUNT(*) FROM product_images) as images
";
$stats = $pdo->query($stats_query)->fetch();
?>

<style>
.test-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.test-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.test-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.results-table th,
.results-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.results-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.performance-good {
    color: #28a745;
    font-weight: 600;
}

.performance-warning {
    color: #ffc107;
    font-weight: 600;
}

.performance-poor {
    color: #dc3545;
    font-weight: 600;
}

.recommendations {
    background: #e8f5e8;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}

.recommendations h3 {
    color: #155724;
    margin-bottom: 1rem;
}

.recommendations ul {
    color: #155724;
    margin-left: 1rem;
}

.recommendations li {
    margin-bottom: 0.5rem;
}

.test-search-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.search-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    margin-bottom: 1rem;
}

.search-btn {
    background: #007bff;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    margin-right: 1rem;
}

.search-btn:hover {
    background: #0056b3;
}
</style>

<div class="test-container">
    <div class="test-section">
        <h1 class="test-title">🔍 Search Functionality Test & Performance Analysis</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active_products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['categories']); ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['variants']); ?></div>
                <div class="stat-label">Product Variants</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['images']); ?></div>
                <div class="stat-label">Product Images</div>
            </div>
        </div>
    </div>

    <div class="test-section">
        <h2 class="test-title">🧪 Live Search Test</h2>
        <div class="test-search-form">
            <input type="text" class="search-input" placeholder="Test search functionality here..." id="live-search">
            <button class="search-btn" onclick="testFrontendSearch()">Test Frontend Search</button>
            <button class="search-btn" onclick="testAdminSearch()">Test Admin Search</button>
        </div>
        <div id="live-results"></div>
    </div>

    <div class="test-section">
        <h2 class="test-title">📊 Performance Test Results</h2>
        <p>Testing fuzzy search functionality with various query types:</p>
        
        <table class="results-table">
            <thead>
                <tr>
                    <th>Search Query</th>
                    <th>Frontend Results</th>
                    <th>Frontend Time (ms)</th>
                    <th>Admin Results</th>
                    <th>Admin Time (ms)</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($search_results as $result): ?>
                    <?php
                    $avg_time = ($result['frontend_time'] + $result['admin_time']) / 2;
                    $performance_class = $avg_time < 50 ? 'performance-good' : 
                                       ($avg_time < 100 ? 'performance-warning' : 'performance-poor');
                    $performance_text = $avg_time < 50 ? 'Excellent' : 
                                      ($avg_time < 100 ? 'Good' : 'Needs Optimization');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($result['query']); ?></strong></td>
                        <td><?php echo $result['frontend_count']; ?> results</td>
                        <td><?php echo $result['frontend_time']; ?>ms</td>
                        <td><?php echo $result['admin_count']; ?> results</td>
                        <td><?php echo $result['admin_time']; ?>ms</td>
                        <td class="<?php echo $performance_class; ?>"><?php echo $performance_text; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="test-section">
        <div class="recommendations">
            <h3>🚀 Performance Optimization Recommendations</h3>
            <ul>
                <li><strong>Database Indexes:</strong> Add indexes on frequently searched columns (products.name, short_description, long_description)</li>
                <li><strong>Full-Text Search:</strong> Consider implementing MySQL FULLTEXT indexes for better text search performance</li>
                <li><strong>Caching:</strong> Implement Redis/Memcached for frequently searched terms</li>
                <li><strong>Search Analytics:</strong> Track popular search terms to optimize indexing strategy</li>
                <li><strong>Pagination:</strong> Limit results per page to improve load times</li>
                <li><strong>Async Loading:</strong> Load search suggestions asynchronously to improve user experience</li>
                <li><strong>Search History:</strong> Store user search history for personalized suggestions</li>
                <li><strong>Elasticsearch:</strong> For large datasets, consider Elasticsearch for advanced search capabilities</li>
            </ul>
        </div>
    </div>

    <div class="test-section">
        <h2 class="test-title">✅ Features Implemented</h2>
        <ul style="list-style-type: none; padding: 0;">
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Fuzzy Search:</strong> SOUNDEX matching for misspelled words</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Partial Matching:</strong> Search works with incomplete words</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Multi-field Search:</strong> Searches across name, description, benefits, ingredients</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Relevance Scoring:</strong> Results ranked by relevance</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Auto-complete:</strong> Real-time search suggestions</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Category Search:</strong> Includes category names in search</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Variant Search:</strong> Searches product variants (admin)</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Search Highlighting:</strong> Highlights matching terms in results</li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">✅ <strong>Keyboard Navigation:</strong> Arrow keys for suggestion navigation</li>
            <li style="padding: 0.5rem 0;">✅ <strong>Performance Optimized:</strong> Efficient queries with proper indexing recommendations</li>
        </ul>
    </div>
</div>

<script>
function testFrontendSearch() {
    const query = document.getElementById('live-search').value;
    if (query.trim()) {
        window.open(`search.php?q=${encodeURIComponent(query)}`, '_blank');
    }
}

function testAdminSearch() {
    const query = document.getElementById('live-search').value;
    if (query.trim()) {
        window.open(`admin/products.php?search=${encodeURIComponent(query)}`, '_blank');
    }
}

// Add live search preview
document.getElementById('live-search').addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('live-results');
    
    if (query.length >= 2) {
        resultsDiv.innerHTML = `
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                <strong>Search Preview for "${query}":</strong><br>
                <small>• Frontend: <a href="search.php?q=${encodeURIComponent(query)}" target="_blank">search.php?q=${encodeURIComponent(query)}</a></small><br>
                <small>• Admin: <a href="admin/products.php?search=${encodeURIComponent(query)}" target="_blank">admin/products.php?search=${encodeURIComponent(query)}</a></small>
            </div>
        `;
    } else {
        resultsDiv.innerHTML = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
