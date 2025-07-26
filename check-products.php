<?php
require_once 'includes/db_connection.php';

echo "<h1>🔍 Check Products in Database</h1>";

try {
    $stmt = $pdo->prepare("SELECT product_id, name, price FROM products LIMIT 10");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p>❌ No products found in database!</p>";
    } else {
        echo "<p>✅ Found " . count($products) . " products:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Product ID</th><th>Name</th><th>Price</th>";
        echo "</tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>₹" . $product['price'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show the first product ID for use in recovery
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>For Recovery Script:</h3>";
        echo "<p><strong>Use this product_id:</strong> <code>" . $products[0]['product_id'] . "</code></p>";
        echo "<p><strong>Product name:</strong> " . $products[0]['name'] . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Also check the foreign key constraints
echo "<h2>🔗 Foreign Key Constraints</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'order_items' 
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute();
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($constraints)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Constraint Name</th><th>Column</th><th>References Table</th><th>References Column</th>";
        echo "</tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error checking constraints: " . $e->getMessage() . "</p>";
}

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
</style>
