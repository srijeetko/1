<?php
require_once 'includes/db_connection.php';

echo "<h1>🔍 Debug Order Items Issue</h1>";

// Check recent orders and their items
echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Recent Orders with Items</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            co.order_id,
            co.order_number,
            co.first_name,
            co.last_name,
            co.total_amount,
            co.payment_method,
            co.order_status,
            co.created_at,
            COUNT(oi.order_item_id) as item_count,
            GROUP_CONCAT(CONCAT(oi.product_name, ' (Qty: ', oi.quantity, ')') SEPARATOR ', ') as items
        FROM checkout_orders co
        LEFT JOIN order_items oi ON co.order_id = oi.order_id
        WHERE co.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY co.order_id
        ORDER BY co.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p>No recent orders found.</p>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order Number</th><th>Customer</th><th>Amount</th><th>Items Count</th><th>Items</th><th>Status</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($orders as $order) {
            $bgColor = $order['item_count'] > 0 ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: {$bgColor};'>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . $order['item_count'] . "</td>";
            echo "<td>" . ($order['items'] ?? 'No items') . "</td>";
            echo "<td>" . $order['order_status'] . "</td>";
            echo "<td>" . $order['created_at'] . "</td>";
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

// Check cart session data
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Current Cart Session Data</h3>";

session_start();
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
    echo json_encode($_SESSION['cart'], JSON_PRETTY_PRINT);
    echo "</pre>";
} else {
    echo "<p>No cart data in session.</p>";
}
echo "</div>";

// Check order_items table structure
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Order Items Table Structure</h3>";

try {
    $stmt = $pdo->prepare("DESCRIBE order_items");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test cart processing
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Test Cart Processing</h3>";

// Simulate a cart for testing
$testCart = [
    'product1_default' => [
        'product_id' => 'test-product-1',
        'variant_id' => null,
        'quantity' => 2
    ],
    'product2_default' => [
        'product_id' => 'test-product-2', 
        'variant_id' => null,
        'quantity' => 1
    ]
];

echo "<h4>Test Cart Data:</h4>";
echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
echo json_encode($testCart, JSON_PRETTY_PRINT);
echo "</pre>";

// Test processing logic
$orderItems = [];
$totalAmount = 0;

foreach ($testCart as $cartKey => $cartItem) {
    $product_id = $cartItem['product_id'];
    $variant_id = $cartItem['variant_id'];
    $quantity = $cartItem['quantity'];
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $price = $product['price'];
        $itemTotal = $price * $quantity;
        $totalAmount += $itemTotal;
        
        $orderItems[] = [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'variant_id' => $variant_id,
            'price' => $price,
            'quantity' => $quantity,
            'total' => $itemTotal
        ];
        
        echo "<p>✅ Found product: {$product['name']} - ₹{$price} x {$quantity} = ₹{$itemTotal}</p>";
    } else {
        echo "<p>❌ Product not found: {$product_id}</p>";
    }
}

echo "<h4>Processed Order Items:</h4>";
echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
echo json_encode($orderItems, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<p><strong>Total Amount: ₹{$totalAmount}</strong></p>";

echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
pre { font-size: 12px; }
</style>
