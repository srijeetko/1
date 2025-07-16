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
    
    // Get form data (multipart/form-data for file uploads)
    $input = $_POST;
    
    // Validate required fields
    $required_fields = ['product_id', 'rating', 'title', 'content'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $product_id = trim($input['product_id']);
    $rating = intval($input['rating']);
    $title = trim($input['title']);
    $content = trim($input['content']);
    $user_id = $input['user_id'] ?? null;
    $guest_name = $input['guest_name'] ?? null;
    $guest_email = $input['guest_email'] ?? null;
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }
    
    // Validate title and content length
    if (strlen($title) > 100) {
        throw new Exception('Title must be 100 characters or less');
    }
    
    if (strlen($content) > 2000) {
        throw new Exception('Review content must be 2000 characters or less');
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT product_id, name FROM products WHERE product_id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found or inactive');
    }
    
    // Handle user authentication
    // $auth instance is already created in auth.php
    $is_logged_in = $auth->isLoggedIn();
    $verified_purchase = 0;
    
    if ($is_logged_in) {
        $current_user = $auth->getCurrentUser();
        $user_id = $current_user['user_id'];
        
        // Check if user has purchased this product (verified purchase)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as purchase_count 
            FROM checkout_orders co
            JOIN order_items oi ON co.order_id = oi.order_id
            WHERE co.user_id = ? AND oi.product_id = ? AND co.order_status IN ('confirmed', 'delivered')
        ");
        $stmt->execute([$user_id, $product_id]);
        $purchase_result = $stmt->fetch();
        $verified_purchase = $purchase_result['purchase_count'] > 0 ? 1 : 0;
        
        // Check if user has already reviewed this product
        $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        if ($stmt->fetch()) {
            throw new Exception('You have already reviewed this product');
        }
        
    } else {
        // For guest reviews, require name and email
        if (!$guest_name || !$guest_email) {
            throw new Exception('Name and email are required for guest reviews');
        }
        
        if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please provide a valid email address');
        }
        
        // Check if guest has already reviewed this product with same email
        $stmt = $pdo->prepare("
            SELECT review_id FROM reviews 
            WHERE product_id = ? AND user_id IS NULL AND JSON_EXTRACT(review_images, '$.guest_email') = ?
        ");
        $stmt->execute([$product_id, $guest_email]);
        if ($stmt->fetch()) {
            throw new Exception('A review from this email already exists for this product');
        }
    }
    
    // Generate review ID
    $review_id = bin2hex(random_bytes(18));
    
    // Prepare review data
    $review_images_data = [];
    if (!$is_logged_in) {
        $review_images_data['guest_name'] = $guest_name;
        $review_images_data['guest_email'] = $guest_email;
    }
    
    // Handle image uploads if any
    if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
        $uploaded_images = [];
        $upload_dir = '../assets/reviews/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        for ($i = 0; $i < count($_FILES['review_images']['name']); $i++) {
            if ($_FILES['review_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['review_images']['type'][$i];
                $file_size = $_FILES['review_images']['size'][$i];
                
                if (!in_array($file_type, $allowed_types)) {
                    throw new Exception('Only JPEG, PNG, and GIF images are allowed');
                }
                
                if ($file_size > $max_file_size) {
                    throw new Exception('Image file size must be less than 5MB');
                }
                
                $file_extension = pathinfo($_FILES['review_images']['name'][$i], PATHINFO_EXTENSION);
                $new_filename = 'review_' . $review_id . '_' . ($i + 1) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['review_images']['tmp_name'][$i], $upload_path)) {
                    $uploaded_images[] = 'assets/reviews/' . $new_filename;
                }
            }
        }
        
        if (!empty($uploaded_images)) {
            $review_images_data['images'] = $uploaded_images;
        }
    }
    
    // Convert review images data to JSON
    $review_images_json = !empty($review_images_data) ? json_encode($review_images_data) : null;
    
    // Insert review into database
    $stmt = $pdo->prepare("
        INSERT INTO reviews (
            review_id, user_id, product_id, rating, title, content, 
            verified_purchase, review_images, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $review_id,
        $user_id,
        $product_id,
        $rating,
        $title,
        $content,
        $verified_purchase,
        $review_images_json
    ]);
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Review submitted successfully! It will be published after moderation.';
    $response['data'] = [
        'review_id' => $review_id,
        'status' => 'pending',
        'verified_purchase' => $verified_purchase,
        'product_name' => $product['name']
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
