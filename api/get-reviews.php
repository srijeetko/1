<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../includes/db_connection.php';

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Get parameters
    $product_id = $_GET['product_id'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $sort = $_GET['sort'] ?? 'newest'; // newest, oldest, highest_rating, lowest_rating, most_helpful
    
    if (empty($product_id)) {
        throw new Exception('Product ID is required');
    }
    
    // Verify product exists
    $stmt = $pdo->prepare("SELECT product_id, name FROM products WHERE product_id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Determine sort order
    $orderBy = 'r.created_at DESC'; // default
    switch ($sort) {
        case 'oldest':
            $orderBy = 'r.created_at ASC';
            break;
        case 'highest_rating':
            $orderBy = 'r.rating DESC, r.created_at DESC';
            break;
        case 'lowest_rating':
            $orderBy = 'r.rating ASC, r.created_at DESC';
            break;
        case 'most_helpful':
            $orderBy = 'r.helpful_count DESC, r.created_at DESC';
            break;
    }
    
    // Get reviews with user information
    $reviewsQuery = "
        SELECT 
            r.*,
            CASE 
                WHEN r.user_id IS NOT NULL THEN CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))
                ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_name')), 'Anonymous')
            END as reviewer_name,
            u.email as reviewer_email,
            (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 1) as helpful_yes_count,
            (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 0) as helpful_no_count
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.product_id = ? AND r.status = 'approved'
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($reviewsQuery);
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total
        FROM reviews r
        WHERE r.product_id = ? AND r.status = 'approved'
    ";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute([$product_id]);
    $totalReviews = $stmt->fetch()['total'];
    
    // Get review statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating,
            COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_count,
            COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star_count,
            COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star_count,
            COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star_count,
            COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_count,
            COUNT(CASE WHEN verified_purchase = 1 THEN 1 END) as verified_reviews_count
        FROM reviews
        WHERE product_id = ? AND status = 'approved'
    ";
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute([$product_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate rating percentages
    if ($stats['total_reviews'] > 0) {
        $stats['five_star_percentage'] = round(($stats['five_star_count'] / $stats['total_reviews']) * 100, 1);
        $stats['four_star_percentage'] = round(($stats['four_star_count'] / $stats['total_reviews']) * 100, 1);
        $stats['three_star_percentage'] = round(($stats['three_star_count'] / $stats['total_reviews']) * 100, 1);
        $stats['two_star_percentage'] = round(($stats['two_star_count'] / $stats['total_reviews']) * 100, 1);
        $stats['one_star_percentage'] = round(($stats['one_star_count'] / $stats['total_reviews']) * 100, 1);
        $stats['average_rating'] = round($stats['average_rating'], 1);
    } else {
        $stats['five_star_percentage'] = 0;
        $stats['four_star_percentage'] = 0;
        $stats['three_star_percentage'] = 0;
        $stats['two_star_percentage'] = 0;
        $stats['one_star_percentage'] = 0;
        $stats['average_rating'] = 0;
    }
    
    // Pagination info
    $totalPages = ceil($totalReviews / $limit);
    $pagination = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_reviews' => $totalReviews,
        'per_page' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];
    
    // Format reviews for output
    foreach ($reviews as &$review) {
        // Format dates
        $review['created_at_formatted'] = date('M j, Y', strtotime($review['created_at']));
        $review['updated_at_formatted'] = $review['updated_at'] ? date('M j, Y', strtotime($review['updated_at'])) : null;
        
        // Parse review images if exists
        if ($review['review_images']) {
            $imageData = json_decode($review['review_images'], true);
            $review['has_images'] = isset($imageData['images']) && !empty($imageData['images']);
            $review['image_count'] = $review['has_images'] ? count($imageData['images']) : 0;
        } else {
            $review['has_images'] = false;
            $review['image_count'] = 0;
        }
        
        // Hide sensitive information
        unset($review['reviewer_email']);
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Reviews loaded successfully';
    $response['data'] = [
        'reviews' => $reviews,
        'stats' => $stats,
        'pagination' => $pagination,
        'product' => [
            'id' => $product['product_id'],
            'name' => $product['name']
        ]
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
