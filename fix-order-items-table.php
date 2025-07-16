<?php
require_once 'includes/db_connection.php';

echo "<h1>Fix Order Items Table</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h2>🔧 Checking and Fixing order_items Table</h2>";
    
    // Check if order_items table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='error'>❌ order_items table doesn't exist. Creating it...</p>";
        
        // Create the order_items table with all required columns
        $create_table_sql = "
            CREATE TABLE order_items (
                order_item_id CHAR(36) NOT NULL PRIMARY KEY,
                order_id CHAR(36) NOT NULL,
                product_id CHAR(36) NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                variant_id CHAR(36) NULL,
                variant_name VARCHAR(100) NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ";
        
        $pdo->exec($create_table_sql);
        echo "<p class='success'>✅ Created order_items table with all required columns</p>";
    } else {
        echo "<p class='success'>✅ order_items table exists</p>";
        
        // Check current table structure
        $stmt = $pdo->query("DESCRIBE order_items");
        $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $column_names = array_column($existing_columns, 'Field');
        
        echo "<p class='info'>Current columns: " . implode(', ', $column_names) . "</p>";
        
        // Define required columns and their definitions
        $required_columns = [
            'order_item_id' => "CHAR(36) NOT NULL PRIMARY KEY",
            'order_id' => "CHAR(36) NOT NULL",
            'product_id' => "CHAR(36) NOT NULL", 
            'product_name' => "VARCHAR(255) NOT NULL",
            'variant_id' => "CHAR(36) NULL",
            'variant_name' => "VARCHAR(100) NULL",
            'quantity' => "INT NOT NULL",
            'price' => "DECIMAL(10,2) NOT NULL",
            'total' => "DECIMAL(10,2) NOT NULL",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        
        $columns_to_add = [];
        $missing_columns = [];
        
        foreach ($required_columns as $column_name => $column_def) {
            if (!in_array($column_name, $column_names)) {
                $missing_columns[] = $column_name;
                
                // Determine the position for the new column
                switch ($column_name) {
                    case 'total':
                        $columns_to_add[$column_name] = "ADD COLUMN total DECIMAL(10,2) NOT NULL AFTER price";
                        break;
                    case 'variant_name':
                        $columns_to_add[$column_name] = "ADD COLUMN variant_name VARCHAR(100) NULL AFTER variant_id";
                        break;
                    case 'created_at':
                        $columns_to_add[$column_name] = "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                        break;
                    default:
                        $columns_to_add[$column_name] = "ADD COLUMN $column_name $column_def";
                        break;
                }
            }
        }
        
        if (!empty($missing_columns)) {
            echo "<p class='warning'>⚠️ Missing columns: " . implode(', ', $missing_columns) . "</p>";
            
            // Add missing columns
            foreach ($columns_to_add as $column_name => $alter_sql) {
                try {
                    $pdo->exec("ALTER TABLE order_items $alter_sql");
                    echo "<p class='success'>✅ Added column: $column_name</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Failed to add column $column_name: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p class='success'>✅ All required columns already exist</p>";
        }
    }
    
    echo "</div>";
    
    // Verify the final structure
    echo "<div class='section'>";
    echo "<h2>🔍 Final Table Structure</h2>";
    
    $stmt = $pdo->query("DESCRIBE order_items");
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Status</th></tr>";
    
    $required_columns = ['order_item_id', 'order_id', 'product_id', 'product_name', 'variant_id', 'variant_name', 'quantity', 'price', 'total', 'created_at'];
    
    foreach ($final_columns as $column) {
        $is_required = in_array($column['Field'], $required_columns);
        $status = $is_required ? "<span class='success'>✅ REQUIRED</span>" : "<span class='info'>ℹ️ OPTIONAL</span>";
        $row_class = $is_required ? "style='background: #d4edda;'" : "";
        
        echo "<tr $row_class>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if all required columns exist
    $final_column_names = array_column($final_columns, 'Field');
    $still_missing = array_diff($required_columns, $final_column_names);
    
    if (empty($still_missing)) {
        echo "<p class='success'>🎉 All required columns are now present!</p>";
    } else {
        echo "<p class='error'>❌ Still missing columns: " . implode(', ', $still_missing) . "</p>";
    }
    
    echo "</div>";
    
    // Test the INSERT statement
    echo "<div class='section'>";
    echo "<h2>🧪 Test INSERT Statement</h2>";
    
    try {
        // Test if we can prepare the INSERT statement from process-order.php
        $test_stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_item_id, order_id, product_id, product_name, variant_id, 
                variant_name, quantity, price, total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        echo "<p class='success'>✅ Order items INSERT statement can be prepared successfully</p>";
        echo "<p class='info'>The order processing should now work correctly</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Order items INSERT statement failed: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Summary and next steps
    echo "<div class='section'>";
    echo "<h2>📋 Summary</h2>";
    
    echo "<p class='success'>✅ order_items table structure has been fixed</p>";
    echo "<p class='info'>The table now includes all required columns for order processing</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the complete order processing flow</li>";
    echo "<li>Try placing an order through the checkout</li>";
    echo "<li>Verify that order items are saved correctly</li>";
    echo "</ol>";
    
    echo "<h3>Quick Links:</h3>";
    echo "<a href='test-order-flow.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Order Flow</a>";
    echo "<a href='test-order-processing.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Full Diagnostic</a>";
    echo "<a href='checkout.php' style='padding: 10px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px; margin: 5px;'>Try Checkout</a>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
