<?php
include 'includes/db_connection.php';

echo "<h1>Available Orders for Testing Return System</h1>";

try {
    // Get all paid orders
    $stmt = $pdo->query("
        SELECT co.*, CONCAT(co.first_name, ' ', co.last_name) as customer_name,
               COUNT(oi.order_item_id) as item_count
        FROM checkout_orders co
        LEFT JOIN order_items oi ON co.order_id = oi.order_id
        WHERE co.payment_status = 'paid'
        GROUP BY co.order_id
        ORDER BY co.created_at DESC
        LIMIT 10
    ");
    $orders = $stmt->fetchAll();
    
    if (empty($orders)) {
        echo "<p>No paid orders found. Let me create a sample order for testing...</p>";
        
        // Create a sample order for testing
        $order_id = str_replace('-', '', bin2hex(random_bytes(18)));
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($order_id, 0, 6));
        
        $stmt = $pdo->prepare("
            INSERT INTO checkout_orders (
                order_id, order_number, first_name, last_name, email, phone,
                address, city, state, pincode, total_amount, payment_method,
                order_status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $order_id,
            $order_number,
            'John',
            'Doe',
            'john.doe@example.com',
            '9876543210',
            '123 Test Street',
            'Mumbai',
            'Maharashtra',
            '400001',
            2500.00,
            'online',
            'delivered',
            'paid',
            date('Y-m-d H:i:s', strtotime('-5 days')) // 5 days ago
        ]);
        
        // Add sample order items
        $products = [
            ['name' => 'Whey Protein Isolate', 'price' => 1500.00, 'quantity' => 1],
            ['name' => 'Creatine Monohydrate', 'price' => 800.00, 'quantity' => 1],
            ['name' => 'BCAA Powder', 'price' => 200.00, 'quantity' => 1]
        ];
        
        foreach ($products as $product) {
            $item_id = str_replace('-', '', bin2hex(random_bytes(18)));
            $product_id = str_replace('-', '', bin2hex(random_bytes(18)));
            
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_item_id, order_id, product_id, product_name, quantity, price, total
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $total = $product['price'] * $product['quantity'];
            $stmt->execute([
                $item_id,
                $order_id,
                $product_id,
                $product['name'],
                $product['quantity'],
                $product['price'],
                $total
            ]);
        }
        
        echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
        echo "✅ Sample order created successfully!<br>";
        echo "Order ID: <strong>$order_id</strong><br>";
        echo "Order Number: <strong>$order_number</strong>";
        echo "</div>";
        
        // Refresh the orders list
        $stmt = $pdo->query("
            SELECT co.*, CONCAT(co.first_name, ' ', co.last_name) as customer_name,
                   COUNT(oi.order_item_id) as item_count
            FROM checkout_orders co
            LEFT JOIN order_items oi ON co.order_id = oi.order_id
            WHERE co.payment_status = 'paid'
            GROUP BY co.order_id
            ORDER BY co.created_at DESC
            LIMIT 10
        ");
        $orders = $stmt->fetchAll();
    }
    
    echo "<h2>Available Orders (Payment Status: Paid)</h2>";
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
    echo "<p><strong>Instructions:</strong> Copy any Order ID or Order Number below and use it to test the return request system.</p>";
    echo "</div>";
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 1rem 0;'>";
    echo "<thead style='background: #ff6b35; color: white;'>";
    echo "<tr>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Order Number</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Order ID</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Customer</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Items</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Amount</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Date</th>";
    echo "<th style='padding: 1rem; border: 1px solid #ddd;'>Test Return</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($orders as $order) {
        echo "<tr style='border-bottom: 1px solid #ddd;'>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd;'><strong>" . htmlspecialchars($order['order_number']) . "</strong></td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd; font-family: monospace; font-size: 0.9rem;'>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd;'>" . htmlspecialchars($order['customer_name']) . "<br><small>" . htmlspecialchars($order['email']) . "</small></td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd; text-align: center;'>" . $order['item_count'] . "</td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd; text-align: right;'>₹" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd;'>" . date('M j, Y', strtotime($order['created_at'])) . "</td>";
        echo "<td style='padding: 1rem; border: 1px solid #ddd; text-align: center;'>";
        echo "<a href='return-request.php?order_id=" . urlencode($order['order_id']) . "' style='background: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-size: 0.9rem;'>Test Return</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
    echo "<h2>Quick Test Links</h2>";
    if (!empty($orders)) {
        $first_order = $orders[0];
        echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap; margin: 1rem 0;'>";
        echo "<a href='return-request.php?order_id=" . urlencode($first_order['order_id']) . "' style='background: #ff6b35; color: white; padding: 1rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500;'>🔄 Test Return Request</a>";
        echo "<a href='return-tracking.php' style='background: #17a2b8; color: white; padding: 1rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500;'>📍 Test Return Tracking</a>";
        echo "<a href='products.php' style='background: #6c757d; color: white; padding: 1rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500;'>🛍️ Shop Products</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin: 1rem 0;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

table {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    
    table {
        font-size: 0.9rem;
    }
    
    td, th {
        padding: 0.5rem !important;
    }
}
</style>
