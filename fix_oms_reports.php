<?php
require_once 'includes/db_connection.php';

echo "<h2>🔧 Fix OMS Reports System</h2>";

// Handle fix request
if (isset($_POST['fix_reports'])) {
    try {
        echo "<h3>🔧 Fixing OMS Reports System...</h3>";
        
        // Check and create missing tables
        $tables_to_check = [
            'payment_transactions' => "
                CREATE TABLE IF NOT EXISTS `payment_transactions` (
                    `transaction_id` CHAR(36) NOT NULL PRIMARY KEY,
                    `order_id` CHAR(36) NOT NULL,
                    `payment_gateway` VARCHAR(50) NOT NULL,
                    `gateway_transaction_id` VARCHAR(255),
                    `amount` DECIMAL(10,2) NOT NULL,
                    `currency` VARCHAR(3) DEFAULT 'INR',
                    `transaction_status` ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
                    `payment_method` VARCHAR(50),
                    `gateway_response` JSON,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            ",
            'activity_log' => "
                CREATE TABLE IF NOT EXISTS `activity_log` (
                    `log_id` CHAR(36) NOT NULL PRIMARY KEY,
                    `admin_id` CHAR(36),
                    `action_type` VARCHAR(100) NOT NULL,
                    `action_description` TEXT NOT NULL,
                    `affected_table` VARCHAR(100),
                    `affected_record_id` CHAR(36),
                    `old_values` JSON,
                    `new_values` JSON,
                    `ip_address` VARCHAR(45),
                    `user_agent` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`admin_id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            "
        ];
        
        foreach ($tables_to_check as $table_name => $create_sql) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec($create_sql);
                echo "<p>✅ Created missing table: $table_name</p>";
            } else {
                echo "<p>✅ Table exists: $table_name</p>";
            }
        }
        
        // Add sample data for testing if tables are empty
        echo "<h4>Adding Sample Data for Testing...</h4>";
        
        // Check if we have any orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM checkout_orders");
        $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($order_count == 0) {
            // Add sample orders
            $sample_orders = [
                ['total' => 1299.00, 'status' => 'delivered', 'payment' => 'paid', 'method' => 'razorpay'],
                ['total' => 899.00, 'status' => 'shipped', 'payment' => 'paid', 'method' => 'cashfree'],
                ['total' => 1599.00, 'status' => 'delivered', 'payment' => 'paid', 'method' => 'cod'],
                ['total' => 799.00, 'status' => 'processing', 'payment' => 'paid', 'method' => 'razorpay'],
                ['total' => 2199.00, 'status' => 'cancelled', 'payment' => 'failed', 'method' => 'razorpay']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO checkout_orders 
                (order_id, first_name, last_name, email, phone, total_amount, order_status, payment_status, payment_method, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($sample_orders as $i => $order) {
                $order_id = bin2hex(random_bytes(16));
                $stmt->execute([
                    $order_id,
                    'Customer',
                    'Test' . ($i + 1),
                    'customer' . ($i + 1) . '@example.com',
                    '9876543210',
                    $order['total'],
                    $order['status'],
                    $order['payment'],
                    $order['method']
                ]);
                
                // Add corresponding payment transaction
                if ($order['payment'] === 'paid') {
                    $transaction_id = bin2hex(random_bytes(16));
                    $pdo->prepare("
                        INSERT INTO payment_transactions 
                        (transaction_id, order_id, payment_gateway, amount, transaction_status, payment_method, created_at) 
                        VALUES (?, ?, ?, ?, 'success', ?, NOW())
                    ")->execute([
                        $transaction_id,
                        $order_id,
                        $order['method'] === 'cod' ? 'cod' : $order['method'],
                        $order['total'],
                        $order['method']
                    ]);
                }
            }
            
            echo "<p>✅ Added " . count($sample_orders) . " sample orders</p>";
        } else {
            echo "<p>✅ Orders table has $order_count existing records</p>";
        }
        
        // Check delivery assignments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM delivery_assignments");
        $assignment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($assignment_count == 0) {
            // Get some orders and delivery partners to create assignments
            $stmt = $pdo->query("SELECT order_id FROM checkout_orders WHERE payment_status = 'paid' LIMIT 3");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT partner_id FROM delivery_partners LIMIT 3");
            $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($orders) && !empty($partners)) {
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_assignments 
                    (assignment_id, order_id, partner_id, assigned_by, pickup_address, delivery_address, delivery_status, delivery_charges, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                foreach ($orders as $i => $order) {
                    $assignment_id = bin2hex(random_bytes(16));
                    $partner = $partners[$i % count($partners)];
                    $statuses = ['delivered', 'in_transit', 'delivered'];
                    $charges = [50, 45, 55];
                    
                    $stmt->execute([
                        $assignment_id,
                        $order['order_id'],
                        $partner['partner_id'],
                        'admin-test-id',
                        'Alpha Nutrition Warehouse, Mumbai',
                        'Customer Address, City, State',
                        $statuses[$i],
                        $charges[$i]
                    ]);
                }
                
                echo "<p>✅ Added " . count($orders) . " sample delivery assignments</p>";
            }
        } else {
            echo "<p>✅ Delivery assignments table has $assignment_count existing records</p>";
        }
        
        echo "<p style='color: green; font-weight: bold;'>🎉 OMS Reports system fixed successfully!</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Fix Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test the reports system
echo "<h3>🧪 Testing Reports System</h3>";

try {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-d');
    
    // Test each report query
    $tests = [
        'Sales Overview' => "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM checkout_orders 
            WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
        ",
        'Payment Methods' => "
            SELECT 
                payment_method,
                COUNT(*) as count
            FROM checkout_orders 
            WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
            GROUP BY payment_method
        ",
        'Delivery Performance' => "
            SELECT 
                dp.partner_name,
                COUNT(da.assignment_id) as assignments
            FROM delivery_assignments da
            JOIN delivery_partners dp ON da.partner_id = dp.partner_id
            WHERE DATE(da.created_at) BETWEEN '$date_from' AND '$date_to'
            GROUP BY dp.partner_id, dp.partner_name
        ",
        'Transaction Performance' => "
            SELECT 
                payment_gateway,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount
            FROM payment_transactions
            WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
            GROUP BY payment_gateway
        "
    ];
    
    foreach ($tests as $test_name => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✅ $test_name: " . count($result) . " records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ $test_name: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check if fix is needed
    $needs_fix = false;
    
    // Check required tables
    $required_tables = ['checkout_orders', 'payment_transactions', 'delivery_assignments', 'delivery_partners'];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $needs_fix = true;
            break;
        }
    }
    
    // Check if we have data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM checkout_orders");
    $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($order_count == 0) {
        $needs_fix = true;
    }
    
    if ($needs_fix) {
        echo "<form method='POST' style='margin-top: 20px;'>";
        echo "<button type='submit' name='fix_reports' style='background: #27ae60; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 Fix Reports System</button>";
        echo "</form>";
    } else {
        echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ Reports system appears to be working correctly!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
