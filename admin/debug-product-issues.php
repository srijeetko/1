<?php
// Debug script to identify product storage and display issues
session_start();
include '../includes/db_connection.php';

echo "<h1>Product Issues Debug Script</h1>";

// Test 1: Check if products exist in database
echo "<h2>1. Products in Database</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "✅ Total products in database: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT product_id, name, price, category_id, stock_quantity, is_active FROM products LIMIT 5");
        $products = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Category ID</th><th>Stock</th><th>Active</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($product['name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($product['price'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($product['category_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($product['stock_quantity'] ?? '') . "</td>";
            echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking products: " . $e->getMessage() . "<br>";
}

// Test 2: Check product images
echo "<h2>2. Product Images</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_images");
    $count = $stmt->fetch()['count'];
    echo "✅ Total product images in database: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT pi.*, p.name as product_name FROM product_images pi LEFT JOIN products p ON pi.product_id = p.product_id LIMIT 5");
        $images = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Image ID</th><th>Product</th><th>Image URL</th><th>Primary</th><th>File Exists</th></tr>";
        foreach ($images as $image) {
            $fileExists = file_exists('../' . $image['image_url']) ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($image['image_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($image['product_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($image['image_url'] ?? '') . "</td>";
            echo "<td>" . ($image['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "<td>$fileExists</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking product images: " . $e->getMessage() . "<br>";
}

// Test 3: Check product variants
echo "<h2>3. Product Variants</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_variants");
    $count = $stmt->fetch()['count'];
    echo "✅ Total product variants in database: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT pv.*, p.name as product_name FROM product_variants pv LEFT JOIN products p ON pv.product_id = p.product_id LIMIT 5");
        $variants = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Variant ID</th><th>Product</th><th>Size</th><th>Price Modifier</th><th>Stock</th></tr>";
        foreach ($variants as $variant) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($variant['variant_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($variant['product_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($variant['size'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($variant['price_modifier'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($variant['stock'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking product variants: " . $e->getMessage() . "<br>";
}

// Test 4: Check supplement details
echo "<h2>4. Supplement Details</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM supplement_details");
    $count = $stmt->fetch()['count'];
    echo "✅ Total supplement details in database: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT sd.*, p.name as product_name FROM supplement_details sd LEFT JOIN products p ON sd.product_id = p.product_id LIMIT 3");
        $supplements = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Product</th><th>Serving Size</th><th>Calories</th><th>Protein</th><th>Carbs</th></tr>";
        foreach ($supplements as $supp) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($supp['product_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($supp['serving_size'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($supp['calories'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($supp['protein'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($supp['carbs'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking supplement details: " . $e->getMessage() . "<br>";
}

// Test 5: Test the products query used in admin panel
echo "<h2>5. Admin Panel Query Test</h2>";
try {
    $query = "
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p
        LEFT JOIN sub_category c ON p.category_id = c.category_id
        ORDER BY p.created_at DESC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "✅ Admin panel query executed successfully<br>";
    echo "Products returned: " . count($products) . "<br>";
    
    if (!empty($products)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Name</th><th>Price</th><th>Category</th><th>Image URL</th><th>Stock</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['name'] ?? '') . "</td>";
            echo "<td>₹" . htmlspecialchars($product['price'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($product['category_name'] ?? 'No Category') . "</td>";
            echo "<td>" . htmlspecialchars($product['image_url'] ?? 'No Image') . "</td>";
            echo "<td>" . htmlspecialchars($product['stock_quantity'] ?? '0') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error with admin panel query: " . $e->getMessage() . "<br>";
}

// Test 6: Check file upload directory
echo "<h2>6. File Upload Directory Check</h2>";
$uploadDir = '../assets/';
if (is_dir($uploadDir)) {
    echo "✅ Upload directory exists: $uploadDir<br>";
    if (is_writable($uploadDir)) {
        echo "✅ Upload directory is writable<br>";
    } else {
        echo "❌ Upload directory is not writable<br>";
    }
    
    $files = glob($uploadDir . '*');
    echo "Files in upload directory: " . count($files) . "<br>";
    
    if (count($files) > 0) {
        echo "Recent files:<br>";
        $recentFiles = array_slice($files, -5);
        foreach ($recentFiles as $file) {
            echo "- " . basename($file) . "<br>";
        }
    }
} else {
    echo "❌ Upload directory does not exist: $uploadDir<br>";
}

echo "<h2>Debug Complete</h2>";
echo "<p><a href='products.php'>Go to Products Page</a> | <a href='product-edit.php'>Add New Product</a></p>";
?>
