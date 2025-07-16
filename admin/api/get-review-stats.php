<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Get review statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_reviews,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
            COUNT(CASE WHEN status = 'spam' THEN 1 END) as spam_reviews,
            AVG(CASE WHEN status = 'approved' THEN rating END) as avg_rating,
            COUNT(CASE WHEN verified_purchase = 1 THEN 1 END) as verified_reviews,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as reviews_today,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reviews_this_week
        FROM reviews
    ";
    
    $stmt = $pdo->query($statsQuery);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top rated products
    $topRatedQuery = "
        SELECT 
            p.name as product_name,
            p.product_id,
            COUNT(r.review_id) as review_count,
            AVG(r.rating) as avg_rating
        FROM products p
        JOIN reviews r ON p.product_id = r.product_id
        WHERE r.status = 'approved'
        GROUP BY p.product_id, p.name
        HAVING review_count >= 3
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($topRatedQuery);
    $topRated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $recentActivityQuery = "
        SELECT 
            r.review_id,
            r.title,
            r.rating,
            r.status,
            r.created_at,
            p.name as product_name,
            CASE 
                WHEN r.user_id IS NOT NULL THEN CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))
                ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_name')), 'Anonymous')
            END as reviewer_name
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.product_id
        LEFT JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->query($recentActivityQuery);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format numbers
    $stats['avg_rating'] = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
    
    $response['success'] = true;
    $response['data'] = [
        'stats' => $stats,
        'top_rated_products' => $topRated,
        'recent_activity' => $recentActivity
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>
