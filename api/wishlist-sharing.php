<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
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

    // Check if user is logged in
    $auth = new UserAuth($pdo);
    $isLoggedIn = $auth->isLoggedIn();
    $currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

    switch ($action) {
        case 'create_share_link':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to share your wishlist');
            }

            // Check if user has wishlist items
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
            $stmt->execute([$currentUser['user_id']]);
            $wishlistCount = $stmt->fetch()['count'];

            if ($wishlistCount === 0) {
                throw new Exception('Your wishlist is empty. Add some products first!');
            }

            // Create or get existing share token
            $shareToken = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days')); // Link expires in 30 days

            // Check if user already has a share link
            $stmt = $pdo->prepare("
                SELECT share_token FROM wishlist_shares 
                WHERE user_id = ? AND expires_at > NOW() AND is_active = 1
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$currentUser['user_id']]);
            $existingShare = $stmt->fetch();

            if ($existingShare) {
                $shareToken = $existingShare['share_token'];
            } else {
                // Create new share record
                $shareId = bin2hex(random_bytes(18));
                $stmt = $pdo->prepare("
                    INSERT INTO wishlist_shares (share_id, user_id, share_token, expires_at, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$shareId, $currentUser['user_id'], $shareToken, $expiresAt]);
            }

            $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                       '://' . $_SERVER['HTTP_HOST'] . 
                       dirname(dirname($_SERVER['REQUEST_URI'])) . 
                       '/shared-wishlist.php?token=' . $shareToken;

            $response = [
                'success' => true,
                'message' => 'Share link created successfully',
                'data' => [
                    'share_url' => $shareUrl,
                    'share_token' => $shareToken,
                    'expires_at' => $expiresAt,
                    'wishlist_count' => $wishlistCount
                ]
            ];
            break;

        case 'get_shared_wishlist':
            $shareToken = $_GET['token'] ?? '';
            if (empty($shareToken)) {
                throw new Exception('Share token is required');
            }

            // Get shared wishlist details
            $stmt = $pdo->prepare("
                SELECT ws.*, u.first_name, u.last_name 
                FROM wishlist_shares ws
                JOIN users u ON ws.user_id = u.user_id
                WHERE ws.share_token = ? AND ws.expires_at > NOW() AND ws.is_active = 1
            ");
            $stmt->execute([$shareToken]);
            $shareDetails = $stmt->fetch();

            if (!$shareDetails) {
                throw new Exception('Invalid or expired share link');
            }

            // Get wishlist items
            $query = "
                SELECT 
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
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.product_id AND status = 'approved') as review_count,
                    w.created_at as added_at
                FROM wishlists w
                JOIN products p ON w.product_id = p.product_id
                LEFT JOIN sub_category sc ON p.category_id = sc.category_id
                WHERE w.user_id = ? AND p.is_active = 1
                ORDER BY w.created_at DESC
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$shareDetails['user_id']]);
            $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                'message' => 'Shared wishlist retrieved successfully',
                'data' => [
                    'owner_name' => $shareDetails['first_name'] . ' ' . $shareDetails['last_name'],
                    'items' => $wishlistItems,
                    'total_items' => count($wishlistItems),
                    'shared_at' => $shareDetails['created_at']
                ]
            ];
            break;

        case 'deactivate_share':
            if (!$isLoggedIn) {
                throw new Exception('Please log in to manage your wishlist sharing');
            }

            // Deactivate all active share links for the user
            $stmt = $pdo->prepare("UPDATE wishlist_shares SET is_active = 0 WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$currentUser['user_id']]);

            $response = [
                'success' => true,
                'message' => 'All share links have been deactivated',
                'data' => ['deactivated_count' => $stmt->rowCount()]
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
