<?php
session_start();
require_once 'includes/db_connection.php';

$order_id = $_GET['order_id'] ?? 'fb4d70c04a1b5c1538da6cbc6aea7ffb';

echo "<h1>🔍 Debug Order Details</h1>";
echo "<p><strong>Order ID:</strong> $order_id</p>";

// Check if order exists
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>1. Check Order Exists</h3>";

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

// Check order items
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>2. Check Order Items</h3>";

try {
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orderItems)) {
        echo "<p>✅ Found " . count($orderItems) . " order items:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Product Name</th><th>Quantity</th><th>Price</th><th>Total</th>";
        echo "</tr>";
        
        foreach ($orderItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
            echo "<td>₹" . htmlspecialchars($item['price']) . "</td>";
            echo "<td>₹" . htmlspecialchars($item['total']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No order items found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check payment_transactions table
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>3. Check Payment Transactions Table</h3>";

try {
    $stmt = $pdo->prepare("DESCRIBE payment_transactions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ payment_transactions table exists with " . count($columns) . " columns:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>Column</th><th>Type</th><th>Null</th><th>Key</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if there are payment records for this order
    $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($payments)) {
        echo "<p>✅ Found " . count($payments) . " payment records:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Transaction ID</th><th>Status</th><th>Gateway</th><th>Amount</th>";
        echo "</tr>";
        
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($payment['transaction_id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($payment['transaction_status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($payment['payment_gateway'] ?? 'N/A') . "</td>";
            echo "<td>₹" . htmlspecialchars($payment['amount'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No payment records found for this order.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ payment_transactions table doesn't exist or error: " . $e->getMessage() . "</p>";
    echo "<p>This might be why order-details.php is failing.</p>";
}
echo "</div>";

// Test the original query
echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>4. Test Original Query</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            co.*,
            pt.transaction_id,
            pt.transaction_status,
            pt.gateway_transaction_id,
            pt.payment_gateway
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p>✅ Original query works!</p>";
        echo "<p><strong>Order Number:</strong> " . ($result['order_number'] ?? 'N/A') . "</p>";
        echo "<p><strong>Customer:</strong> " . ($result['first_name'] ?? 'N/A') . " " . ($result['last_name'] ?? 'N/A') . "</p>";
        echo "<p><strong>Total:</strong> ₹" . ($result['total_amount'] ?? 'N/A') . "</p>";
        echo "<p><strong>Payment Status:</strong> " . ($result['payment_status'] ?? 'N/A') . "</p>";
    } else {
        echo "<p>❌ Original query failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Original query error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> Need to fix the query or create missing table.</p>";
}
echo "</div>";

// Suggest fixes
echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔧 Suggested Fixes</h3>";

if (isset($order) && $order) {
    echo "<p>✅ Order exists, so the main issue is likely the payment_transactions table.</p>";
    
    echo "<h4>Option 1: Remove payment_transactions join</h4>";
    echo "<p>Simplify the query to only use checkout_orders table.</p>";
    
    echo "<h4>Option 2: Create payment_transactions table</h4>";
    echo "<p>Create the missing table to store payment information.</p>";
    
    echo "<h4>Option 3: Use existing payment fields</h4>";
    echo "<p>Use payment_method and payment_status from checkout_orders table.</p>";
} else {
    echo "<p>❌ Order doesn't exist. Check if the order_id is correct.</p>";
}

echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
</style>
