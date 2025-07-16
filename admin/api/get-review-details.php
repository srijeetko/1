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
    $review_id = $_GET['review_id'] ?? '';
    
    if (empty($review_id)) {
        throw new Exception('Review ID is required');
    }
    
    // Get detailed review information
    $query = "
        SELECT 
            r.*,
            p.name as product_name,
            CASE 
                WHEN r.user_id IS NOT NULL THEN CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))
                ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_name')), 'Anonymous')
            END as reviewer_name,
            CASE 
                WHEN r.user_id IS NOT NULL THEN u.email
                ELSE JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_email'))
            END as reviewer_email,
            (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 1) as helpful_yes_count,
            (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 0) as helpful_no_count,
            (SELECT COUNT(*) FROM review_reports rr WHERE rr.review_id = r.review_id) as report_count
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.product_id
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.review_id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        throw new Exception('Review not found');
    }
    
    // Get review reports if any
    $reportsQuery = "
        SELECT 
            rr.*,
            u.first_name as reporter_name,
            u.email as reporter_email
        FROM review_reports rr
        LEFT JOIN users u ON rr.reporter_user_id = u.user_id
        WHERE rr.review_id = ?
        ORDER BY rr.created_at DESC
    ";
    
    $reportsStmt = $pdo->prepare($reportsQuery);
    $reportsStmt->execute([$review_id]);
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    $review['created_at_formatted'] = date('M j, Y g:i A', strtotime($review['created_at']));
    $review['updated_at_formatted'] = $review['updated_at'] ? date('M j, Y g:i A', strtotime($review['updated_at'])) : null;
    $review['admin_response_date_formatted'] = $review['admin_response_date'] ? date('M j, Y g:i A', strtotime($review['admin_response_date'])) : null;
    
    // Add reports to review data
    $review['reports'] = $reports;
    
    $response['success'] = true;
    $response['data'] = $review;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
