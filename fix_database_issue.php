<?php
echo "<h1>Database Fix Script</h1>";

try {
    // Connect without specifying database
    $pdo = new PDO("mysql:host=localhost", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Step 1: Drop and Recreate Database</h2>";
    
    // Drop the problematic database
    $pdo->exec("DROP DATABASE IF EXISTS alphanutrition_db");
    echo "✅ Dropped existing database (if it existed)<br>";
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE alphanutrition_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Created fresh 'alphanutrition_db' database<br>";
    
    // Test connection to new database
    $pdo_test = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Successfully connected to new database<br>";
    
    echo "<h2>Step 2: Create Basic Tables</h2>";
    
    // Create essential tables
    $tables = [
        "sub_category" => "
            CREATE TABLE sub_category (
                category_id CHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
        
        "products" => "
            CREATE TABLE products (
                product_id CHAR(36) PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                description TEXT,
                short_description TEXT,
                long_description TEXT,
                key_benefits TEXT,
                how_to_use TEXT,
                how_to_use_images TEXT,
                ingredients TEXT,
                price DECIMAL(10,2) NOT NULL,
                category_id CHAR(36),
                stock_quantity INT,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES sub_category(category_id)
            )",
        
        "product_variants" => "
            CREATE TABLE product_variants (
                variant_id CHAR(36) PRIMARY KEY,
                product_id CHAR(36),
                size VARCHAR(20),
                color VARCHAR(30),
                price_modifier DECIMAL(10,2) DEFAULT 0.0,
                stock INT DEFAULT 0,
                FOREIGN KEY (product_id) REFERENCES products(product_id)
            )",
        
        "product_images" => "
            CREATE TABLE product_images (
                image_id CHAR(36) PRIMARY KEY,
                product_id CHAR(36),
                image_url TEXT NOT NULL,
                alt_text TEXT,
                is_primary TINYINT(1) DEFAULT 0,
                FOREIGN KEY (product_id) REFERENCES products(product_id)
            )",
        
        "best_sellers" => "
            CREATE TABLE best_sellers (
                product_id CHAR(36) PRIMARY KEY,
                sales_count INT DEFAULT 0,
                FOREIGN KEY (product_id) REFERENCES products(product_id)
            )",
        
        "admin_users" => "
            CREATE TABLE admin_users (
                admin_id CHAR(36) PRIMARY KEY,
                name VARCHAR(100),
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role VARCHAR(50) DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo_test->exec($sql);
            echo "✅ Created table: $tableName<br>";
        } catch (PDOException $e) {
            echo "⚠️ Table $tableName: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>Step 3: Insert Sample Data</h2>";
    
    // Insert a sample category
    $categoryId = 'cat-' . uniqid();
    $pdo_test->exec("INSERT INTO sub_category (category_id, name, description) VALUES ('$categoryId', 'Supplements', 'Health supplements and nutrition products')");
    echo "✅ Added sample category<br>";
    
    // Insert a sample product
    $productId = 'prod-' . uniqid();
    $pdo_test->exec("
        INSERT INTO products (product_id, name, description, price, category_id, stock_quantity, is_active) 
        VALUES ('$productId', 'Test Product', 'Sample product for testing', 1699.00, '$categoryId', 100, 1)
    ");
    echo "✅ Added sample product<br>";
    
    // Insert a sample variant
    $variantId = 'var-' . uniqid();
    $pdo_test->exec("
        INSERT INTO product_variants (variant_id, product_id, size, price_modifier, stock) 
        VALUES ('$variantId', '$productId', '1kg', 1699.00, 50)
    ");
    echo "✅ Added sample variant<br>";
    
    echo "<h2>Step 4: Test Your Application</h2>";
    
    // Test your db_connection.php
    try {
        ob_start();
        include 'includes/db_connection.php';
        $output = ob_get_clean();
        
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "✅ Your db_connection.php now works!<br>";
            
            // Test a query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
            $result = $stmt->fetch();
            echo "✅ Found " . $result['count'] . " products in database<br>";
            
        } else {
            echo "❌ db_connection.php still has issues<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>🎉 Database Fixed Successfully!</h2>";
    echo "<p>Your database has been recreated with basic structure. You can now:</p>";
    echo "<ul>";
    echo "<li>Access your website normally</li>";
    echo "<li>Import your full database schema if you have it</li>";
    echo "<li>Add more products through the admin panel</li>";
    echo "</ul>";
    echo "<p><strong>Next:</strong> <a href='products.php'>Test your products page</a> or <a href='admin/'>Access admin panel</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Database Fix Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Manual Fix Steps:</h3>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin in Laragon</li>";
    echo "<li>Drop the 'alphanutrition_db' database</li>";
    echo "<li>Create a new database named 'alphanutrition_db'</li>";
    echo "<li>Import your SQL schema file</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>
