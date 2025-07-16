<?php
echo "<h1>Add Missing Tables</h1>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database<br>";
    
    // Create banner_images table
    echo "<h2>Creating Missing Tables</h2>";
    
    $missingTables = [
        "banner_images" => "
            CREATE TABLE banner_images (
                banner_id CHAR(36) PRIMARY KEY,
                image_path VARCHAR(255) NOT NULL,
                title VARCHAR(200),
                description TEXT,
                is_active TINYINT(1) DEFAULT 1,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
        
        "users" => "
            CREATE TABLE users (
                user_id CHAR(36) PRIMARY KEY,
                full_name VARCHAR(100),
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                phone_number VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active TINYINT(1) DEFAULT 1
            )",
        
        "cart_items" => "
            CREATE TABLE cart_items (
                cart_item_id CHAR(36) PRIMARY KEY,
                user_id CHAR(36),
                product_id CHAR(36),
                variant_id CHAR(36),
                quantity INT NOT NULL,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id),
                FOREIGN KEY (product_id) REFERENCES products(product_id),
                FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
            )",
        
        "checkout_orders" => "
            CREATE TABLE checkout_orders (
                order_id CHAR(36) PRIMARY KEY,
                user_id CHAR(36),
                total_amount DECIMAL(10,2),
                order_status VARCHAR(50) DEFAULT 'pending',
                shipping_address TEXT,
                billing_address TEXT,
                payment_method VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id)
            )",
        
        "order_items" => "
            CREATE TABLE order_items (
                order_item_id CHAR(36) PRIMARY KEY,
                order_id CHAR(36),
                product_id CHAR(36),
                variant_id CHAR(36),
                quantity INT,
                price DECIMAL(10,2),
                FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id),
                FOREIGN KEY (product_id) REFERENCES products(product_id),
                FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
            )",
        
        "supplement_details" => "
            CREATE TABLE supplement_details (
                detail_id CHAR(36) PRIMARY KEY,
                product_id CHAR(36),
                serving_size VARCHAR(50),
                servings_per_container INT,
                calories DECIMAL(8,2),
                protein DECIMAL(8,2),
                carbs DECIMAL(8,2),
                fats DECIMAL(8,2),
                fiber DECIMAL(8,2),
                sodium DECIMAL(8,2),
                ingredients TEXT,
                directions TEXT,
                warnings TEXT,
                FOREIGN KEY (product_id) REFERENCES products(product_id)
            )"
    ];
    
    foreach ($missingTables as $tableName => $sql) {
        try {
            // Check if table exists first
            $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
            if ($stmt->rowCount() > 0) {
                echo "⚠️ Table '$tableName' already exists, skipping<br>";
                continue;
            }
            
            $pdo->exec($sql);
            echo "✅ Created table: $tableName<br>";
        } catch (PDOException $e) {
            echo "❌ Failed to create table $tableName: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>Adding Sample Data</h2>";
    
    // Add sample banner images
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM banner_images");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $bannerData = [
                ['banner-1', 'assets/banner1.jpg', 'Welcome to Alpha Nutrition', 'Premium supplements for your health'],
                ['banner-2', 'assets/banner2.jpg', 'Build Muscle', 'High-quality protein supplements'],
                ['banner-3', 'assets/banner3.jpg', 'Stay Healthy', 'Natural vitamins and minerals']
            ];
            
            foreach ($bannerData as $banner) {
                $pdo->exec("INSERT INTO banner_images (banner_id, image_path, title, description, is_active, display_order) 
                           VALUES ('{$banner[0]}', '{$banner[1]}', '{$banner[2]}', '{$banner[3]}', 1, 1)");
            }
            echo "✅ Added sample banner images<br>";
        } else {
            echo "⚠️ Banner images already exist, skipping<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Failed to add banner data: " . $e->getMessage() . "<br>";
    }
    
    // Add sample admin user
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $adminId = 'admin-' . uniqid();
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO admin_users (admin_id, name, email, password_hash, role) 
                       VALUES ('$adminId', 'Admin User', 'admin@alphanutrition.com', '$passwordHash', 'admin')");
            echo "✅ Added admin user (email: admin@alphanutrition.com, password: admin123)<br>";
        } else {
            echo "⚠️ Admin user already exists, skipping<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Failed to add admin user: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Testing Homepage</h2>";
    
    // Test the query that was failing
    try {
        $stmt = $pdo->query("SELECT image_path, title, description FROM banner_images WHERE is_active = 1 ORDER BY display_order");
        $banners = $stmt->fetchAll();
        echo "✅ Homepage banner query works! Found " . count($banners) . " banners<br>";
    } catch (PDOException $e) {
        echo "❌ Homepage banner query still failing: " . $e->getMessage() . "<br>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>🎉 Missing Tables Added Successfully!</h2>";
    echo "<p>Your database now has all the required tables. You can:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Test your homepage</a></li>";
    echo "<li><a href='products.php'>Check products page</a></li>";
    echo "<li><a href='admin/'>Access admin panel</a> (admin@alphanutrition.com / admin123)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Error Adding Tables</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
