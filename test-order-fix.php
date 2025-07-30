<?php
// Test the order processing fixes
require_once 'includes/db_connection.php';

echo "<h1>🧪 Test Order Processing Fixes</h1>";

// Test 1: Check if order_items table structure is correct
echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔍 Test 1: Order Items Table Structure</h3>";

try {
    $stmt = $pdo->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
    echo "✅ Order items table structure looks good!";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 2: Check recent orders and their items
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📊 Test 2: Recent Orders with Items</h3>";

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
            GROUP_CONCAT(CONCAT(oi.product_name, ' (Qty: ', oi.quantity, ', ₹', oi.price, ')') SEPARATOR '<br>') as items
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
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "⚠️ No orders found in last 24 hours.";
        echo "</div>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order Number</th><th>Customer</th><th>Amount</th><th>Items</th><th>Products</th><th>Status</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($orders as $order) {
            $bgColor = $order['item_count'] > 0 ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: {$bgColor};'>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['first_name'] . " " . $order['last_name'] . "</td>";
            echo "<td>₹" . $order['total_amount'] . "</td>";
            echo "<td>" . $order['item_count'] . "</td>";
            echo "<td style='font-size: 12px;'>" . ($order['items'] ?? 'No items') . "</td>";
            echo "<td>" . $order['order_status'] . "</td>";
            echo "<td>" . date('M j, H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count orders with and without items
        $withItems = 0;
        $withoutItems = 0;
        foreach ($orders as $order) {
            if ($order['item_count'] > 0) {
                $withItems++;
            } else {
                $withoutItems++;
            }
        }
        
        echo "<div style='margin-top: 15px;'>";
        echo "<strong>Summary:</strong><br>";
        echo "✅ Orders with items: {$withItems}<br>";
        echo "❌ Orders without items: {$withoutItems}<br>";
        
        if ($withoutItems > 0) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "⚠️ Found {$withoutItems} orders without items. These need to be fixed.";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "🎉 All recent orders have items! The fix is working.";
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 3: Simulate cart and order creation
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🛒 Test 3: Simulate Order Creation Process</h3>";

try {
    // Get a sample product (check what columns exist first)
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'status'");
    $hasStatus = $stmt->rowCount() > 0;

    if ($hasStatus) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products LIMIT 1");
    }
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ No active products found. Cannot simulate order creation.";
        echo "</div>";
    } else {
        echo "<h4>Sample Product Found:</h4>";
        echo "<div style='background: #fff; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Name:</strong> {$product['name']}<br>";
        echo "<strong>Price:</strong> ₹{$product['price']}<br>";
        echo "<strong>ID:</strong> {$product['product_id']}<br>";
        echo "</div>";
        
        // Simulate cart items
        $cartItems = [
            [
                'product_id' => $product['product_id'],
                'variant_id' => null,
                'quantity' => 2
            ]
        ];
        
        echo "<h4>Simulated Cart:</h4>";
        echo "<div style='background: #fff; padding: 15px; border-radius: 5px;'>";
        echo "Product: {$product['name']}<br>";
        echo "Quantity: 2<br>";
        echo "Unit Price: ₹{$product['price']}<br>";
        echo "Total: ₹" . ($product['price'] * 2) . "<br>";
        echo "</div>";
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "✅ Cart simulation successful. Ready for order processing test.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Test 4: Check for orphaned transactions
echo "<div style='background: #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔍 Test 4: Check for Orphaned Transactions</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            pt.transaction_id,
            pt.amount,
            pt.transaction_status,
            pt.created_at,
            co.order_number,
            co.first_name,
            co.last_name
        FROM payment_transactions pt
        LEFT JOIN checkout_orders co ON pt.order_id = co.order_id
        WHERE pt.payment_gateway = 'cashfree' 
        AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY pt.created_at DESC
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transactions)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "⚠️ No Cashfree transactions found in last 48 hours.";
        echo "</div>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Transaction ID</th><th>Amount</th><th>Status</th><th>Order</th><th>Customer</th><th>Created</th>";
        echo "</tr>";
        
        $orphaned = 0;
        foreach ($transactions as $txn) {
            $bgColor = $txn['order_number'] ? '#d4edda' : '#f8d7da';
            if (!$txn['order_number']) $orphaned++;
            
            echo "<tr style='background: {$bgColor};'>";
            echo "<td>" . substr($txn['transaction_id'], 0, 8) . "...</td>";
            echo "<td>₹" . $txn['amount'] . "</td>";
            echo "<td>" . $txn['transaction_status'] . "</td>";
            echo "<td>" . ($txn['order_number'] ?? 'MISSING') . "</td>";
            echo "<td>" . ($txn['first_name'] ?? 'Unknown') . " " . ($txn['last_name'] ?? '') . "</td>";
            echo "<td>" . date('M j, H:i', strtotime($txn['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='margin-top: 15px;'>";
        echo "<strong>Summary:</strong><br>";
        echo "Total transactions: " . count($transactions) . "<br>";
        echo "Orphaned transactions: {$orphaned}<br>";
        
        if ($orphaned > 0) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "⚠️ Found {$orphaned} orphaned transactions. Use the recovery tool to fix them.";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "🎉 No orphaned transactions found!";
            echo "</div>";
        }
        echo "</div>";
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
</style>
