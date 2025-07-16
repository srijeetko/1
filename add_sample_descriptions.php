<?php
include 'includes/db_connection.php';

echo "<h1>Add Sample Product Descriptions</h1>";

try {
    // Get all products
    $stmt = $pdo->query("SELECT product_id, name FROM products LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "<p>No products found in database.</p>";
        exit;
    }
    
    // Sample descriptions for different types of products
    $sampleDescriptions = [
        'short_description' => 'Premium quality supplement designed for optimal health and wellness. This high-quality supplement is formulated with natural ingredients to support your health goals.',
        'long_description' => 'Our premium supplement combines the finest natural ingredients with advanced scientific research to deliver exceptional results. Each serving is carefully crafted to provide optimal nutrition and support for your active lifestyle. Whether you\'re an athlete, fitness enthusiast, or simply someone who values their health, this product is designed to help you achieve your wellness goals.',
        'key_benefits' => "• Supports overall health and wellness\n• Made with natural ingredients\n• Science-backed formulation\n• Easy to use and digest\n• No artificial preservatives",
        'how_to_use' => 'Take 1-2 servings daily with water, preferably with meals. For best results, use consistently as part of a balanced diet and exercise program.',
        'ingredients' => 'Natural protein blend, vitamins, minerals, natural flavoring, and other carefully selected ingredients. See product label for complete ingredient list.'
    ];
    
    if (isset($_POST['add_descriptions'])) {
        $updated = 0;
        foreach ($products as $product) {
            $stmt = $pdo->prepare("
                UPDATE products SET
                    short_description = ?,
                    long_description = ?,
                    key_benefits = ?,
                    how_to_use = ?,
                    ingredients = ?
                WHERE product_id = ?
            ");

            $result = $stmt->execute([
                $sampleDescriptions['short_description'],
                $sampleDescriptions['long_description'],
                $sampleDescriptions['key_benefits'],
                $sampleDescriptions['how_to_use'],
                $sampleDescriptions['ingredients'],
                $product['product_id']
            ]);
            
            if ($result) {
                $updated++;
            }
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h2>✅ Success!</h2>";
        echo "<p>Updated descriptions for $updated products.</p>";
        echo "</div>";
    }
    
    echo "<h2>Current Products:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='padding: 8px; background: #f5f5f5;'>Product Name</th><th style='padding: 8px; background: #f5f5f5;'>Has Description?</th><th style='padding: 8px; background: #f5f5f5;'>Actions</th></tr>";
    
    foreach ($products as $product) {
        // Check if product has descriptions
        $stmt = $pdo->prepare("SELECT short_description, long_description, key_benefits, how_to_use, ingredients FROM products WHERE product_id = ?");
        $stmt->execute([$product['product_id']]);
        $details = $stmt->fetch();

        $hasData = !empty($details['short_description']) || !empty($details['long_description']);
        $status = $hasData ? '✅ Yes' : '❌ No';
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td style='padding: 8px; text-align: center;'>$status</td>";
        echo "<td style='padding: 8px;'>";
        echo "<a href='product-detail.php?id=" . urlencode($product['product_id']) . "&debug=1' target='_blank' style='margin-right: 10px;'>View Detail</a>";
        echo "<a href='check_product_data.php?check=" . urlencode($product['product_id']) . "' target='_blank'>Check Data</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>Add Sample Descriptions</h2>";
    echo "<p>This will add sample description data to all products so you can test the display functionality.</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='add_descriptions' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Add Sample Descriptions to All Products</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<h3>Sample Data Preview:</h3>";
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 5px;'>";
    foreach ($sampleDescriptions as $field => $value) {
        echo "<p><strong>" . ucfirst(str_replace('_', ' ', $field)) . ":</strong><br>";
        echo "<em>" . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . "</em></p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { border: 1px solid #ddd; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
