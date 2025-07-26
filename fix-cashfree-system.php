<?php
// Comprehensive Cashfree System Fix
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

echo "<h1>🛠️ Cashfree System Fix & Recovery</h1>";

// Step 1: Check for orphaned Cashfree payments
echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔍 Step 1: Checking for Orphaned Payments</h3>";

try {
    $cashfreeHandler = new CashfreeHandler($pdo);
    
    // Get recent Cashfree transactions from database
    $stmt = $pdo->prepare("
        SELECT 
            pt.*,
            co.order_number,
            co.first_name,
            co.last_name,
            co.email,
            co.total_amount as order_amount
        FROM payment_transactions pt
        LEFT JOIN checkout_orders co ON pt.order_id = co.order_id
        WHERE pt.payment_gateway = 'cashfree' 
        AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY pt.created_at DESC
    ");
    $stmt->execute();
    $cashfreeTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cashfreeTransactions)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "⚠️ No Cashfree transactions found in last 24 hours.";
        echo "</div>";
    } else {
        echo "<h4>Recent Cashfree Transactions:</h4>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Transaction ID</th><th>Order Number</th><th>Customer</th><th>Amount</th><th>Status</th><th>Created</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($cashfreeTransactions as $txn) {
            $bgColor = $txn['order_number'] ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: {$bgColor};'>";
            echo "<td>" . substr($txn['transaction_id'], 0, 8) . "...</td>";
            echo "<td>" . ($txn['order_number'] ?? 'MISSING') . "</td>";
            echo "<td>" . ($txn['first_name'] ?? 'Unknown') . " " . ($txn['last_name'] ?? '') . "</td>";
            echo "<td>₹" . $txn['amount'] . "</td>";
            echo "<td>" . $txn['transaction_status'] . "</td>";
            echo "<td>" . $txn['created_at'] . "</td>";
            echo "<td>";
            if ($txn['order_number']) {
                echo "<a href='?check_order=" . $txn['order_number'] . "'>Check</a>";
            } else {
                echo "<a href='?recover_txn=" . $txn['transaction_id'] . "'>Recover</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Step 2: Manual Order Recovery
if (isset($_GET['recover_txn'])) {
    $txnId = $_GET['recover_txn'];
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔧 Step 2: Manual Order Recovery</h3>";
    
    try {
        // Get transaction details
        $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
        $stmt->execute([$txnId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$txn) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "❌ Transaction not found.";
            echo "</div>";
        } else {
            echo "<h4>Transaction Details:</h4>";
            echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
            echo json_encode($txn, JSON_PRETTY_PRINT);
            echo "</pre>";
            
            // Create recovery form
            echo "<h4>Create Missing Order:</h4>";
            echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
            echo "<input type='hidden' name='action' value='create_missing_order'>";
            echo "<input type='hidden' name='transaction_id' value='{$txnId}'>";
            echo "<input type='hidden' name='amount' value='{$txn['amount']}'>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>First Name: <input type='text' name='first_name' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>Last Name: <input type='text' name='last_name' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>Email: <input type='email' name='email' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>Phone: <input type='text' name='phone' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>Address: <textarea name='address' required style='margin-left: 10px; padding: 5px;'></textarea>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>City: <input type='text' name='city' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>State: <input type='text' name='state' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<div style='margin: 10px 0;'>";
            echo "<label>Pincode: <input type='text' name='pincode' required style='margin-left: 10px; padding: 5px;'></label>";
            echo "</div>";
            
            echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Order</button>";
            echo "</form>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ Error: " . $e->getMessage();
        echo "</div>";
    }
    echo "</div>";
}

// Handle order creation
if (isset($_POST['action']) && $_POST['action'] === 'create_missing_order') {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Creating Missing Order</h3>";
    
    try {
        $pdo->beginTransaction();
        
        // Generate new order ID and number
        $order_id = bin2hex(random_bytes(16));
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($order_id, 0, 6));
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO checkout_orders (
                order_id, order_number, first_name, last_name, email, phone,
                address, city, state, pincode, total_amount, payment_method,
                order_status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cashfree', 'confirmed', 'paid', NOW())
        ");
        
        $result = $stmt->execute([
            $order_id, $order_number, $_POST['first_name'], $_POST['last_name'], 
            $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['city'], 
            $_POST['state'], $_POST['pincode'], $_POST['amount']
        ]);
        
        // Update transaction to link to new order
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET order_id = ?, transaction_status = 'success', processed_at = NOW()
            WHERE transaction_id = ?
        ");
        $stmt->execute([$order_id, $_POST['transaction_id']]);
        
        $pdo->commit();
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✅ Order created successfully!<br>";
        echo "<strong>Order Number:</strong> {$order_number}<br>";
        echo "<strong>Order ID:</strong> {$order_id}<br>";
        echo "<a href='order-success.php?order_id={$order_id}' target='_blank'>View Order</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ Error creating order: " . $e->getMessage();
        echo "</div>";
    }
    echo "</div>";
}

// Step 3: System Improvements
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔧 Step 3: System Improvements Needed</h3>";
echo "<ol>";
echo "<li><strong>Add Error Logging:</strong> Enhanced logging in process-order.php</li>";
echo "<li><strong>Add Transaction Rollback:</strong> If Cashfree order creation fails, rollback database changes</li>";
echo "<li><strong>Add Order Validation:</strong> Verify order exists before redirecting to payment</li>";
echo "<li><strong>Add Webhook Retry:</strong> Handle webhook failures gracefully</li>";
echo "<li><strong>Add Customer Notification:</strong> Email customers about order status</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
pre { font-size: 12px; }
</style>
