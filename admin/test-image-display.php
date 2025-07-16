<?php
// Test script to verify image display functionality
session_start();
include '../includes/db_connection.php';

echo "<h1>Image Display Test</h1>";

// Test 1: Check assets directory
echo "<h2>1. Assets Directory Check</h2>";
$assetsDir = '../assets/';
if (is_dir($assetsDir)) {
    echo "✅ Assets directory exists: $assetsDir<br>";
    if (is_writable($assetsDir)) {
        echo "✅ Assets directory is writable<br>";
    } else {
        echo "❌ Assets directory is not writable<br>";
    }
} else {
    echo "❌ Assets directory does not exist: $assetsDir<br>";
    echo "Creating assets directory...<br>";
    if (mkdir($assetsDir, 0755, true)) {
        echo "✅ Assets directory created successfully<br>";
    } else {
        echo "❌ Failed to create assets directory<br>";
    }
}

// Test 2: List existing images in assets
echo "<h2>2. Existing Images in Assets</h2>";
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$imageFiles = [];

foreach ($imageExtensions as $ext) {
    $files = glob($assetsDir . "*.$ext");
    $imageFiles = array_merge($imageFiles, $files);
}

if (!empty($imageFiles)) {
    echo "Found " . count($imageFiles) . " image files:<br>";
    foreach (array_slice($imageFiles, 0, 10) as $file) {
        $filename = basename($file);
        $filesize = filesize($file);
        echo "- $filename (" . round($filesize/1024, 2) . " KB)<br>";
    }
} else {
    echo "No image files found in assets directory<br>";
}

// Test 3: Check product images in database
echo "<h2>3. Product Images in Database</h2>";
try {
    $stmt = $pdo->query("SELECT pi.*, p.name as product_name FROM product_images pi LEFT JOIN products p ON pi.product_id = p.product_id ORDER BY pi.is_primary DESC");
    $dbImages = $stmt->fetchAll();
    
    if (!empty($dbImages)) {
        echo "Found " . count($dbImages) . " product images in database:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Product</th><th>Image URL</th><th>Primary</th><th>File Exists</th><th>Preview</th></tr>";
        
        foreach ($dbImages as $img) {
            $fullPath = '../' . $img['image_url'];
            $fileExists = file_exists($fullPath);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($img['product_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($img['image_url'] ?? '') . "</td>";
            echo "<td>" . ($img['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($fileExists ? '✅' : '❌') . "</td>";
            echo "<td>";
            if ($fileExists) {
                echo "<img src='../" . htmlspecialchars($img['image_url']) . "' style='width: 50px; height: 50px; object-fit: cover;'>";
            } else {
                echo "No file";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No product images found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking database images: " . $e->getMessage() . "<br>";
}

// Test 4: Test image upload functionality
echo "<h2>4. Test Image Upload</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $file = $_FILES['test_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($ext, $allowedTypes) && in_array($mimeType, $allowedMimes)) {
            $newName = 'test_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target = $assetsDir . $newName;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                echo "✅ Test image uploaded successfully: $newName<br>";
                echo "<img src='../assets/$newName' style='width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd;'><br>";
                echo "<small>File path: assets/$newName</small><br>";
            } else {
                echo "❌ Failed to move uploaded file<br>";
            }
        } else {
            echo "❌ Invalid file type. Allowed: " . implode(', ', $allowedTypes) . "<br>";
        }
    } else {
        echo "❌ Upload error: " . $file['error'] . "<br>";
    }
}

// Test 5: Sample product with image display
echo "<h2>5. Sample Product Display Test</h2>";
try {
    $query = "
        SELECT p.*, 
               (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p 
        ORDER BY p.created_at DESC 
        LIMIT 3
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    if (!empty($products)) {
        echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
        foreach ($products as $product) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; width: 200px;'>";
            echo "<h4>" . htmlspecialchars($product['name'] ?? 'Unnamed Product') . "</h4>";
            
            $imageUrl = $product['image_url'] ?? '';
            $imagePath = '../' . $imageUrl;
            
            if (!empty($imageUrl) && file_exists($imagePath)) {
                echo "<img src='../" . htmlspecialchars($imageUrl) . "' style='width: 100%; height: 120px; object-fit: cover; border-radius: 4px;'>";
            } else {
                echo "<div style='width: 100%; height: 120px; background: #f8f9fa; border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center; border-radius: 4px;'>";
                echo "<span style='color: #6c757d; font-size: 0.8rem;'>No Image</span>";
                echo "</div>";
            }
            
            echo "<p style='margin: 10px 0; font-size: 0.9rem;'>Price: ₹" . number_format($product['price'] ?? 0, 2) . "</p>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "No products found to display<br>";
    }
} catch (Exception $e) {
    echo "❌ Error displaying products: " . $e->getMessage() . "<br>";
}
?>

<h2>Upload Test Image</h2>
<form method="POST" enctype="multipart/form-data" style="margin: 20px 0;">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Upload Test Image</button>
</form>

<h2>Navigation</h2>
<p>
    <a href="products.php">View Products Page</a> | 
    <a href="product-edit.php">Add New Product</a> | 
    <a href="debug-product-issues.php">Debug Script</a>
</p>

<style>
.product-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.no-image-placeholder {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #6c757d;
    border-radius: 4px;
}
</style>
