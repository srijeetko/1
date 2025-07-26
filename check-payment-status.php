<?php
// Emergency Payment Status Checker
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';

echo "<h1>🚨 Payment Status Emergency Check</h1>";

try {
    // Get recent orders from last 1 hour
    $stmt = $pdo->prepare("
        SELECT 
            co.*,
            pt.transaction_id,
            pt.payment_gateway,
            pt.transaction_status,
            pt.gateway_transaction_id,
            pt.gateway_response,
            pt.created_at as payment_created
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY co.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recentOrders)) {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>❌ No Recent Orders Found</h3>";
        echo "<p>No orders found in the last hour. This might indicate a database issue.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📋 Recent Orders (Last 1 Hour):</h3>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order ID</th><th>Order Number</th><th>Amount</th><th>Payment Status</th><th>Transaction Status</th><th>Created</th><th>Actions</th>";
        echo "</tr>";
        
        foreach ($recentOrders as $order) {
            $bgColor = '';
            if ($order['payment_status'] === 'paid') {
                $bgColor = 'background: #d4edda;'; // Green
            } elseif ($order['payment_status'] === 'pending') {
                $bgColor = 'background: #fff3cd;'; // Yellow
            } else {
                $bgColor = 'background: #f8d7da;'; // Red
            }
            
            echo "<tr style='{$bgColor}'>";
            echo "<td>{$order['order_id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>₹{$order['total_amount']}</td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>" . ($order['transaction_status'] ?? 'N/A') . "</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "<td>";
            if ($order['order_number']) {
                echo "<a href='check-cashfree-order.php?order_id={$order['order_number']}' target='_blank'>Check Cashfree</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }

    // Check for any orders with your email or recent timestamp
    echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔍 Quick Search:</h3>";
    echo "<form method='GET'>";
    echo "<input type='text' name='search' placeholder='Enter your email or phone' style='padding: 8px; width: 300px;'>";
    echo "<button type='submit' style='padding: 8px 15px; margin-left: 10px;'>Search Orders</button>";
    echo "</form>";
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $stmt = $pdo->prepare("
            SELECT
                co.*,
                pt.transaction_status,
                pt.gateway_response
            FROM checkout_orders co
            LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
            WHERE co.email LIKE ? OR co.phone LIKE ? OR CONCAT(co.first_name, ' ', co.last_name) LIKE ?
            ORDER BY co.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$search, $search, $search]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($searchResults)) {
            echo "<h4>Search Results:</h4>";
            foreach ($searchResults as $result) {
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
                echo "<strong>Order:</strong> {$result['order_number']} | ";
                echo "<strong>Amount:</strong> ₹{$result['total_amount']} | ";
                echo "<strong>Status:</strong> {$result['payment_status']} | ";
                echo "<strong>Created:</strong> {$result['created_at']}";
                echo "</div>";
            }
        }
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🛠️ Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check Cashfree Dashboard:</strong> Login to merchant.cashfree.com and check recent transactions</li>";
echo "<li><strong>Note Transaction ID:</strong> If payment was successful in Cashfree, note the transaction ID</li>";
echo "<li><strong>Manual Update:</strong> We can manually update the order status if needed</li>";
echo "<li><strong>Refund if Needed:</strong> If order is missing but payment went through, we can process refund</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
</style>
