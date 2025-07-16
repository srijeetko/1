<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

echo "<h2>Product Display Test</h2>";

// Get a sample product to test
$sql = "SELECT * FROM products WHERE is_active = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ No Products Found</h4>";
    echo "<p style='color: #721c24; margin-bottom: 0;'>Please add some products through the admin panel first.</p>";
    echo "</div>";
    exit;
}

echo "<h3>Testing Product: " . htmlspecialchars($product['name']) . "</h3>";
echo "<p><strong>Product ID:</strong> " . htmlspecialchars($product['product_id']) . "</p>";

// Check which fields have data
$adminFields = [
    'name' => 'Product Name',
    'description' => 'Basic Description', 
    'short_description' => 'Short Description',
    'long_description' => 'Long Description',
    'key_benefits' => 'Key Benefits',
    'how_to_use' => 'How to Use',
    'ingredients' => 'Ingredients',
    'how_to_use_images' => 'How-to-Use Images',
    'price' => 'Price',
    'stock_quantity' => 'Stock Quantity'
];

echo "<h3>Admin Panel Fields Status:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 1rem 0;'>";
echo "<tr><th>Field</th><th>Status</th><th>Content Preview</th></tr>";

foreach ($adminFields as $fieldName => $fieldLabel) {
    $hasData = isset($product[$fieldName]) && !empty($product[$fieldName]);
    $status = $hasData ? "✅ Has Data" : "❌ Empty";
    $statusColor = $hasData ? "#28a745" : "#dc3545";
    
    $preview = '';
    if ($hasData) {
        $value = $product[$fieldName];
        if (is_numeric($value)) {
            $preview = $value;
        } else {
            $preview = htmlspecialchars(substr($value, 0, 100));
            if (strlen($value) > 100) {
                $preview .= '...';
            }
        }
    } else {
        $preview = '<em style="color: #6c757d;">(empty)</em>';
    }
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($fieldLabel) . "</strong><br><small style='color: #6c757d;'>($fieldName)</small></td>";
    echo "<td style='color: $statusColor;'>$status</td>";
    echo "<td>$preview</td>";
    echo "</tr>";
}
echo "</table>";

// Test the product detail page link
echo "<h3>Test Product Detail Page:</h3>";
echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>Product Detail Page URL:</strong></p>";
echo "<p><a href='product-detail.php?id=" . urlencode($product['product_id']) . "' target='_blank' style='color: #0c5460; text-decoration: none; font-weight: bold;'>product-detail.php?id=" . htmlspecialchars($product['product_id']) . "</a></p>";
echo "<p><small>Click the link above to test the product detail page display</small></p>";
echo "</div>";

// Debug mode link
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>Debug Mode (shows detailed field information):</strong></p>";
echo "<p><a href='product-detail.php?id=" . urlencode($product['product_id']) . "&debug=1' target='_blank' style='color: #856404; text-decoration: none; font-weight: bold;'>product-detail.php?id=" . htmlspecialchars($product['product_id']) . "&debug=1</a></p>";
echo "<p><small>Use debug mode to see exactly what data is being loaded for each field</small></p>";
echo "</div>";

// Recommendations
echo "<h3>Recommendations:</h3>";
$emptyFields = [];
foreach ($adminFields as $fieldName => $fieldLabel) {
    if (!isset($product[$fieldName]) || empty($product[$fieldName])) {
        if (!in_array($fieldName, ['how_to_use_images'])) { // how_to_use_images can be empty
            $emptyFields[] = $fieldLabel;
        }
    }
}

if (!empty($emptyFields)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #856404; margin-top: 0;'>⚠️ Missing Data</h4>";
    echo "<p style='color: #856404;'>The following fields are empty and won't be displayed on the product detail page:</p>";
    echo "<ul style='color: #856404;'>";
    foreach ($emptyFields as $field) {
        echo "<li>$field</li>";
    }
    echo "</ul>";
    echo "<p style='color: #856404; margin-bottom: 0;'><strong>Action:</strong> Go to your admin panel and edit this product to fill in the missing information.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ All Good!</h4>";
    echo "<p style='color: #155724; margin-bottom: 0;'>This product has data in all the main fields and should display properly on the product detail page.</p>";
    echo "</div>";
}

// Check supplement details
echo "<h3>Supplement Details Check:</h3>";
try {
    $supplementSql = "SELECT * FROM supplement_details WHERE product_id = :product_id";
    $supplementStmt = $pdo->prepare($supplementSql);
    $supplementStmt->bindParam(':product_id', $product['product_id']);
    $supplementStmt->execute();
    $supplementData = $supplementStmt->fetch();
    
    if ($supplementData) {
        echo "<p>✅ This product has supplement details (nutritional information)</p>";
        $nutritionalFields = ['serving_size', 'calories', 'protein', 'carbs', 'fats', 'fiber', 'sodium'];
        $hasNutritionalData = false;
        foreach ($nutritionalFields as $field) {
            if (!empty($supplementData[$field])) {
                $hasNutritionalData = true;
                break;
            }
        }
        echo "<p>" . ($hasNutritionalData ? "✅" : "⚠️") . " Nutritional information: " . ($hasNutritionalData ? "Available" : "Not filled in") . "</p>";
    } else {
        echo "<p>ℹ️ This product doesn't have supplement details (nutritional information)</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking supplement details: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr style='margin: 2rem 0;'>";
echo "<p><a href='check-product-fields.php'>← Check Database Structure</a> | <a href='add-product-fields.php'>Add Missing Fields</a></p>";
?>
