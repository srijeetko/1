<?php
// Script to add sample images to existing products for testing
session_start();
include '../includes/db_connection.php';

echo "<h1>Add Sample Images to Products</h1>";

// Check if we have products without images
try {
    $stmt = $pdo->query("
        SELECT p.product_id, p.name 
        FROM products p 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id 
        WHERE pi.product_id IS NULL 
        LIMIT 5
    ");
    $productsWithoutImages = $stmt->fetchAll();
    
    if (!empty($productsWithoutImages)) {
        echo "<h2>Products without images:</h2>";
        foreach ($productsWithoutImages as $product) {
            echo "- " . htmlspecialchars($product['name']) . " (ID: " . htmlspecialchars($product['product_id']) . ")<br>";
        }
        
        // Create placeholder images for these products
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_placeholders'])) {
            echo "<h2>Creating placeholder images...</h2>";
            
            foreach ($productsWithoutImages as $product) {
                // Create a simple placeholder image URL (you can replace with actual image files)
                $placeholderImages = [
                    'assets/placeholder-protein.jpg',
                    'assets/placeholder-gainer.jpg',
                    'assets/placeholder-supplement.jpg',
                    'assets/placeholder-vitamin.jpg',
                    'assets/placeholder-product.jpg'
                ];
                
                $randomImage = $placeholderImages[array_rand($placeholderImages)];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO product_images (image_id, product_id, image_url, is_primary) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        bin2hex(random_bytes(16)),
                        $product['product_id'],
                        $randomImage,
                        1 // Set as primary
                    ]);
                    echo "✅ Added placeholder image for: " . htmlspecialchars($product['name']) . "<br>";
                } catch (Exception $e) {
                    echo "❌ Error adding image for " . htmlspecialchars($product['name']) . ": " . $e->getMessage() . "<br>";
                }
            }
        }
    } else {
        echo "<p>✅ All products have images assigned.</p>";
    }
    
    // Show current product images
    echo "<h2>Current Product Images</h2>";
    $stmt = $pdo->query("
        SELECT p.name, pi.image_url, pi.is_primary 
        FROM products p 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id 
        ORDER BY p.name, pi.is_primary DESC
    ");
    $productImages = $stmt->fetchAll();
    
    if (!empty($productImages)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Product Name</th><th>Image URL</th><th>Primary</th><th>File Exists</th></tr>";
        
        foreach ($productImages as $img) {
            $fileExists = !empty($img['image_url']) && file_exists('../' . $img['image_url']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($img['name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($img['image_url'] ?? 'No image') . "</td>";
            echo "<td>" . ($img['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($fileExists ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Create some actual placeholder image files if they don't exist
echo "<h2>Create Placeholder Image Files</h2>";
$placeholderDir = '../assets/';
$placeholderFiles = [
    'placeholder-protein.jpg',
    'placeholder-gainer.jpg', 
    'placeholder-supplement.jpg',
    'placeholder-vitamin.jpg',
    'placeholder-product.jpg'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_files'])) {
    foreach ($placeholderFiles as $filename) {
        $filepath = $placeholderDir . $filename;
        if (!file_exists($filepath)) {
            // Create a simple colored rectangle as placeholder
            $width = 300;
            $height = 300;
            $image = imagecreate($width, $height);
            
            // Random colors for different placeholders
            $colors = [
                [255, 100, 100], // Red
                [100, 255, 100], // Green  
                [100, 100, 255], // Blue
                [255, 255, 100], // Yellow
                [255, 100, 255]  // Magenta
            ];
            
            $colorIndex = array_search($filename, $placeholderFiles) % count($colors);
            $color = $colors[$colorIndex];
            
            $bg_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
            $text_color = imagecolorallocate($image, 255, 255, 255);
            
            // Add text
            $text = strtoupper(str_replace(['placeholder-', '.jpg'], '', $filename));
            imagestring($image, 5, 50, 140, $text, $text_color);
            imagestring($image, 3, 80, 160, 'PLACEHOLDER', $text_color);
            
            if (imagejpeg($image, $filepath, 80)) {
                echo "✅ Created: $filename<br>";
            } else {
                echo "❌ Failed to create: $filename<br>";
            }
            imagedestroy($image);
        } else {
            echo "ℹ️ Already exists: $filename<br>";
        }
    }
}

?>

<h2>Actions</h2>
<form method="POST" style="margin: 20px 0;">
    <button type="submit" name="create_placeholders" style="margin-right: 10px;">
        Add Placeholder Images to Products
    </button>
    <button type="submit" name="create_files">
        Create Placeholder Image Files
    </button>
</form>

<h2>Navigation</h2>
<p>
    <a href="test-image-display.php">Test Image Display</a> | 
    <a href="products.php">View Products</a> | 
    <a href="product-edit.php">Add Product</a>
</p>
