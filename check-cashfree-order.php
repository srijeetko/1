<?php
// Check specific order status with Cashfree API
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

$orderId = $_GET['order_id'] ?? '';

echo "<h1>🔍 Cashfree Order Status Checker</h1>";

if (empty($orderId)) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px;'>";
    echo "<h3>Enter Order ID to Check:</h3>";
    echo "<form method='GET'>";
    echo "<input type='text' name='order_id' placeholder='Enter Order Number' style='padding: 8px; width: 300px;'>";
    echo "<button type='submit' style='padding: 8px 15px; margin-left: 10px;'>Check Status</button>";
    echo "</form>";
    echo "</div>";
} else {
    try {
        $cashfreeHandler = new CashfreeHandler($pdo);
        
        echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>Checking Order: {$orderId}</h3>";
        echo "</div>";
        
        // Get order status from Cashfree
        $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📊 Cashfree API Response:</h3>";
        echo "<pre style='background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo json_encode($orderStatus, JSON_PRETTY_PRINT);
        echo "</pre>";
        echo "</div>";
        
        // Check database for this order
        $stmt = $pdo->prepare("
            SELECT 
                co.*,
                pt.transaction_id,
                pt.transaction_status,
                pt.gateway_transaction_id,
                pt.gateway_response
            FROM checkout_orders co
            LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
            WHERE co.order_number = ?
        ");
        $stmt->execute([$orderId]);
        $dbOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dbOrder) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>✅ Order Found in Database:</h3>";
            echo "<strong>Order ID:</strong> {$dbOrder['order_id']}<br>";
            echo "<strong>Order Number:</strong> {$dbOrder['order_number']}<br>";
            echo "<strong>Amount:</strong> ₹{$dbOrder['total_amount']}<br>";
            echo "<strong>Payment Status:</strong> {$dbOrder['payment_status']}<br>";
            echo "<strong>Order Status:</strong> {$dbOrder['order_status']}<br>";
            echo "<strong>Customer:</strong> {$dbOrder['customer_name']} ({$dbOrder['email']})<br>";
            echo "<strong>Created:</strong> {$dbOrder['created_at']}<br>";
            
            if ($dbOrder['transaction_status']) {
                echo "<strong>Transaction Status:</strong> {$dbOrder['transaction_status']}<br>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>❌ Order NOT Found in Database</h3>";
            echo "<p>This order number does not exist in our database. This could mean:</p>";
            echo "<ul>";
            echo "<li>Order creation failed before payment</li>";
            echo "<li>Database connection issue during order creation</li>";
            echo "<li>Order number mismatch</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        // Analyze the situation
        $cashfreeStatus = $orderStatus['order_status'] ?? $orderStatus['order']['order_status'] ?? 'UNKNOWN';
        
        echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🔍 Analysis & Next Steps:</h3>";
        
        if ($cashfreeStatus === 'PAID' && !$dbOrder) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🚨 CRITICAL: Payment Successful but Order Missing</h4>";
            echo "<p><strong>Status:</strong> Payment was successful in Cashfree but order is missing from database.</p>";
            echo "<p><strong>Action Required:</strong> Manual order creation or refund needed.</p>";
            echo "</div>";
        } elseif ($cashfreeStatus === 'PAID' && $dbOrder && $dbOrder['payment_status'] !== 'paid') {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>⚠️ Payment Successful but Status Not Updated</h4>";
            echo "<p><strong>Status:</strong> Payment successful but database not updated.</p>";
            echo "<p><strong>Action:</strong> Update order status to 'paid'.</p>";
            echo "<a href='fix-order-status.php?order_id={$orderId}' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Fix Order Status</a>";
            echo "</div>";
        } elseif ($cashfreeStatus === 'PAID' && $dbOrder && $dbOrder['payment_status'] === 'paid') {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>✅ Everything is Correct</h4>";
            echo "<p>Payment successful and order status is correct.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>ℹ️ Status Information</h4>";
            echo "<p><strong>Cashfree Status:</strong> {$cashfreeStatus}</p>";
            echo "<p><strong>Database Status:</strong> " . ($dbOrder ? $dbOrder['payment_status'] : 'Order not found') . "</p>";
            echo "</div>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>❌ Error Checking Order Status</h3>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
pre { font-size: 12px; }
</style>
