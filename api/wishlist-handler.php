<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Get request method and input
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    // Check if user is logged in for most actions
    $auth = new UserAuth($pdo);
    $isLoggedIn = $auth->isLoggedIn();
    $currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

    switch ($action) {
        case 'add_to_wishlist':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to add items to your wishlist');
            }

            $productId = $input['product_id'] ?? '';
            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            // Check if product exists
            $stmt = $pdo->prepare("SELECT product_id, name FROM products WHERE product_id = ? AND is_active = 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Product not found');
            }

            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$currentUser['user_id'], $productId]);
            $existing = $stmt->fetch();

            if ($existing) {
                throw new Exception('Product is already in your wishlist');
            }

            // Add to wishlist
            $wishlistId = bin2hex(random_bytes(18));
            $stmt = $pdo->prepare("INSERT INTO wishlists (wishlist_id, user_id, product_id, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$wishlistId, $currentUser['user_id'], $productId]);

            // Get updated wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            $response = [
                'success' => true,
                'message' => 'Product added to wishlist successfully',
                'data' => [
                    'wishlist_count' => $wishlistCount,
                    'product_name' => $product['name']
                ]
            ];
            break;

        case 'remove_from_wishlist':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to manage your wishlist');
            }

            $productId = $input['product_id'] ?? '';
            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            // Remove from wishlist
            $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$currentUser['user_id'], $productId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Product not found in wishlist');
            }

            // Get updated wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            $response = [
                'success' => true,
                'message' => 'Product removed from wishlist successfully',
                'data' => [
                    'wishlist_count' => $wishlistCount
                ]
            ];
            break;

        case 'get_wishlist':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to view your wishlist');
            }

            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Get wishlist items with product details
            $query = "
                SELECT 
                    w.wishlist_id,
                    w.created_at as added_at,
                    p.product_id,
                    p.name,
                    p.price,
                    p.short_description,
                    sc.name as category_name,
                    COALESCE(
                        (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
                        (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
                    ) AS image_url,
                    (SELECT AVG(rating) FROM reviews WHERE product_id = p.product_id AND status = 'approved') as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.product_id AND status = 'approved') as review_count
                FROM wishlists w
                JOIN products p ON w.product_id = p.product_id
                LEFT JOIN sub_category sc ON p.category_id = sc.category_id
                WHERE w.user_id = ? AND p.is_active = 1
                ORDER BY w.created_at DESC
                LIMIT ? OFFSET ?
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$currentUser['user_id'], $limit, $offset]);
            $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlists w JOIN products p ON w.product_id = p.product_id WHERE w.user_id = ? AND p.is_active = 1");
            $countStmt->execute([$currentUser['user_id']]);
            $totalItems = $countStmt->fetch()['total'];

            // Process wishlist items
            foreach ($wishlistItems as &$item) {
                $item['avg_rating'] = $item['avg_rating'] ? round($item['avg_rating'], 1) : 0;
                $item['review_count'] = intval($item['review_count']);
                $item['price'] = floatval($item['price']);
                
                // Process image URL
                if ($item['image_url']) {
                    $imgFile = basename($item['image_url']);
                    $item['image_url'] = 'assets/' . $imgFile;
                }
            }

            $response = [
                'success' => true,
                'message' => 'Wishlist retrieved successfully',
                'data' => [
                    'items' => $wishlistItems,
                    'pagination' => [
                        'current_page' => $page,
                        'total_items' => $totalItems,
                        'items_per_page' => $limit,
                        'total_pages' => ceil($totalItems / $limit)
                    ]
                ]
            ];
            break;

        case 'check_wishlist_status':
            if (!$isLoggedIn) {
                $response = [
                    'success' => true,
                    'data' => [
                        'in_wishlist' => false,
                        'wishlist_count' => 0
                    ]
                ];
                break;
            }

            $productId = $_GET['product_id'] ?? '';
            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            // Check if product is in wishlist
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$currentUser['user_id'], $productId]);
            $inWishlist = $stmt->fetch() ? true : false;

            // Get total wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            $response = [
                'success' => true,
                'data' => [
                    'in_wishlist' => $inWishlist,
                    'wishlist_count' => $wishlistCount
                ]
            ];
            break;

        case 'move_to_cart':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to move items to cart');
            }

            $productId = $input['product_id'] ?? '';
            $quantity = intval($input['quantity'] ?? 1);
            $removeFromWishlist = $input['remove_from_wishlist'] ?? true;

            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            // Check if product exists and is in wishlist
            $stmt = $pdo->prepare("
                SELECT w.wishlist_id, p.product_id, p.name 
                FROM wishlists w 
                JOIN products p ON w.product_id = p.product_id 
                WHERE w.user_id = ? AND w.product_id = ? AND p.is_active = 1
            ");
            $stmt->execute([$currentUser['user_id'], $productId]);
            $wishlistItem = $stmt->fetch();

            if (!$wishlistItem) {
                throw new Exception('Product not found in wishlist');
            }

            // Add to cart (using session-based cart like the existing system)
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $cartKey = $productId . '_default'; // No variant for wishlist items
            
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s')
                ];
            }

            // Remove from wishlist if requested
            if ($removeFromWishlist) {
                $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$currentUser['user_id'], $productId]);
            }

            // Get cart count
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }

            // Get updated wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            $response = [
                'success' => true,
                'message' => 'Product moved to cart successfully',
                'data' => [
                    'cart_count' => $cartCount,
                    'wishlist_count' => $wishlistCount,
                    'product_name' => $wishlistItem['name']
                ]
            ];
            break;

        case 'get_wishlist_count':
            if (!$isLoggedIn) {
                $response = [
                    'success' => true,
                    'data' => ['wishlist_count' => 0]
                ];
                break;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            $response = [
                'success' => true,
                'data' => ['wishlist_count' => $wishlistCount]
            ];
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
}

echo json_encode($response);
?>
