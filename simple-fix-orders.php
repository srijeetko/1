<?php
require_once 'includes/db_connection.php';

echo "<h1>🔧 Simple Order Items Fix</h1>";

// Handle fix request first
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
        
        // Get a real product_id to satisfy foreign key constraint
        $stmt = $pdo->prepare("SELECT product_id, name FROM products LIMIT 1");
        $stmt->execute();
        $sampleProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sampleProduct) {
            throw new Exception("No products found in database. Cannot create recovered item.");
        }

        // Create a recovered item using real product_id
        $item_id = bin2hex(random_bytes(16));
        $product_name = "Order Items (Recovered) - " . $sampleProduct['name'];
        $quantity = 1;
        $price = $order['total_amount'];
        $total = $order['total_amount'];

        // Insert the recovered item
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_item_id, order_id, product_id, product_name,
                quantity, unit_price, total_price, price, total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $item_id, $fix_order_id, $sampleProduct['product_id'], $product_name,
            $quantity, $price, $total, $price, $total, $order['created_at']
        ]);
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Order Fixed Successfully!</strong><br>";
            echo "Added recovered item: $product_name (₹$price)<br>";
            echo "The order now shows 1 item in the system.";
            echo "</div>";
        } else {
            throw new Exception("Failed to insert recovered item");
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Fix Failed:</strong> " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
}

// Find orders with missing items (simplified query)
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Recent Orders with Missing Items</h3>";

try {
    // Get all recent orders
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, first_name, last_name, total_amount, payment_method, created_at
        FROM checkout_orders 
        WHERE created_at >= CURDATE() - INTERVAL 7 DAY
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ordersWithoutItems = [];
    
    // Check each order for items
    foreach ($allOrders as $order) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['order_id']]);
        $itemCount = $stmt->fetchColumn();
        
        if ($itemCount == 0) {
            $order['item_count'] = 0;
            $ordersWithoutItems[] = $order;
        }
    }
    
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

// Show recent orders with items
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Recent Orders with Items (for comparison)</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, first_name, last_name, total_amount, created_at
        FROM checkout_orders 
        WHERE created_at >= CURDATE() - INTERVAL 7 DAY
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ordersWithItems = [];
    
    // Check each order for items
    foreach ($allOrders as $order) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as item_count, GROUP_CONCAT(product_name SEPARATOR ', ') as items
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$order['order_id']]);
        $itemData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($itemData['item_count'] > 0) {
            $order['item_count'] = $itemData['item_count'];
            $order['items'] = $itemData['items'];
            $ordersWithItems[] = $order;
        }
    }
    
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

// Quick test of order items table
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Order Items Table Test</h3>";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_items FROM order_items");
    $stmt->execute();
    $totalItems = $stmt->fetchColumn();
    
    echo "<p><strong>Total items in order_items table:</strong> $totalItems</p>";
    
    if ($totalItems > 0) {
        $stmt = $pdo->prepare("
            SELECT oi.*, co.order_number 
            FROM order_items oi 
            JOIN checkout_orders co ON oi.order_id = co.order_id 
            ORDER BY oi.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Recent Order Items:</h4>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order Number</th><th>Product Name</th><th>Quantity</th><th>Price</th><th>Total</th>";
        echo "</tr>";
        
        foreach ($recentItems as $item) {
            echo "<tr>";
            echo "<td>" . $item['order_number'] . "</td>";
            echo "<td>" . $item['product_name'] . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>₹" . $item['price'] . "</td>";
            echo "<td>₹" . $item['total'] . "</td>";
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
