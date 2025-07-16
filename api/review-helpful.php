<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // Fallback to form data
    }
    
    // Validate required fields
    $review_id = $input['review_id'] ?? '';
    $is_helpful = isset($input['is_helpful']) ? (bool)$input['is_helpful'] : null;
    
    if (empty($review_id)) {
        throw new Exception('Review ID is required');
    }
    
    if ($is_helpful === null) {
        throw new Exception('Helpful status is required');
    }
    
    // Check if review exists and is approved
    $stmt = $pdo->prepare("
        SELECT r.review_id, r.product_id, p.name as product_name
        FROM reviews r
        JOIN products p ON r.product_id = p.product_id
        WHERE r.review_id = ? AND r.status = 'approved'
    ");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch();
    
    if (!$review) {
        throw new Exception('Review not found or not approved');
    }
    
    // Handle user authentication
    $auth = new Auth($pdo);
    $is_logged_in = $auth->isLoggedIn();
    $user_id = null;
    $guest_identifier = null;
    
    if ($is_logged_in) {
        $current_user = $auth->getCurrentUser();
        $user_id = $current_user['user_id'];
    } else {
        // For guest users, use IP address and user agent as identifier
        $guest_identifier = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        
        // Check if guest has already voted (using a temporary approach)
        // In production, you might want to use cookies or other methods
        $stmt = $pdo->prepare("
            SELECT helpful_id FROM review_helpful 
            WHERE review_id = ? AND user_id IS NULL AND helpful_id LIKE ?
        ");
        $stmt->execute([$review_id, $guest_identifier . '%']);
        if ($stmt->fetch()) {
            throw new Exception('You have already voted on this review');
        }
    }
    
    // Check if user has already voted on this review
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT helpful_id, is_helpful FROM review_helpful 
            WHERE review_id = ? AND user_id = ?
        ");
        $stmt->execute([$review_id, $user_id]);
        $existing_vote = $stmt->fetch();
        
        if ($existing_vote) {
            if ($existing_vote['is_helpful'] == $is_helpful) {
                // Same vote - remove it (toggle off)
                $stmt = $pdo->prepare("DELETE FROM review_helpful WHERE review_id = ? AND user_id = ?");
                $stmt->execute([$review_id, $user_id]);
                $action = 'removed';
            } else {
                // Different vote - update it
                $stmt = $pdo->prepare("
                    UPDATE review_helpful 
                    SET is_helpful = ?, created_at = NOW() 
                    WHERE review_id = ? AND user_id = ?
                ");
                $stmt->execute([$is_helpful, $review_id, $user_id]);
                $action = 'updated';
            }
        } else {
            // New vote - insert it
            $helpful_id = bin2hex(random_bytes(18));
            $stmt = $pdo->prepare("
                INSERT INTO review_helpful (helpful_id, review_id, user_id, is_helpful, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$helpful_id, $review_id, $user_id, $is_helpful]);
            $action = 'added';
        }
    } else {
        // Guest vote - insert with guest identifier
        $helpful_id = $guest_identifier . '_' . $review_id;
        $stmt = $pdo->prepare("
            INSERT INTO review_helpful (helpful_id, review_id, user_id, is_helpful, created_at)
            VALUES (?, ?, NULL, ?, NOW())
        ");
        $stmt->execute([$helpful_id, $review_id, $is_helpful]);
        $action = 'added';
    }
    
    // Get updated counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN is_helpful = 1 THEN 1 END) as helpful_yes_count,
            COUNT(CASE WHEN is_helpful = 0 THEN 1 END) as helpful_no_count
        FROM review_helpful 
        WHERE review_id = ?
    ");
    $stmt->execute([$review_id]);
    $counts = $stmt->fetch();
    
    // Update the helpful_count in reviews table (for helpful votes only)
    $stmt = $pdo->prepare("
        UPDATE reviews 
        SET helpful_count = ? 
        WHERE review_id = ?
    ");
    $stmt->execute([$counts['helpful_yes_count'], $review_id]);
    
    // Success response
    $response['success'] = true;
    $response['message'] = ucfirst($action) . ' your vote successfully';
    $response['data'] = [
        'review_id' => $review_id,
        'action' => $action,
        'helpful_yes_count' => (int)$counts['helpful_yes_count'],
        'helpful_no_count' => (int)$counts['helpful_no_count'],
        'user_vote' => $action === 'removed' ? null : $is_helpful
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
