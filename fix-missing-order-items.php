<?php
require_once 'includes/db_connection.php';

echo "<h1>🔧 Fix Missing Order Items</h1>";

// Find orders with missing items
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Orders with Missing Items</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT
            co.order_id,
            co.order_number,
            co.first_name,
            co.last_name,
            co.total_amount,
            co.payment_method,
            co.created_at,
            COUNT(oi.order_item_id) as item_count
        FROM checkout_orders co
        LEFT JOIN order_items oi ON co.order_id = oi.order_id
        WHERE co.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY co.order_id, co.order_number, co.first_name, co.last_name, co.total_amount, co.payment_method, co.created_at
        HAVING item_count = 0
        ORDER BY co.created_at DESC
    ");
    $stmt->execute();
    $ordersWithoutItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ordersWithoutItems)) {
        echo "<p>✅ No orders found with missing items.</p>";
    } else {
        echo "<p>Found " . count($ordersWithoutItems) . " orders with missing items:</p>";
        
        echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order Number</th><th>Customer</th><th>Amount</th><th>Payment Method</th><th>Created</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($ordersWithoutItems as $order) {
            echo "<tr>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . strtoupper($order['payment_method']) . "</td>";
            echo "<td>" . $order['created_at'] . "</td>";
            echo "<td>";
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='fix_order_id' value='" . $order['order_id'] . "'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Fix Order</button>";
            echo "</form>";
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

// Handle fix request
if (isset($_POST['fix_order_id'])) {
    $fix_order_id = $_POST['fix_order_id'];
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔧 Fixing Order: $fix_order_id</h3>";
    
    try {
        // Get order details
        $stmt = $pdo->prepare("SELECT * FROM checkout_orders WHERE order_id = ?");
        $stmt->execute([$fix_order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        echo "<p><strong>Order:</strong> {$order['order_number']} - {$order['first_name']} {$order['last_name']}</p>";
        echo "<p><strong>Amount:</strong> ₹{$order['total_amount']}</p>";
        
        // Since we don't have the original cart data, we'll create a generic item
        // based on the order amount
        $item_id = bin2hex(random_bytes(16));
        $product_name = "Order Items (Recovered)";
        $quantity = 1;
        $price = $order['total_amount'];
        $total = $order['total_amount'];
        
        // Insert the recovered item
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_item_id, order_id, product_id, product_name, variant_id, 
                variant_name, quantity, unit_price, total_price, price, total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $item_id, $fix_order_id, 'recovered-item', $product_name,
            null, null, $quantity, $price, $total, $price, $total, $order['created_at']
        ]);
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Order Fixed Successfully!</strong><br>";
            echo "Added recovered item: $product_name (₹$price)<br>";
            echo "The order now shows 1 item in the system.";
            echo "</div>";
            
            // Log the recovery
            error_log("Order items recovered - Order: {$order['order_number']}, Amount: ₹$price");
            
        } else {
            throw new Exception("Failed to insert recovered item");
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Fix Failed:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
    
    // Refresh the page to show updated results
    echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
}

// Show recent successful orders for comparison
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Recent Orders with Items (for comparison)</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT
            co.order_id,
            co.order_number,
            co.first_name,
            co.last_name,
            co.total_amount,
            COUNT(oi.order_item_id) as item_count,
            GROUP_CONCAT(oi.product_name SEPARATOR ', ') as items
        FROM checkout_orders co
        LEFT JOIN order_items oi ON co.order_id = oi.order_id
        WHERE co.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY co.order_id, co.order_number, co.first_name, co.last_name, co.total_amount
        HAVING item_count > 0
        ORDER BY co.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $ordersWithItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ordersWithItems)) {
        echo "<p>No recent orders with items found.</p>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order Number</th><th>Customer</th><th>Amount</th><th>Items Count</th><th>Items</th>";
        echo "</tr>";
        
        foreach ($ordersWithItems as $order) {
            echo "<tr>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . $order['item_count'] . "</td>";
            echo "<td>" . $order['items'] . "</td>";
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

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
button:hover { opacity: 0.8; }
</style>
