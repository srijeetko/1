<?php
// Simple test script to verify admin panel functionality
session_start();
include '../includes/db_connection.php';

echo "<h1>Admin Panel Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    if ($pdo) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Products Query
echo "<h2>2. Products Query Test</h2>";
try {
    $query = "
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p 
        LEFT JOIN sub_category c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    echo "✅ Products query successful. Found " . count($products) . " products<br>";
    
    if (!empty($products)) {
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li>" . htmlspecialchars($product['name']) . " (Category: " . htmlspecialchars($product['category_name'] ?? 'None') . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Products query error: " . $e->getMessage() . "<br>";
}

// Test 3: Categories Query
echo "<h2>3. Categories Query Test</h2>";
try {
    $categories = $pdo->query('SELECT * FROM sub_category ORDER BY name LIMIT 5')->fetchAll();
    echo "✅ Categories query successful. Found " . count($categories) . " categories<br>";
    
    if (!empty($categories)) {
        echo "<ul>";
        foreach ($categories as $category) {
            echo "<li>" . htmlspecialchars($category['name']) . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Categories query error: " . $e->getMessage() . "<br>";
}

// Test 4: Banner Images Query
echo "<h2>4. Banner Images Query Test</h2>";
try {
    $images = $pdo->query('SELECT * FROM banner_images ORDER BY display_order ASC LIMIT 5')->fetchAll();
    echo "✅ Banner images query successful. Found " . count($images) . " images<br>";
    
    if (!empty($images)) {
        echo "<ul>";
        foreach ($images as $image) {
            echo "<li>" . htmlspecialchars($image['title']) . " (" . htmlspecialchars($image['status']) . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Banner images query error: " . $e->getMessage() . "<br>";
}

// Test 5: Admin Users Query
echo "<h2>5. Admin Users Query Test</h2>";
try {
    $admins = $pdo->query('SELECT admin_id, name, email FROM admin_users LIMIT 5')->fetchAll();
    echo "✅ Admin users query successful. Found " . count($admins) . " admin users<br>";
    
    if (!empty($admins)) {
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>" . htmlspecialchars($admin['name']) . " (" . htmlspecialchars($admin['email']) . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Admin users query error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='index.php'>Go to Admin Dashboard</a></p>";
?>
