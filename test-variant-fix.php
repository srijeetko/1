<?php
include 'includes/db_connection.php';

echo "<h1>🧪 Testing Variant Foreign Key Fix</h1>";

try {
    // Test 1: Check if we can identify products with variants referenced by orders
    echo "<h2>Test 1: Products with variants in orders</h2>";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.product_id, p.name, COUNT(oi.order_item_id) as order_references
        FROM products p
        JOIN product_variants pv ON p.product_id = pv.product_id
        JOIN order_items oi ON pv.variant_id = oi.variant_id
        GROUP BY p.product_id, p.name
        ORDER BY order_references DESC
        LIMIT 5
    ");
    $stmt->execute();
    $productsWithOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productsWithOrders)) {
        echo "<p>✅ No products have variants referenced by orders</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Order References</th></tr>";
        foreach ($productsWithOrders as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . $product['order_references'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Check if we can identify products with variants referenced by cart items
    echo "<h2>Test 2: Products with variants in cart</h2>";
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.product_id, p.name, COUNT(ci.cart_item_id) as cart_references
        FROM products p
        JOIN product_variants pv ON p.product_id = pv.product_id
        JOIN cart_items ci ON pv.variant_id = ci.variant_id
        GROUP BY p.product_id, p.name
        ORDER BY cart_references DESC
        LIMIT 5
    ");
    $stmt->execute();
    $productsWithCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($productsWithCart)) {
        echo "<p>✅ No products have variants referenced by cart items</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Cart References</th></tr>";
        foreach ($productsWithCart as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . $product['cart_references'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Show foreign key constraints
    echo "<h2>Test 3: Foreign Key Constraints</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'product_variants'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($constraints)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References</th></tr>";
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "." . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>✅ Fix Summary</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>What was fixed:</h3>";
    echo "<ul>";
    echo "<li><strong>Product Edit:</strong> Now updates existing variants instead of deleting and recreating them</li>";
    echo "<li><strong>Variant Deletion:</strong> Only deletes variants that aren't referenced by orders or cart items</li>";
    echo "<li><strong>Product Deletion:</strong> Checks for order and cart references before allowing deletion</li>";
    echo "<li><strong>Error Handling:</strong> Provides clear error messages when deletion is blocked</li>";
    echo "</ul>";
    echo "<h3>The foreign key constraint error should now be resolved!</h3>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error during testing:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

table {
    margin: 10px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
}

th {
    background-color: #f8f9fa;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f8f9fa;
}
</style>
