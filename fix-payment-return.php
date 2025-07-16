<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

echo "<h2>Fix Payment Return Issues</h2>";

// Get the order ID from URL or use the one you mentioned
$orderId = $_GET['order_id'] ?? 'ORD-20250716-C2D48D';

echo "<h3>Processing Order: " . htmlspecialchars($orderId) . "</h3>";

try {
    // Step 1: Check if order exists in database
    $stmt = $pdo->prepare("
        SELECT co.*, pt.transaction_id, pt.transaction_status
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<p style='color: red;'>❌ Order not found in database!</p>";
        
        // Check if there are any recent orders
        $stmt = $pdo->query("SELECT order_number, created_at FROM checkout_orders ORDER BY created_at DESC LIMIT 5");
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Recent Orders:</h4>";
        foreach ($recentOrders as $recent) {
            echo "<p>- " . $recent['order_number'] . " (Created: " . $recent['created_at'] . ")</p>";
        }
        exit;
    }

    echo "<p style='color: green;'>✅ Order found in database</p>";
    echo "<p><strong>Current Status:</strong> Order: " . $order['order_status'] . ", Payment: " . $order['payment_status'] . "</p>";

    // Step 2: Check Cashfree payment status
    echo "<h4>Checking Cashfree Payment Status...</h4>";
    
    $cashfreeHandler = new CashfreeHandler($pdo);
    $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
    
    echo "<p>Cashfree Response:</p>";
    echo "<pre>" . json_encode($orderStatus, JSON_PRETTY_PRINT) . "</pre>";

    // Step 3: Determine if payment was successful
    $isPaid = false;
    if (isset($orderStatus['order_status']) && $orderStatus['order_status'] === 'PAID') {
        $isPaid = true;
    } elseif (isset($orderStatus['order']['order_status']) && $orderStatus['order']['order_status'] === 'PAID') {
        $isPaid = true;
    }

    echo "<p><strong>Payment Status:</strong> " . ($isPaid ? "✅ PAID" : "❌ NOT PAID") . "</p>";

    // Step 4: Update order status if payment was successful
    if ($isPaid && $order['payment_status'] !== 'paid') {
        echo "<h4>Updating Order Status...</h4>";
        
        // Update transaction status
        if ($order['transaction_id']) {
            $cashfreeHandler->updateTransactionStatus($order['transaction_id'], 'success', $orderStatus);
            echo "<p>✅ Transaction status updated</p>";
        }

        // Update order status
        $stmt = $pdo->prepare("
            UPDATE checkout_orders
            SET payment_status = 'paid',
                order_status = 'confirmed',
                updated_at = NOW()
            WHERE order_number = ?
        ");
        $result = $stmt->execute([$orderId]);
        
        if ($result) {
            echo "<p>✅ Order status updated to 'confirmed' and payment status to 'paid'</p>";
            
            // Create order success session data
            $_SESSION['order_success'] = [
                'order_id' => $order['order_id'],
                'order_number' => $order['order_number'],
                'total_amount' => $order['total_amount'],
                'payment_method' => $order['payment_method'],
                'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
                'email' => $order['email']
            ];
            
            echo "<p>✅ Order success session data created</p>";
            echo "<p><a href='order-success.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Order Success Page</a></p>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to update order status</p>";
        }
    } elseif ($isPaid) {
        echo "<p>ℹ️ Order is already marked as paid</p>";
        
        // Still create success session for testing
        $_SESSION['order_success'] = [
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
            'total_amount' => $order['total_amount'],
            'payment_method' => $order['payment_method'],
            'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
            'email' => $order['email']
        ];
        
        echo "<p><a href='order-success.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Order Success Page</a></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Payment not completed yet</p>";
    }

    // Step 5: Check OMS integration
    echo "<h4>OMS Integration Status:</h4>";
    echo "<p>✅ Order is stored in checkout_orders table (OMS compatible)</p>";
    echo "<p>📊 <a href='oms/orders.php' target='_blank' style='background: #2196F3; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px;'>View in OMS</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check error logs for more details.</p>";
}

echo "<hr>";
echo "<h4>Quick Actions:</h4>";
echo "<p><a href='debug-payment-return.php?order_id=" . urlencode($orderId) . "'>🔍 Debug Payment Return</a></p>";
echo "<p><a href='checkout.php'>🛒 Back to Checkout</a></p>";
echo "<p><a href='oms/orders.php' target='_blank'>📊 View OMS Orders</a></p>";
?>
