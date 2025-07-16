<?php
require_once 'includes/db_connection.php';

echo "<h1>Fix Checkout Orders Table</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h2>🔧 Adding Missing Columns to checkout_orders Table</h2>";
    
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($existing_columns, 'Field');
    
    echo "<p class='info'>Current columns: " . implode(', ', $column_names) . "</p>";
    
    // Define missing columns to add
    $columns_to_add = [
        'address' => "ADD COLUMN address TEXT AFTER phone",
        'city' => "ADD COLUMN city VARCHAR(100) AFTER address", 
        'state' => "ADD COLUMN state VARCHAR(100) AFTER city",
        'pincode' => "ADD COLUMN pincode VARCHAR(10) AFTER state"
    ];
    
    $added_columns = [];
    $skipped_columns = [];
    
    foreach ($columns_to_add as $column_name => $alter_sql) {
        if (!in_array($column_name, $column_names)) {
            try {
                $pdo->exec("ALTER TABLE checkout_orders $alter_sql");
                echo "<p class='success'>✅ Added column: $column_name</p>";
                $added_columns[] = $column_name;
            } catch (Exception $e) {
                echo "<p class='error'>❌ Failed to add column $column_name: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Column $column_name already exists, skipping</p>";
            $skipped_columns[] = $column_name;
        }
    }
    
    echo "</div>";
    
    // Verify the fix
    echo "<div class='section'>";
    echo "<h2>🔍 Verification</h2>";
    
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $required_columns = ['order_id', 'order_number', 'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'total_amount', 'payment_method', 'payment_status', 'order_status'];
    
    foreach ($updated_columns as $column) {
        $is_required = in_array($column['Field'], $required_columns);
        $row_class = $is_required ? "style='background: #d4edda;'" : "";
        
        echo "<tr $row_class>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if all required columns exist now
    $current_column_names = array_column($updated_columns, 'Field');
    $missing_required = array_diff($required_columns, $current_column_names);
    
    if (empty($missing_required)) {
        echo "<p class='success'>✅ All required columns are now present!</p>";
    } else {
        echo "<p class='error'>❌ Still missing columns: " . implode(', ', $missing_required) . "</p>";
    }
    
    echo "</div>";
    
    // Test order processing compatibility
    echo "<div class='section'>";
    echo "<h2>🧪 Test Order Processing Compatibility</h2>";
    
    try {
        // Test if we can prepare the INSERT statement from process-order.php
        $test_stmt = $pdo->prepare("
            INSERT INTO checkout_orders (
                order_id, order_number, user_id, first_name, last_name, email, phone, 
                address, city, state, pincode, total_amount, payment_method, 
                order_status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
        ");
        
        echo "<p class='success'>✅ Order INSERT statement can be prepared successfully</p>";
        echo "<p class='info'>The order processing should now work correctly</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Order INSERT statement failed: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Summary and next steps
    echo "<div class='section'>";
    echo "<h2>📋 Summary</h2>";
    
    if (!empty($added_columns)) {
        echo "<p class='success'><strong>Successfully added columns:</strong> " . implode(', ', $added_columns) . "</p>";
    }
    
    if (!empty($skipped_columns)) {
        echo "<p class='warning'><strong>Columns already existed:</strong> " . implode(', ', $skipped_columns) . "</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the order processing by going to checkout</li>";
    echo "<li>Add products to cart and try to place an order</li>";
    echo "<li>Check if the order gets saved to the database</li>";
    echo "</ol>";
    
    echo "<h3>Quick Links:</h3>";
    echo "<a href='test-order-processing.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Order Processing</a>";
    echo "<a href='checkout.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Try Checkout</a>";
    echo "<a href='cart.php' style='padding: 10px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px; margin: 5px;'>View Cart</a>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
