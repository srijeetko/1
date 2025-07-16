<?php
require_once 'includes/db_connection.php';

echo "<h2>Payment Return Debug</h2>";

// Check if order_id is provided
$orderId = $_GET['order_id'] ?? 'ORD-20250716-C2D48D'; // Use the one from your URL as default

echo "<h3>Testing Order ID: " . htmlspecialchars($orderId) . "</h3>";

// Check if order exists in database
$stmt = $pdo->prepare("
    SELECT co.*, pt.transaction_id, pt.transaction_status
    FROM checkout_orders co
    LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
    WHERE co.order_number = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    echo "<h4>✅ Order Found in Database:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    foreach ($order as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<h4>❌ Order NOT Found in Database</h4>";
    
    // Check what orders exist
    echo "<h5>Recent Orders in Database:</h5>";
    $stmt = $pdo->query("SELECT order_number, order_id, created_at, order_status, payment_status FROM checkout_orders ORDER BY created_at DESC LIMIT 10");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($recentOrders) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Order Number</th><th>Order ID</th><th>Created</th><th>Order Status</th><th>Payment Status</th></tr>";
        foreach ($recentOrders as $recentOrder) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($recentOrder['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($recentOrder['order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($recentOrder['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($recentOrder['order_status']) . "</td>";
            echo "<td>" . htmlspecialchars($recentOrder['payment_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found in database.</p>";
    }
}

// Test Cashfree API connection
echo "<h3>Testing Cashfree API:</h3>";
try {
    require_once 'includes/cashfree-config.php';
    require_once 'includes/cashfree-handler.php';
    
    $cashfreeHandler = new CashfreeHandler($pdo);
    $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
    
    echo "<h4>✅ Cashfree API Response:</h4>";
    echo "<pre>" . json_encode($orderStatus, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<h4>❌ Cashfree API Error:</h4>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check OMS integration
echo "<h3>OMS Integration Check:</h3>";
if ($order) {
    echo "<p>✅ Order is stored in checkout_orders table (used by OMS)</p>";
    echo "<p>📊 <a href='oms/orders.php' target='_blank'>View in OMS</a></p>";
} else {
    echo "<p>❌ Order not found - won't appear in OMS</p>";
}

// Test payment-return.php directly
echo "<h3>Test Links:</h3>";
echo "<p><a href='payment-return.php?order_id=" . urlencode($orderId) . "' target='_blank'>Test payment-return.php</a></p>";
echo "<p><a href='order-success.php' target='_blank'>Test order-success.php</a></p>";
?>
