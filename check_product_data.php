<?php
include 'includes/db_connection.php';

echo "<h1>Product Data Check</h1>";

try {
    // Get all products
    $stmt = $pdo->query("SELECT product_id, name FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "<p>No products found in database.</p>";
        exit;
    }
    
    echo "<h2>Available Products:</h2>";
    foreach ($products as $prod) {
        echo "<p><a href='?check=" . urlencode($prod['product_id']) . "'>" . htmlspecialchars($prod['name']) . "</a></p>";
    }
    
    if (isset($_GET['check'])) {
        $productId = $_GET['check'];
        
        echo "<h2>Product Details for: " . htmlspecialchars($productId) . "</h2>";
        
        // Get full product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th style='background: #f5f5f5; padding: 8px;'>Field</th><th style='background: #f5f5f5; padding: 8px;'>Value</th><th style='background: #f5f5f5; padding: 8px;'>Has Data?</th></tr>";
            
            $importantFields = [
                'name' => 'Product Name',
                'description' => 'Description',
                'short_description' => 'Short Description', 
                'long_description' => 'Long Description',
                'key_benefits' => 'Key Benefits',
                'how_to_use' => 'How to Use',
                'ingredients' => 'Ingredients',
                'price' => 'Price',
                'category_id' => 'Category ID'
            ];
            
            foreach ($importantFields as $field => $label) {
                $value = $product[$field] ?? 'NULL';
                $hasData = !empty($value) ? '✅ Yes' : '❌ No';
                $displayValue = !empty($value) ? htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') : '<em>Empty</em>';
                
                echo "<tr>";
                echo "<td style='padding: 8px; font-weight: bold;'>$label</td>";
                echo "<td style='padding: 8px;'>$displayValue</td>";
                echo "<td style='padding: 8px; text-align: center;'>$hasData</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>Raw Data (JSON):</h3>";
            echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto;'>";
            echo json_encode($product, JSON_PRETTY_PRINT);
            echo "</pre>";
            
            echo "<p><a href='product-detail.php?id=" . urlencode($productId) . "' target='_blank'>View Product Detail Page</a></p>";
            
        } else {
            echo "<p>Product not found.</p>";
        }
    }
    
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
