<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Get a sample product to test the layout
$sql = "SELECT * FROM products WHERE is_active = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    echo "<h2>❌ No Products Found</h2>";
    echo "<p>Please add some products through the admin panel first.</p>";
    echo "<p><a href='add-product-fields.php'>Add Missing Database Fields</a></p>";
    exit;
}

echo "<h2>✅ Zig-Zag Layout Test</h2>";
echo "<p><strong>Testing Product:</strong> " . htmlspecialchars($product['name']) . "</p>";

// Check which fields have data
$fieldsWithData = [];
$fieldsToCheck = [
    'short_description' => 'Short Description',
    'long_description' => 'Long Description',
    'key_benefits' => 'Key Benefits',
    'ingredients' => 'Ingredients',
    'how_to_use_images' => 'How-to-Use Images'
];

foreach ($fieldsToCheck as $field => $label) {
    if (!empty($product[$field])) {
        $fieldsWithData[] = $label;
    }
}

echo "<h3>Fields with Data (will appear in zig-zag layout):</h3>";
if (!empty($fieldsWithData)) {
    echo "<ul>";
    foreach ($fieldsWithData as $field) {
        echo "<li>✅ $field</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No extended product information found. Please edit the product in admin panel to add:</p>";
    echo "<ul>";
    foreach ($fieldsToCheck as $field => $label) {
        echo "<li>$label</li>";
    }
    echo "</ul>";
}

// Check supplement details
try {
    $supplementSql = "SELECT * FROM supplement_details WHERE product_id = :product_id";
    $supplementStmt = $pdo->prepare($supplementSql);
    $supplementStmt->bindParam(':product_id', $product['product_id']);
    $supplementStmt->execute();
    $supplementData = $supplementStmt->fetch();
    
    if ($supplementData) {
        $nutritionalFields = ['serving_size', 'calories', 'protein', 'carbs', 'fats', 'fiber', 'sodium'];
        $hasNutritionalData = false;
        foreach ($nutritionalFields as $field) {
            if (!empty($supplementData[$field])) {
                $hasNutritionalData = true;
                break;
            }
        }
        
        if ($hasNutritionalData) {
            echo "<p>✅ <strong>Nutritional Information:</strong> Available (will show in full-width nutrition section)</p>";
        } else {
            echo "<p>⚠️ <strong>Nutritional Information:</strong> Table exists but no data filled in</p>";
        }
    } else {
        echo "<p>ℹ️ <strong>Nutritional Information:</strong> Not available for this product</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking nutritional information: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr style='margin: 2rem 0;'>";

echo "<h3>🎯 Enhanced Zig-Zag Layout Features:</h3>";
echo "<div style='background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='margin-top: 0; color: #495057;'>✨ New Improvements:</h4>";
echo "<ul>";
echo "<li><strong>Better Spacing:</strong> 6rem margin between sections for proper visual separation</li>";
echo "<li><strong>Image Containers:</strong> Each section now has an adjacent image container (300px × 300px)</li>";
echo "<li><strong>Smart Image Display:</strong> Uses product images or fallback icons</li>";
echo "<li><strong>Section Labels:</strong> Each image container shows what section it represents</li>";
echo "<li><strong>Responsive Design:</strong> Images stack properly on mobile devices</li>";
echo "</ul>";
echo "<h4 style='color: #495057;'>📋 Perfect Zig-Zag Pattern:</h4>";
echo "<ol>";
echo "<li><strong>Short Description</strong> - Left side with product image (Yellow highlight) ⬅️</li>";
echo "<li><strong>Long Description</strong> - Right side with image container (Blue theme) ➡️</li>";
echo "<li><strong>Key Benefits</strong> - Left side with image container (Green theme) ⬅️</li>";
echo "<li><strong>How-to-Use Images</strong> - Right side with image gallery (Gray theme) ➡️</li>";
echo "<li><strong>Ingredients</strong> - Left side with product image (Light green theme) ⬅️</li>";
echo "<li><strong>Nutritional Information</strong> - Full width with image (Orange theme) [if available]</li>";
echo "<li><strong>Warnings</strong> - Full width with image (Red theme) [if available]</li>";
echo "</ol>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 6px; margin-top: 1rem;'>";
echo "<p style='margin: 0; color: #155724;'><strong>✅ Perfect Zig-Zag Fixed:</strong></p>";
echo "<ul style='margin: 0.5rem 0 0 0; color: #155724;'>";
echo "<li>Separate sections for short and long descriptions</li>";
echo "<li>Perfect alternating pattern: Left → Right → Left → Right → Left</li>";
echo "<li>No duplicate CSS classes causing alignment issues</li>";
echo "<li>Removed duplicate Key Benefits preview section</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<h3>🔗 Test Links:</h3>";
echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>View Product with New Zig-Zag Layout:</strong></p>";
echo "<p><a href='product-detail.php?id=" . urlencode($product['product_id']) . "' target='_blank' style='color: #0c5460; text-decoration: none; font-weight: bold; font-size: 1.1rem;'>🔗 Open Product Detail Page</a></p>";
echo "<p><small>This will show the new zig-zag layout instead of the old tabbed layout</small></p>";
echo "</div>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>Debug Mode (shows field data info):</strong></p>";
echo "<p><a href='product-detail.php?id=" . urlencode($product['product_id']) . "&debug=1' target='_blank' style='color: #856404; text-decoration: none; font-weight: bold;'>🔍 Debug Mode</a></p>";
echo "<p><small>Use this to see exactly what data is loaded for each field</small></p>";
echo "</div>";

if (empty($fieldsWithData)) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>⚠️ Action Required</h4>";
    echo "<p style='color: #721c24;'>To see the zig-zag layout in action, you need to:</p>";
    echo "<ol style='color: #721c24;'>";
    echo "<li>Go to your <strong>Admin Panel</strong></li>";
    echo "<li>Edit the product: <strong>" . htmlspecialchars($product['name']) . "</strong></li>";
    echo "<li>Fill in the missing fields (short description, long description, key benefits, how to use, ingredients)</li>";
    echo "<li>Save the product</li>";
    echo "<li>Return to the product detail page to see the zig-zag layout</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr style='margin: 2rem 0;'>";
echo "<p><a href='test-product-display.php'>← Back to Product Display Test</a> | <a href='check-product-fields.php'>Check Database Fields</a></p>";
?>
