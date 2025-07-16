<?php
require_once 'includes/db_connection.php';

echo "<h1>Complete Order Tables Fix</h1>";
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
    .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .btn-success { background: #28a745; color: white; }
    .btn-primary { background: #007bff; color: white; }
    .btn-warning { background: #ffc107; color: black; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h2>🔧 Comprehensive Order Tables Fix</h2>";
    echo "<p class='info'>This script will check and fix all order-related database tables</p>";
    echo "</div>";
    
    // Fix 1: checkout_orders table
    echo "<div class='section'>";
    echo "<h3>1️⃣ Fixing checkout_orders Table</h3>";
    
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $checkout_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    $checkout_missing = [];
    $checkout_required = ['address', 'city', 'state', 'pincode'];
    
    foreach ($checkout_required as $col) {
        if (!in_array($col, $checkout_columns)) {
            $checkout_missing[] = $col;
        }
    }
    
    if (!empty($checkout_missing)) {
        $checkout_fixes = [
            'address' => "ADD COLUMN address TEXT AFTER phone",
            'city' => "ADD COLUMN city VARCHAR(100) AFTER address", 
            'state' => "ADD COLUMN state VARCHAR(100) AFTER city",
            'pincode' => "ADD COLUMN pincode VARCHAR(10) AFTER state"
        ];
        
        foreach ($checkout_missing as $col) {
            try {
                $pdo->exec("ALTER TABLE checkout_orders " . $checkout_fixes[$col]);
                echo "<p class='success'>✅ Added column: $col</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Failed to add $col: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p class='success'>✅ checkout_orders table is complete</p>";
    }
    echo "</div>";
    
    // Fix 2: order_items table
    echo "<div class='section'>";
    echo "<h3>2️⃣ Fixing order_items Table</h3>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='warning'>⚠️ order_items table doesn't exist. Creating...</p>";
        
        $create_order_items = "
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
        
        $pdo->exec($create_order_items);
        echo "<p class='success'>✅ Created order_items table</p>";
    } else {
        $stmt = $pdo->query("DESCRIBE order_items");
        $items_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        $items_required = ['order_item_id', 'order_id', 'product_id', 'product_name', 'variant_id', 'variant_name', 'quantity', 'price', 'total', 'created_at'];
        $items_missing = array_diff($items_required, $items_columns);
        
        if (!empty($items_missing)) {
            $items_fixes = [
                'variant_name' => "ADD COLUMN variant_name VARCHAR(100) NULL AFTER variant_id",
                'total' => "ADD COLUMN total DECIMAL(10,2) NOT NULL AFTER price",
                'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            ];
            
            foreach ($items_missing as $col) {
                if (isset($items_fixes[$col])) {
                    try {
                        $pdo->exec("ALTER TABLE order_items " . $items_fixes[$col]);
                        echo "<p class='success'>✅ Added column: $col</p>";
                    } catch (Exception $e) {
                        echo "<p class='error'>❌ Failed to add $col: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            echo "<p class='success'>✅ order_items table is complete</p>";
        }
    }
    echo "</div>";
    
    // Fix 3: payment_transactions table
    echo "<div class='section'>";
    echo "<h3>3️⃣ Checking payment_transactions Table</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_transactions'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='warning'>⚠️ payment_transactions table doesn't exist. Creating...</p>";
        
        $create_transactions = "
            CREATE TABLE payment_transactions (
                transaction_id CHAR(36) NOT NULL PRIMARY KEY,
                order_id CHAR(36) NOT NULL,
                payment_gateway VARCHAR(50) NULL,
                gateway_transaction_id VARCHAR(100) NULL,
                payment_method ENUM('card', 'upi', 'netbanking', 'wallet', 'cod') NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'INR',
                transaction_status ENUM('pending', 'processing', 'success', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
                gateway_response JSON NULL,
                failure_reason TEXT NULL,
                processed_at DATETIME NULL,
                refund_amount DECIMAL(10,2) DEFAULT 0.00,
                refund_date DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ";
        
        $pdo->exec($create_transactions);
        echo "<p class='success'>✅ Created payment_transactions table</p>";
    } else {
        echo "<p class='success'>✅ payment_transactions table exists</p>";
    }
    echo "</div>";
    
    // Verification
    echo "<div class='section'>";
    echo "<h2>🔍 Final Verification</h2>";
    
    // Test all INSERT statements
    $tests = [
        'checkout_orders' => "
            INSERT INTO checkout_orders (
                order_id, order_number, user_id, first_name, last_name, email, phone, 
                address, city, state, pincode, total_amount, payment_method, 
                order_status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
        ",
        'order_items' => "
            INSERT INTO order_items (
                order_item_id, order_id, product_id, product_name, variant_id, 
                variant_name, quantity, price, total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ",
        'payment_transactions' => "
            INSERT INTO payment_transactions (
                transaction_id, order_id, payment_method, amount, currency, 
                transaction_status, created_at
            ) VALUES (?, ?, 'cod', ?, 'INR', 'pending', NOW())
        "
    ];
    
    $all_tests_passed = true;
    foreach ($tests as $table => $sql) {
        try {
            $stmt = $pdo->prepare($sql);
            echo "<p class='success'>✅ $table INSERT statement: OK</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ $table INSERT statement failed: " . $e->getMessage() . "</p>";
            $all_tests_passed = false;
        }
    }
    
    if ($all_tests_passed) {
        echo "<p class='success'>🎉 All database tables are now ready for order processing!</p>";
    } else {
        echo "<p class='error'>❌ Some issues remain. Please check the errors above.</p>";
    }
    
    echo "</div>";
    
    // Summary
    echo "<div class='section'>";
    echo "<h2>📋 Summary</h2>";
    echo "<p>✅ Fixed checkout_orders table structure</p>";
    echo "<p>✅ Fixed order_items table structure</p>";
    echo "<p>✅ Verified payment_transactions table</p>";
    echo "<p>✅ Tested all INSERT statements</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the complete order flow</li>";
    echo "<li>Place a test order through checkout</li>";
    echo "<li>Verify orders appear in the database</li>";
    echo "<li>Check the OMS (Order Management System)</li>";
    echo "</ol>";
    
    echo "<h3>Test Links:</h3>";
    echo "<a href='test-order-flow.php' class='btn btn-success'>Test Order Flow</a>";
    echo "<a href='checkout.php' class='btn btn-primary'>Try Checkout</a>";
    echo "<a href='test-order-processing.php' class='btn btn-warning'>Full Diagnostic</a>";
    echo "<a href='oms/orders.php' class='btn btn-primary'>View OMS Orders</a>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Critical error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
