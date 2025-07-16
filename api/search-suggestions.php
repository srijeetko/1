<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../includes/db_connection.php';

// Get search query
$query = $_GET['q'] ?? '';
$limit = min(10, $_GET['limit'] ?? 8);

$suggestions = [];

if (strlen($query) >= 2) {
    try {
        // Search for product suggestions with fuzzy matching
        $search_query = "
            SELECT DISTINCT p.name, p.product_id, sc.name as category_name,
                   (
                       CASE WHEN p.name LIKE ? THEN 100 ELSE 0 END +
                       CASE WHEN p.name LIKE ? THEN 50 ELSE 0 END +
                       CASE WHEN SOUNDEX(p.name) = SOUNDEX(?) THEN 30 ELSE 0 END +
                       CASE WHEN sc.name LIKE ? THEN 20 ELSE 0 END
                   ) as relevance_score
            FROM products p
            LEFT JOIN sub_category sc ON p.category_id = sc.category_id
            WHERE p.is_active = 1 AND (
                p.name LIKE ? OR
                p.name LIKE ? OR
                COALESCE(p.short_description, '') LIKE ? OR
                SOUNDEX(p.name) = SOUNDEX(?) OR
                sc.name LIKE ?
            )
            HAVING relevance_score > 0
            ORDER BY relevance_score DESC, p.name ASC
            LIMIT ?
        ";
        
        $exact_start = $query . '%';
        $fuzzy_match = '%' . $query . '%';
        
        $stmt = $pdo->prepare($search_query);
        $stmt->execute([
            $exact_start,    // Exact start match (highest priority)
            $fuzzy_match,    // Contains match
            $query,          // SOUNDEX match
            $fuzzy_match,    // Category match
            $exact_start,    // Product name exact start
            $fuzzy_match,    // Product name contains
            $fuzzy_match,    // Description contains
            $query,          // Product name SOUNDEX
            $fuzzy_match,    // Category contains
            $limit
        ]);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $suggestions[] = [
                'type' => 'product',
                'title' => $product['name'],
                'subtitle' => $product['category_name'] ?? 'Product',
                'url' => 'product-detail.php?id=' . $product['product_id'],
                'search_url' => 'search.php?q=' . urlencode($product['name'])
            ];
        }
        
        // Add category suggestions if we have space
        if (count($suggestions) < $limit) {
            $remaining_limit = $limit - count($suggestions);
            
            $category_query = "
                SELECT DISTINCT sc.name, sc.category_id,
                       (
                           CASE WHEN sc.name LIKE ? THEN 100 ELSE 0 END +
                           CASE WHEN sc.name LIKE ? THEN 50 ELSE 0 END +
                           CASE WHEN SOUNDEX(sc.name) = SOUNDEX(?) THEN 30 ELSE 0 END
                       ) as relevance_score
                FROM sub_category sc
                WHERE sc.name LIKE ? OR sc.name LIKE ? OR SOUNDEX(sc.name) = SOUNDEX(?)
                HAVING relevance_score > 0
                ORDER BY relevance_score DESC, sc.name ASC
                LIMIT ?
            ";
            
            $stmt = $pdo->prepare($category_query);
            $stmt->execute([
                $exact_start,    // Exact start match
                $fuzzy_match,    // Contains match
                $query,          // SOUNDEX match
                $exact_start,    // Category name exact start
                $fuzzy_match,    // Category name contains
                $query,          // Category name SOUNDEX
                $remaining_limit
            ]);
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categories as $category) {
                $suggestions[] = [
                    'type' => 'category',
                    'title' => $category['name'],
                    'subtitle' => 'Category',
                    'url' => 'products.php?category=' . $category['category_id'],
                    'search_url' => 'search.php?q=' . urlencode($category['name'])
                ];
            }
        }
        
        // Add "Search for" suggestion if we have results
        if (!empty($suggestions)) {
            array_unshift($suggestions, [
                'type' => 'search',
                'title' => 'Search for "' . htmlspecialchars($query) . '"',
                'subtitle' => 'View all results',
                'url' => 'search.php?q=' . urlencode($query),
                'search_url' => 'search.php?q=' . urlencode($query)
            ]);
        }
        
    } catch (Exception $e) {
        // Log error but don't expose it
        error_log('Search suggestions error: ' . $e->getMessage());
        $suggestions = [];
    }
}

// Return JSON response
echo json_encode([
    'query' => $query,
    'suggestions' => $suggestions,
    'count' => count($suggestions)
]);
?>
