<?php
require_once 'includes/db_connection.php';

echo "<h2>🔧 Fix checkout_orders Table Structure</h2>";

// Check current table structure
try {
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    $existing_columns = [];
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check which columns are missing
    $required_columns = [
        'order_number' => 'VARCHAR(50)',
        'first_name' => 'VARCHAR(100)',
        'last_name' => 'VARCHAR(100)',
        'email' => 'VARCHAR(255)',
        'phone' => 'VARCHAR(20)',
        'address' => 'TEXT',
        'city' => 'VARCHAR(100)',
        'state' => 'VARCHAR(100)',
        'pincode' => 'VARCHAR(10)',
        'payment_method' => "ENUM('cod', 'razorpay', 'cashfree')",
        'payment_status' => "ENUM('pending', 'paid', 'failed')",
        'order_status' => "ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled')"
    ];
    
    $missing_columns = [];
    foreach ($required_columns as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            $missing_columns[$col] = $type;
        }
    }
    
    echo "<h3>Analysis:</h3>";
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✅ All required columns exist! The table structure is correct.</p>";
        echo "<p>If you're still getting the error, there might be a different issue.</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing " . count($missing_columns) . " required columns:</p>";
        echo "<ul>";
        foreach ($missing_columns as $col => $type) {
            echo "<li><strong>$col</strong> ($type)</li>";
        }
        echo "</ul>";
        
        echo "<h3>🚀 Auto-Fix Available:</h3>";
        echo "<p><a href='?auto_fix=1' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 Fix Table Structure Now</a></p>";
        echo "<p><em>This will add all missing columns to your table.</em></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>The checkout_orders table might not exist.</p>";
    echo "<p><a href='?create_table=1' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>Create Table</a></p>";
}

// Handle auto-fix
if (isset($_GET['auto_fix'])) {
    try {
        echo "<h3>🔧 Fixing Table Structure...</h3>";
        
        // Get current columns again
        $stmt = $pdo->query("DESCRIBE checkout_orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existing_columns = array_column($columns, 'Field');
        
        $required_columns = [
            'order_number' => 'VARCHAR(50) UNIQUE',
            'first_name' => 'VARCHAR(100) NOT NULL',
            'last_name' => 'VARCHAR(100) NOT NULL',
            'email' => 'VARCHAR(255) NOT NULL',
            'phone' => 'VARCHAR(20) NOT NULL',
            'address' => 'TEXT NOT NULL',
            'city' => 'VARCHAR(100) NOT NULL',
            'state' => 'VARCHAR(100) NOT NULL',
            'pincode' => 'VARCHAR(10) NOT NULL',
            'payment_method' => "ENUM('cod', 'razorpay', 'cashfree') DEFAULT 'cod'",
            'payment_status' => "ENUM('pending', 'paid', 'failed') DEFAULT 'pending'",
            'order_status' => "ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'"
        ];
        
        $added_columns = [];
        
        foreach ($required_columns as $col => $definition) {
            if (!in_array($col, $existing_columns)) {
                $sql = "ALTER TABLE checkout_orders ADD COLUMN $col $definition";
                echo "<p>Adding column: <code>$col</code></p>";
                $pdo->exec($sql);
                $added_columns[] = $col;
            }
        }
        
        if (empty($added_columns)) {
            echo "<p style='color: orange;'>ℹ️ No columns needed to be added.</p>";
        } else {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>✅ Table Fixed Successfully!</h4>";
            echo "<p>Added " . count($added_columns) . " columns:</p>";
            echo "<ul>";
            foreach ($added_columns as $col) {
                echo "<li>$col</li>";
            }
            echo "</ul>";
            echo "<p><strong>Your checkout should now work!</strong></p>";
            echo "</div>";
        }
        
        echo "<p><a href='fix-table-now.php'>🔄 Refresh to verify</a></p>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Error fixing table:</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// Handle table creation
if (isset($_GET['create_table'])) {
    try {
        echo "<h3>🔧 Creating checkout_orders Table...</h3>";
        
        $sql = "
        CREATE TABLE checkout_orders (
            order_id CHAR(36) NOT NULL PRIMARY KEY,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            user_id CHAR(36) NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            pincode VARCHAR(10) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cod', 'razorpay', 'cashfree') DEFAULT 'cod',
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ";
        
        $pdo->exec($sql);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Table Created Successfully!</h4>";
        echo "<p>The checkout_orders table has been created with all required columns.</p>";
        echo "</div>";
        
        echo "<p><a href='fix-table-now.php'>🔄 Refresh to verify</a></p>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Error creating table:</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<p>";
echo "<a href='checkout.php'>Try Checkout</a> | ";
echo "<a href='cart.php'>View Cart</a> | ";
echo "<a href='products.php'>Products</a>";
echo "</p>";
?>
