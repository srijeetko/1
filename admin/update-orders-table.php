<?php
// Update checkout_orders table to include coupon_id column
session_start();
include '../includes/db_connection.php';

echo "<h1>Update Orders Table for Coupon Support</h1>";

try {
    // Check current checkout_orders table structure
    echo "<h2>Current Orders Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if coupon_id column exists
    $existingColumns = array_column($columns, 'Field');
    
    if (!in_array('coupon_id', $existingColumns)) {
        echo "<h2>Adding coupon_id Column:</h2>";
        
        // Add coupon_id column
        $pdo->exec("ALTER TABLE checkout_orders ADD COLUMN coupon_id CHAR(36) NULL AFTER shipping_method_id");
        echo "✅ Added coupon_id column<br>";
        
        // Add foreign key constraint
        try {
            $pdo->exec("ALTER TABLE checkout_orders ADD CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id)");
            echo "✅ Added foreign key constraint<br>";
        } catch (Exception $e) {
            echo "ℹ️ Foreign key constraint not added (may already exist or coupons table not ready): " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<h2>✅ coupon_id column already exists!</h2>";
    }
    
    // Show updated table structure
    echo "<h2>Updated Orders Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Orders table update completed successfully!</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error updating orders table:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
