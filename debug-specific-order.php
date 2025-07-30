<?php
require_once 'includes/db_connection.php';

// Get the order ID from the URL you showed me
$order_id = '767f440b36af7a5bc84b63b60f1f0852'; // From your screenshot URL

echo "<h1>🔍 Debug Specific Order: $order_id</h1>";

// Check if order exists
echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>1. Order Record</h3>";

try {
    $stmt = $pdo->prepare("SELECT * FROM checkout_orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<p>✅ Order found!</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Field</th><th>Value</th>";
        echo "</tr>";
        
        foreach ($order as $key => $value) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($key) . "</td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Order not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check order items with exact same query as order-details.php
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>2. Order Items (Same Query as order-details.php)</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT oi.*
        FROM order_items oi
        WHERE oi.order_id = ?
        ORDER BY oi.created_at
    ");
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Query executed:</strong> SELECT oi.* FROM order_items oi WHERE oi.order_id = '$order_id' ORDER BY oi.created_at</p>";
    echo "<p><strong>Number of rows returned:</strong> " . count($orderItems) . "</p>";
    
    if (!empty($orderItems)) {
        echo "<p>✅ Found " . count($orderItems) . " order items:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Item ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Price</th><th>Total</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($orderItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($item['order_item_id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars(substr($item['product_id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
            echo "<td>₹" . htmlspecialchars($item['price']) . "</td>";
            echo "<td>₹" . htmlspecialchars($item['total']) . "</td>";
            echo "<td>" . htmlspecialchars($item['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No order items found!</p>";
        
        // Let's check if there are ANY order items in the table
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_items FROM order_items");
        $stmt->execute();
        $totalItems = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total order items in database: " . $totalItems['total_items'] . "</p>";
        
        // Check if there are items with similar order_id
        $stmt = $pdo->prepare("SELECT order_id, COUNT(*) as count FROM order_items GROUP BY order_id LIMIT 10");
        $stmt->execute();
        $otherOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($otherOrders)) {
            echo "<p><strong>Other orders with items:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th>Order ID</th><th>Item Count</th>";
            echo "</tr>";
            
            foreach ($otherOrders as $otherOrder) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(substr($otherOrder['order_id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars($otherOrder['count']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check payment transactions
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>3. Payment Transaction</h3>";

try {
    $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "<p>✅ Transaction found!</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Field</th><th>Value</th>";
        echo "</tr>";
        
        foreach ($transaction as $key => $value) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($key) . "</td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No transaction found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check if this order was created before or after our fix
echo "<div style='background: #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>4. Analysis</h3>";

if ($order) {
    $orderDate = new DateTime($order['created_at']);
    $now = new DateTime();
    $diff = $now->diff($orderDate);
    
    echo "<p><strong>Order created:</strong> " . $order['created_at'] . "</p>";
    echo "<p><strong>Time ago:</strong> " . $diff->format('%h hours, %i minutes ago') . "</p>";
    
    if (empty($orderItems)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>❌ This order has no items!</strong></p>";
        echo "<p>This order was likely created BEFORE our fix was applied.</p>";
        echo "<p>You can use the manual recovery tool to add the correct products:</p>";
        echo "<a href='fix-cashfree-system.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix This Order</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>✅ This order has items!</strong></p>";
        echo "<p>The fix is working correctly.</p>";
        echo "</div>";
    }
}

echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
</style>
