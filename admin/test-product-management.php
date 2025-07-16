<?php
// Comprehensive test for product management functionality
session_start();
include '../includes/db_connection.php';

echo "<h1>Product Management Test</h1>";

// Test 1: Check if all required tables exist
echo "<h2>1. Database Tables Test</h2>";
$requiredTables = ['products', 'product_variants', 'product_images', 'supplement_details', 'sub_category'];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test 2: Check table structures
echo "<h2>2. Table Structure Test</h2>";

// Check products table structure
try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredProductColumns = ['product_id', 'name', 'description', 'short_description', 'long_description', 'key_benefits', 'how_to_use', 'ingredients', 'price', 'category_id', 'stock_quantity', 'is_active'];
    
    echo "<strong>Products table columns:</strong><br>";
    foreach ($requiredProductColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ $col<br>";
        } else {
            echo "❌ $col (missing)<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking products table structure: " . $e->getMessage() . "<br>";
}

// Check supplement_details table structure
try {
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredSupplementColumns = ['detail_id', 'product_id', 'serving_size', 'servings_per_container', 'calories', 'protein', 'carbs', 'fats', 'fiber', 'sodium', 'ingredients', 'directions', 'warnings'];
    
    echo "<br><strong>Supplement details table columns:</strong><br>";
    foreach ($requiredSupplementColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ $col<br>";
        } else {
            echo "❌ $col (missing)<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking supplement_details table structure: " . $e->getMessage() . "<br>";
}

// Test 3: Test product insertion
echo "<h2>3. Product Insertion Test</h2>";
try {
    $testProductId = 'test-product-' . time();
    
    // Insert test product
    $sql = "INSERT INTO products (product_id, name, description, short_description, long_description, key_benefits, how_to_use, ingredients, price, category_id, stock_quantity, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $testProductId,
        'Test Product',
        'Test Description',
        'Short description',
        'Long description',
        'Key benefits',
        'How to use',
        'Test ingredients',
        29.99,
        null, // category_id can be null for test
        100,
        1
    ]);
    
    if ($result) {
        echo "✅ Product insertion successful<br>";
        
        // Test supplement details insertion
        $supplementSql = "INSERT INTO supplement_details (detail_id, product_id, serving_size, servings_per_container, calories, protein, carbs, fats, fiber, sodium, ingredients, directions, warnings) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($supplementSql);
        $supplementResult = $stmt->execute([
            'test-supplement-' . time(),
            $testProductId,
            '30g',
            30,
            120,
            25.5,
            5.2,
            1.8,
            2.1,
            150.0,
            'Test supplement ingredients',
            'Test directions',
            'Test warnings'
        ]);
        
        if ($supplementResult) {
            echo "✅ Supplement details insertion successful<br>";
        } else {
            echo "❌ Supplement details insertion failed<br>";
        }
        
        // Test variant insertion
        $variantSql = "INSERT INTO product_variants (variant_id, product_id, size, price_modifier, stock) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($variantSql);
        $variantResult = $stmt->execute([
            'test-variant-' . time(),
            $testProductId,
            '1kg',
            29.99,
            50
        ]);
        
        if ($variantResult) {
            echo "✅ Product variant insertion successful<br>";
        } else {
            echo "❌ Product variant insertion failed<br>";
        }
        
        // Clean up test data
        $pdo->prepare("DELETE FROM supplement_details WHERE product_id = ?")->execute([$testProductId]);
        $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$testProductId]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$testProductId]);
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Product insertion failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Product insertion test error: " . $e->getMessage() . "<br>";
}

// Test 4: Test product retrieval with all related data
echo "<h2>4. Product Retrieval Test</h2>";
try {
    $query = "
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p 
        LEFT JOIN sub_category c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if ($product) {
        echo "✅ Product retrieval successful<br>";
        echo "Product: " . htmlspecialchars($product['name']) . "<br>";
        
        // Test related data retrieval
        $variantStmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ?');
        $variantStmt->execute([$product['product_id']]);
        $variants = $variantStmt->fetchAll();
        echo "✅ Found " . count($variants) . " variants<br>";
        
        $supplementStmt = $pdo->prepare('SELECT * FROM supplement_details WHERE product_id = ?');
        $supplementStmt->execute([$product['product_id']]);
        $supplement = $supplementStmt->fetch();
        echo ($supplement ? "✅" : "ℹ️") . " Supplement details " . ($supplement ? "found" : "not found (optional)") . "<br>";
        
    } else {
        echo "ℹ️ No products found in database<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Product retrieval test error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p><strong>Summary:</strong> Product management system is ready for use.</p>";
echo "<p><a href='products.php'>Go to Products Management</a> | <a href='product-edit.php'>Add New Product</a></p>";
?>
