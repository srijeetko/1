<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h1>Complete Order Flow Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; cursor: pointer; border: none; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Handle test actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'simulate_order') {
        echo "<div class='section'>";
        echo "<h2>🧪 Simulating Complete Order Process</h2>";
        
        try {
            // Step 1: Create test cart
            $_SESSION['cart'] = [];
            
            // Get a sample product
            $stmt = $pdo->query("SELECT product_id, name, price FROM products LIMIT 1");
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("No products found in database");
            }
            
            // Add to cart
            $cartKey = $product['product_id'] . '_default';
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $product['product_id'],
                'variant_id' => null,
                'quantity' => 2,
                'added_at' => date('Y-m-d H:i:s')
            ];
            
            echo "<p class='success'>✅ Step 1: Added test product to cart</p>";
            echo "<p><strong>Product:</strong> " . htmlspecialchars($product['name']) . " (Qty: 2)</p>";
            
            // Step 2: Simulate order processing
            $pdo->beginTransaction();
            
            // Test data
            $order_id = bin2hex(random_bytes(16));
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($order_id, 0, 6));
            $user_id = null; // Guest order
            $firstName = 'Test';
            $lastName = 'Customer';
            $email = 'test@example.com';
            $phone = '9876543210';
            $address = '123 Test Street, Test Area';
            $city = 'Test City';
            $state = 'Test State';
            $pincode = '123456';
            $totalAmount = $product['price'] * 2;
            $paymentMethod = 'cod';
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO checkout_orders (
                    order_id, order_number, user_id, first_name, last_name, email, phone, 
                    address, city, state, pincode, total_amount, payment_method, 
                    order_status, payment_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
            ");
            
            $stmt->execute([
                $order_id, $order_number, $user_id, $firstName, $lastName, $email, $phone,
                $address, $city, $state, $pincode, $totalAmount, $paymentMethod
            ]);
            
            echo "<p class='success'>✅ Step 2: Order inserted into checkout_orders table</p>";
            echo "<p><strong>Order Number:</strong> $order_number</p>";
            echo "<p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>";
            
            // Step 3: Insert order items
            $order_item_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_item_id, order_id, product_id, product_name, variant_id, 
                    variant_name, quantity, price, total, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $order_item_id, $order_id, $product['product_id'], $product['name'], 
                null, null, 2, $product['price'], $totalAmount
            ]);
            
            echo "<p class='success'>✅ Step 3: Order items inserted</p>";
            
            // Step 4: Create payment transaction
            $transaction_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (
                    transaction_id, order_id, payment_method, amount, currency, 
                    transaction_status, created_at
                ) VALUES (?, ?, 'cod', ?, 'INR', 'pending', NOW())
            ");
            
            $stmt->execute([$transaction_id, $order_id, $totalAmount]);
            
            echo "<p class='success'>✅ Step 4: Payment transaction created</p>";
            
            // Commit transaction
            $pdo->commit();
            
            echo "<p class='success'>🎉 <strong>Order simulation completed successfully!</strong></p>";
            
            // Display order details
            echo "<h3>Order Details:</h3>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Order ID</td><td>$order_id</td></tr>";
            echo "<tr><td>Order Number</td><td>$order_number</td></tr>";
            echo "<tr><td>Customer</td><td>$firstName $lastName</td></tr>";
            echo "<tr><td>Email</td><td>$email</td></tr>";
            echo "<tr><td>Phone</td><td>$phone</td></tr>";
            echo "<tr><td>Address</td><td>$address, $city, $state - $pincode</td></tr>";
            echo "<tr><td>Total Amount</td><td>₹" . number_format($totalAmount, 2) . "</td></tr>";
            echo "<tr><td>Payment Method</td><td>$paymentMethod</td></tr>";
            echo "</table>";
            
            // Clear test cart
            unset($_SESSION['cart']);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            echo "<p class='error'>❌ Order simulation failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
}

// Display current status
echo "<div class='section'>";
echo "<h2>📊 System Status</h2>";

// Check database tables
$tables_status = [];
$required_tables = ['checkout_orders', 'order_items', 'payment_transactions', 'products'];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        $tables_status[$table] = ['status' => 'OK', 'count' => $result['count']];
        echo "<p class='success'>✅ Table '$table': " . $result['count'] . " records</p>";
    } catch (Exception $e) {
        $tables_status[$table] = ['status' => 'ERROR', 'error' => $e->getMessage()];
        echo "<p class='error'>❌ Table '$table': " . $e->getMessage() . "</p>";
    }
}

// Check checkout_orders table structure
echo "<h3>Checkout Orders Table Structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['address', 'city', 'state', 'pincode'];
    $existing_columns = array_column($columns, 'Field');
    
    $all_required_exist = true;
    foreach ($required_columns as $req_col) {
        if (in_array($req_col, $existing_columns)) {
            echo "<p class='success'>✅ Column '$req_col': EXISTS</p>";
        } else {
            echo "<p class='error'>❌ Column '$req_col': MISSING</p>";
            $all_required_exist = false;
        }
    }
    
    if ($all_required_exist) {
        echo "<p class='success'>🎉 All required columns are present!</p>";
    } else {
        echo "<p class='error'>❌ Some required columns are missing. Run the fix script first.</p>";
        echo "<a href='fix-checkout-orders-table.php' class='btn btn-danger'>Fix Table Structure</a>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test actions
echo "<div class='section'>";
echo "<h2>🧪 Test Actions</h2>";

echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='simulate_order'>";
echo "<button type='submit' class='btn btn-success'>Simulate Complete Order</button>";
echo "</form>";

echo "<a href='test-order-processing.php' class='btn btn-primary'>Full Diagnostic</a>";
echo "<a href='checkout.php' class='btn btn-primary'>Try Real Checkout</a>";
echo "<a href='products.php' class='btn btn-primary'>Add Products to Cart</a>";

echo "</div>";

// Recent orders
echo "<div class='section'>";
echo "<h2>📋 Recent Orders</h2>";

try {
    $stmt = $pdo->query("
        SELECT order_number, first_name, last_name, email, total_amount, 
               payment_method, order_status, created_at 
        FROM checkout_orders 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orders)) {
        echo "<table>";
        echo "<tr><th>Order Number</th><th>Customer</th><th>Email</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th></tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['email']) . "</td>";
            echo "<td>₹" . number_format($order['total_amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($order['payment_method']) . "</td>";
            echo "<td>" . htmlspecialchars($order['order_status']) . "</td>";
            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No orders found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching orders: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
