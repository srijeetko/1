<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Include database connection
require_once '../../includes/db_connection.php';

// Get search query
$query = $_GET['q'] ?? '';
$limit = min(15, $_GET['limit'] ?? 10);

$suggestions = [];

if (strlen($query) >= 2) {
    try {
        // Search for product suggestions with enhanced admin-specific information
        $search_query = "
            SELECT DISTINCT p.product_id, p.name, p.price, p.stock_quantity, p.is_active,
                   sc.name as category_name,
                   (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url,
                   CASE WHEN bs.product_id IS NOT NULL THEN 1 ELSE 0 END as is_best_seller,
                   (
                       CASE WHEN p.name LIKE ? THEN 100 ELSE 0 END +
                       CASE WHEN p.name LIKE ? THEN 50 ELSE 0 END +
                       CASE WHEN SOUNDEX(p.name) = SOUNDEX(?) THEN 30 ELSE 0 END +
                       CASE WHEN COALESCE(p.short_description, '') LIKE ? THEN 20 ELSE 0 END +
                       CASE WHEN sc.name LIKE ? THEN 15 ELSE 0 END
                   ) as relevance_score
            FROM products p
            LEFT JOIN sub_category sc ON p.category_id = sc.category_id
            LEFT JOIN best_sellers bs ON p.product_id = bs.product_id
            WHERE (
                p.name LIKE ? OR
                p.name LIKE ? OR
                COALESCE(p.short_description, '') LIKE ? OR
                COALESCE(p.long_description, '') LIKE ? OR
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
            $fuzzy_match,    // Description match
            $fuzzy_match,    // Category match
            $exact_start,    // Product name exact start
            $fuzzy_match,    // Product name contains
            $fuzzy_match,    // Short description contains
            $fuzzy_match,    // Long description contains
            $query,          // Product name SOUNDEX
            $fuzzy_match,    // Category contains
            $limit
        ]);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $status_info = [];
            if (!$product['is_active']) $status_info[] = 'Inactive';
            if ($product['is_best_seller']) $status_info[] = 'Best Seller';
            if ($product['stock_quantity'] <= 0) $status_info[] = 'Out of Stock';
            
            $subtitle = $product['category_name'] ?? 'Product';
            if (!empty($status_info)) {
                $subtitle .= ' • ' . implode(', ', $status_info);
            }
            
            $suggestions[] = [
                'type' => 'product',
                'id' => $product['product_id'],
                'title' => $product['name'],
                'subtitle' => $subtitle,
                'price' => '₹' . number_format($product['price'], 2),
                'stock' => $product['stock_quantity'],
                'is_active' => $product['is_active'],
                'is_best_seller' => $product['is_best_seller'],
                'image_url' => $product['image_url'],
                'edit_url' => 'product-edit.php?id=' . $product['product_id'],
                'view_url' => '../product-detail.php?id=' . $product['product_id']
            ];
        }
        
        // Add category suggestions if we have space
        if (count($suggestions) < $limit) {
            $remaining_limit = $limit - count($suggestions);
            
            $category_query = "
                SELECT DISTINCT sc.name, sc.category_id,
                       COUNT(p.product_id) as product_count,
                       (
                           CASE WHEN sc.name LIKE ? THEN 100 ELSE 0 END +
                           CASE WHEN sc.name LIKE ? THEN 50 ELSE 0 END +
                           CASE WHEN SOUNDEX(sc.name) = SOUNDEX(?) THEN 30 ELSE 0 END
                       ) as relevance_score
                FROM sub_category sc
                LEFT JOIN products p ON sc.category_id = p.category_id
                WHERE sc.name LIKE ? OR sc.name LIKE ? OR SOUNDEX(sc.name) = SOUNDEX(?)
                GROUP BY sc.category_id, sc.name
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
                    'id' => $category['category_id'],
                    'title' => $category['name'],
                    'subtitle' => $category['product_count'] . ' product' . ($category['product_count'] != 1 ? 's' : ''),
                    'filter_url' => 'products.php?category=' . $category['category_id'],
                    'search_url' => 'products.php?search=' . urlencode($category['name'])
                ];
            }
        }
        
        // Add "Search for" suggestion
        if (!empty($suggestions)) {
            array_unshift($suggestions, [
                'type' => 'search',
                'title' => 'Search for "' . htmlspecialchars($query) . '"',
                'subtitle' => 'View all matching products',
                'search_url' => 'products.php?search=' . urlencode($query)
            ]);
        }
        
    } catch (Exception $e) {
        // Log error but don't expose it
        error_log('Admin search suggestions error: ' . $e->getMessage());
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
