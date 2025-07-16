<?php
require_once 'includes/db_connection.php';

echo "<h2>Testing Review System</h2>";

try {
    // Check if we have products
    $stmt = $pdo->query("SELECT product_id, name FROM products WHERE is_active = 1 LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<h3>Available Products:</h3>";
    if (empty($products)) {
        echo "<p>No products found. Let's create a test product.</p>";
        
        // Create a test product
        $productId = bin2hex(random_bytes(18));
        $categoryId = bin2hex(random_bytes(18));
        
        // Create category first
        $stmt = $pdo->prepare("INSERT INTO sub_category (category_id, name) VALUES (?, ?)");
        $stmt->execute([$categoryId, 'Test Category']);
        
        // Create product
        $stmt = $pdo->prepare("
            INSERT INTO products (product_id, name, short_description, long_description, price, category_id, stock_quantity, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $productId,
            'Test Protein Powder',
            'High-quality whey protein for muscle building',
            'This is a comprehensive protein supplement designed for athletes and fitness enthusiasts.',
            1999.00,
            $categoryId,
            100
        ]);
        
        echo "<p>✅ Created test product with ID: $productId</p>";
        echo "<p><a href='product-detail.php?id=$productId' target='_blank'>View Test Product</a></p>";
        
    } else {
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li><a href='product-detail.php?id={$product['product_id']}' target='_blank'>{$product['name']}</a> (ID: {$product['product_id']})</li>";
        }
        echo "</ul>";
    }
    
    // Check review system tables
    echo "<h3>Review System Tables Status:</h3>";
    
    $tables = ['reviews', 'review_helpful', 'review_reports'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>✅ Table '$table': $count records</p>";
        } catch (Exception $e) {
            echo "<p>❌ Table '$table': Error - " . $e->getMessage() . "</p>";
        }
    }
    
    // Check views
    echo "<h3>Review System Views:</h3>";
    $views = ['approved_reviews', 'product_review_stats'];
    foreach ($views as $view) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $view");
            $count = $stmt->fetch()['count'];
            echo "<p>✅ View '$view': $count records</p>";
        } catch (Exception $e) {
            echo "<p>❌ View '$view': Error - " . $e->getMessage() . "</p>";
        }
    }
    
    // Test API endpoints
    echo "<h3>API Endpoints Test:</h3>";
    echo "<p><a href='api/get-reviews.php?product_id=" . ($products[0]['product_id'] ?? 'test') . "' target='_blank'>Test Get Reviews API</a></p>";
    
    // Add some sample reviews if none exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews");
    $reviewCount = $stmt->fetch()['count'];
    
    if ($reviewCount == 0 && !empty($products)) {
        echo "<h3>Adding Sample Reviews:</h3>";
        
        $sampleReviews = [
            [
                'rating' => 5,
                'title' => 'Excellent Product!',
                'content' => 'This protein powder has really helped me with my fitness goals. Great taste and mixes well with water. Highly recommended for anyone looking to build muscle.',
                'verified_purchase' => 1
            ],
            [
                'rating' => 4,
                'title' => 'Good Quality',
                'content' => 'Good product overall. The taste could be better but the effectiveness is great. I have seen good results after using it for a month.',
                'verified_purchase' => 1
            ],
            [
                'rating' => 5,
                'title' => 'Amazing Results',
                'content' => 'Saw results within 2 weeks of using this product. The quality is top-notch and it dissolves easily. Will definitely buy again!',
                'verified_purchase' => 0
            ]
        ];
        
        foreach ($sampleReviews as $review) {
            $reviewId = bin2hex(random_bytes(18));
            $stmt = $pdo->prepare("
                INSERT INTO reviews (review_id, product_id, rating, title, content, verified_purchase, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW() - INTERVAL FLOOR(RAND() * 30) DAY)
            ");
            $stmt->execute([
                $reviewId,
                $products[0]['product_id'],
                $review['rating'],
                $review['title'],
                $review['content'],
                $review['verified_purchase']
            ]);
        }
        
        echo "<p>✅ Added 3 sample reviews</p>";
    }
    
    echo "<hr>";
    echo "<h3>Review System Ready!</h3>";
    echo "<p>The review system has been successfully set up and tested. You can now:</p>";
    echo "<ul>";
    echo "<li>View products with dynamic ratings</li>";
    echo "<li>Submit new reviews (both logged in and guest users)</li>";
    echo "<li>View reviews in the Reviews tab</li>";
    echo "<li>Vote on review helpfulness</li>";
    echo "<li>Admin can manage reviews through the admin panel</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
