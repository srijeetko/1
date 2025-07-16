<?php
echo "<h1>Fix Database Structure</h1>";
echo "<p>This will add missing columns to match your code requirements.</p>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database<br>";
    
    echo "<h2>Fixing Missing Columns</h2>";
    
    // Fix 1: Add parent_id to sub_category table
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM sub_category LIKE 'parent_id'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE sub_category ADD COLUMN parent_id CHAR(36) NULL AFTER category_id");
            echo "✅ Added parent_id column to sub_category table<br>";
        } else {
            echo "⚠️ parent_id column already exists in sub_category<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error adding parent_id to sub_category: " . $e->getMessage() . "<br>";
    }
    
    // Fix 2: Check and fix banner_images table structure
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM banner_images");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'banner_id' => 'CHAR(36) PRIMARY KEY',
            'image_path' => 'VARCHAR(255) NOT NULL',
            'title' => 'VARCHAR(200)',
            'description' => 'TEXT',
            'is_active' => 'TINYINT(1) DEFAULT 1',
            'display_order' => 'INT DEFAULT 0',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $columnName => $definition) {
            if (!in_array($columnName, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE banner_images ADD COLUMN $columnName $definition");
                    echo "✅ Added $columnName column to banner_images<br>";
                } catch (PDOException $e) {
                    echo "⚠️ Could not add $columnName to banner_images: " . $e->getMessage() . "<br>";
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Error checking banner_images structure: " . $e->getMessage() . "<br>";
    }
    
    // Fix 3: Update banner_images data to have proper status values
    try {
        $pdo->exec("UPDATE banner_images SET is_active = 1 WHERE is_active IS NULL OR is_active = ''");
        echo "✅ Fixed banner_images status values<br>";
    } catch (PDOException $e) {
        echo "⚠️ Could not update banner status: " . $e->getMessage() . "<br>";
    }
    
    // Fix 4: Check products table structure
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredProductColumns = [
            'short_description' => 'TEXT',
            'long_description' => 'TEXT', 
            'key_benefits' => 'TEXT',
            'how_to_use' => 'TEXT',
            'how_to_use_images' => 'TEXT',
            'ingredients' => 'TEXT'
        ];
        
        foreach ($requiredProductColumns as $columnName => $definition) {
            if (!in_array($columnName, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE products ADD COLUMN $columnName $definition");
                    echo "✅ Added $columnName column to products<br>";
                } catch (PDOException $e) {
                    echo "⚠️ Could not add $columnName to products: " . $e->getMessage() . "<br>";
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Error checking products structure: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Testing Fixed Structure</h2>";
    
    // Test the queries that were failing
    $tests = [
        "sub_category with parent_id" => "SELECT * FROM sub_category WHERE parent_id IS NULL LIMIT 1",
        "categories join" => "SELECT c.*, p.name as parent_name FROM sub_category c LEFT JOIN sub_category p ON c.parent_id = p.category_id LIMIT 1",
        "banner_images status" => "SELECT * FROM banner_images WHERE is_active = 1 LIMIT 1"
    ];
    
    foreach ($tests as $testName => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "✅ Test '$testName': PASSED<br>";
        } catch (PDOException $e) {
            echo "❌ Test '$testName': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>Sample Data Check</h2>";
    
    // Check if we have basic data
    $dataChecks = [
        "categories" => "SELECT COUNT(*) as count FROM sub_category",
        "products" => "SELECT COUNT(*) as count FROM products", 
        "banners" => "SELECT COUNT(*) as count FROM banner_images"
    ];
    
    foreach ($dataChecks as $tableName => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "✅ $tableName: " . $result['count'] . " records<br>";
            
            if ($result['count'] == 0 && $tableName == 'categories') {
                // Add sample category
                $categoryId = 'cat-' . uniqid();
                $pdo->exec("INSERT INTO sub_category (category_id, name, description) VALUES ('$categoryId', 'Supplements', 'Health supplements and nutrition products')");
                echo "✅ Added sample category<br>";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error checking $tableName: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>🎉 Database Structure Fixed!</h2>";
    echo "<p>Your database structure has been updated to match the code requirements. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/'>Test admin panel</a></li>";
    echo "<li><a href='admin/product-edit.php'>Test product editing</a></li>";
    echo "<li><a href='admin/categories.php'>Test categories page</a></li>";
    echo "<li><a href='admin/banner-images.php'>Test banner management</a></li>";
    echo "<li><a href='index.php'>Test homepage</a></li>";
    echo "</ul>";
    echo "<p><strong>Next:</strong> Once everything works, we can fix the pricing issue!</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Database Connection Failed</h2>";
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
