<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

echo "<h2>Setup Usage Steps for Products</h2>";

try {
    // First, create the tables if they don't exist
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS product_usage_steps (
        step_id CHAR(36) PRIMARY KEY,
        product_id CHAR(36) NOT NULL,
        step_number INT NOT NULL,
        step_title VARCHAR(100) NOT NULL,
        step_description TEXT NOT NULL,
        step_image VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        UNIQUE KEY unique_product_step (product_id, step_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($createTableSQL);
    echo "<p>✅ Table 'product_usage_steps' created/verified successfully.</p>";
    
    // Get all active products
    $stmt = $pdo->query("SELECT product_id, name FROM products WHERE is_active = 1 LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "<p>❌ No active products found. Please add some products first.</p>";
        exit();
    }
    
    echo "<h3>Adding usage steps for products:</h3>";
    
    // Default usage steps that work for most supplement products
    $defaultSteps = [
        [
            'step_number' => 1,
            'step_title' => 'Mix with Water',
            'step_description' => 'Add 1 scoop (30g) to 200-250ml of cold water in a shaker bottle or glass.',
            'step_image' => 'assets/how-to-use/686238c5559f2_B - Complex 1.jpg'
        ],
        [
            'step_number' => 2,
            'step_title' => 'Shake Well',
            'step_description' => 'Shake vigorously for 30 seconds until the powder is completely dissolved and mixed.',
            'step_image' => 'assets/how-to-use/686238c555f2c_B - Complex 2.jpg'
        ],
        [
            'step_number' => 3,
            'step_title' => 'Consume Immediately',
            'step_description' => 'Drink the mixture immediately after preparation for optimal absorption and effectiveness.',
            'step_image' => 'assets/how-to-use/686238c556100_B - Complex 3.jpg'
        ],
        [
            'step_number' => 4,
            'step_title' => 'Best Time to Take',
            'step_description' => 'Take 30 minutes before workout or as directed by your healthcare professional.',
            'step_image' => 'assets/how-to-use/686238c5562b2_B - Complex 4.jpg'
        ]
    ];
    
    $insertSQL = "
        INSERT IGNORE INTO product_usage_steps 
        (step_id, product_id, step_number, step_title, step_description, step_image, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ";
    
    $insertStmt = $pdo->prepare($insertSQL);
    
    foreach ($products as $product) {
        echo "<h4>Product: " . htmlspecialchars($product['name']) . "</h4>";
        echo "<ul>";
        
        foreach ($defaultSteps as $step) {
            $stepId = 'step-' . uniqid() . '-' . $product['product_id'];
            
            $insertStmt->execute([
                $stepId,
                $product['product_id'],
                $step['step_number'],
                $step['step_title'],
                $step['step_description'],
                $step['step_image']
            ]);
            
            echo "<li>Step " . $step['step_number'] . ": " . htmlspecialchars($step['step_title']) . " ✅</li>";
        }
        
        echo "</ul>";
    }
    
    // Show summary
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM product_usage_steps");
    $count = $countStmt->fetch();
    
    echo "<h3>Summary:</h3>";
    echo "<p>✅ Total usage steps created: " . $count['total'] . "</p>";
    echo "<p>✅ Products with usage steps: " . count($products) . "</p>";
    
    echo "<h3>Test the Results:</h3>";
    echo "<p><a href='test-products.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Products with Usage Steps</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
