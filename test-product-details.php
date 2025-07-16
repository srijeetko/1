<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Get a sample product to test
$sql = "SELECT * FROM products LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    echo "<h2>No products found in database</h2>";
    echo "<p>Please add some products through the admin panel first.</p>";
    exit;
}

$product_id = $product['product_id'];

echo "<h1>Product Details Test</h1>";
echo "<h2>Testing Product: " . htmlspecialchars($product['name']) . "</h2>";

// Test products table fields
echo "<h3>Products Table Fields:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";

$productFields = [
    'product_id' => 'Product ID',
    'name' => 'Product Name',
    'description' => 'Description',
    'short_description' => 'Short Description',
    'long_description' => 'Long Description',
    'key_benefits' => 'Key Benefits',
    'how_to_use' => 'How to Use',
    'how_to_use_images' => 'How to Use Images',
    'ingredients' => 'Ingredients',
    'price' => 'Price',
    'category_id' => 'Category ID',
    'stock_quantity' => 'Stock Quantity',
    'is_active' => 'Is Active'
];

foreach ($productFields as $field => $label) {
    $value = $product[$field] ?? 'NULL';
    $status = !empty($value) ? '✅ Has Data' : '❌ Empty';
    $displayValue = is_string($value) && strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
    echo "<tr>";
    echo "<td><strong>$label</strong></td>";
    echo "<td>" . htmlspecialchars($displayValue) . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Test supplement_details table
echo "<h3>Supplement Details Table Fields:</h3>";
try {
    $supplementSql = "SELECT * FROM supplement_details WHERE product_id = :product_id";
    $supplementStmt = $pdo->prepare($supplementSql);
    $supplementStmt->bindParam(':product_id', $product_id);
    $supplementStmt->execute();
    $supplementData = $supplementStmt->fetch();

    if ($supplementData) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";

        $supplementFields = [
            'detail_id' => 'Detail ID',
            'product_id' => 'Product ID',
            'serving_size' => 'Serving Size',
            'servings_per_container' => 'Servings per Container',
            'calories' => 'Calories',
            'protein' => 'Protein',
            'carbs' => 'Carbohydrates',
            'fats' => 'Fats',
            'fiber' => 'Fiber',
            'sodium' => 'Sodium',
            'ingredients' => 'Ingredients',
            'directions' => 'Directions',
            'warnings' => 'Warnings',
            'weight_value' => 'Weight Value',
            'weight_unit' => 'Weight Unit'
        ];

        foreach ($supplementFields as $field => $label) {
            $value = $supplementData[$field] ?? 'NULL';
            $status = !empty($value) ? '✅ Has Data' : '❌ Empty';
            $displayValue = is_string($value) && strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
            echo "<tr>";
            echo "<td><strong>$label</strong></td>";
            echo "<td>" . htmlspecialchars($displayValue) . "</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No supplement details found for this product.</p>";
        echo "<p>Supplement details can be added through the admin panel.</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Error accessing supplement_details table: " . $e->getMessage() . "</p>";
    echo "<p>The supplement_details table might not exist or have different structure.</p>";
}

// Test category information
echo "<h3>Category Information:</h3>";
if ($product['category_id']) {
    try {
        $categorySql = "SELECT * FROM sub_category WHERE category_id = :category_id";
        $categoryStmt = $pdo->prepare($categorySql);
        $categoryStmt->bindParam(':category_id', $product['category_id']);
        $categoryStmt->execute();
        $category = $categoryStmt->fetch();

        if ($category) {
            echo "<p>✅ Category: " . htmlspecialchars($category['name']) . "</p>";
            if ($category['description']) {
                echo "<p>Description: " . htmlspecialchars($category['description']) . "</p>";
            }
        } else {
            echo "<p>❌ Category not found</p>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ Error accessing category: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ No category assigned to this product</p>";
}

// Test product variants
echo "<h3>Product Variants:</h3>";
try {
    $variantsSql = "SELECT * FROM product_variants WHERE product_id = :product_id";
    $variantsStmt = $pdo->prepare($variantsSql);
    $variantsStmt->bindParam(':product_id', $product_id);
    $variantsStmt->execute();
    $variants = $variantsStmt->fetchAll();

    if ($variants) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Size</th><th>Color</th><th>Price Modifier</th><th>Stock</th></tr>";
        foreach ($variants as $variant) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($variant['size'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($variant['color'] ?? '') . "</td>";
            echo "<td>₹" . number_format($variant['price_modifier'] ?? 0, 2) . "</td>";
            echo "<td>" . ($variant['stock'] ?? 0) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No variants found for this product</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Error accessing variants: " . $e->getMessage() . "</p>";
}

// Test product images
echo "<h3>Product Images:</h3>";
try {
    $imagesSql = "SELECT * FROM product_images WHERE product_id = :product_id";
    $imagesStmt = $pdo->prepare($imagesSql);
    $imagesStmt->bindParam(':product_id', $product_id);
    $imagesStmt->execute();
    $images = $imagesStmt->fetchAll();

    if ($images) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Image URL</th><th>Alt Text</th><th>Is Primary</th></tr>";
        foreach ($images as $image) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($image['image_url'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($image['alt_text'] ?? '') . "</td>";
            echo "<td>" . ($image['is_primary'] ? '✅ Primary' : '❌ Secondary') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No images found for this product</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Error accessing images: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><a href='product-detail.php?id=" . $product_id . "'>View this product's detail page</a></p>";
echo "<p><a href='admin/product-edit.php?id=" . $product_id . "'>Edit this product in admin panel</a></p>";
?>
