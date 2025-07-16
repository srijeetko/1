<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h1>Order Processing System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>🔍 Test 1: Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}
echo "</div>";

// Test 2: Check Required Tables
echo "<div class='test-section'>";
echo "<h2>🔍 Test 2: Required Tables Check</h2>";
$required_tables = ['checkout_orders', 'order_items', 'payment_transactions', 'products', 'product_variants'];
$missing_tables = [];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Table '$table': EXISTS</p>";
        } else {
            echo "<p class='error'>❌ Table '$table': MISSING</p>";
            $missing_tables[] = $table;
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<p class='warning'>⚠️ Missing tables detected. <a href='setup_order_tables.sql' class='btn btn-warning'>Run Setup SQL</a></p>";
}
echo "</div>";

// Test 3: Check Table Structure
echo "<div class='test-section'>";
echo "<h2>🔍 Test 3: Table Structure Check</h2>";
try {
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['order_id', 'order_number', 'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'total_amount', 'payment_method', 'payment_status', 'order_status'];
    $existing_columns = array_column($columns, 'Field');
    
    echo "<p class='info'>📋 checkout_orders table columns:</p>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Status</th></tr>";
    
    foreach ($required_columns as $req_col) {
        $exists = in_array($req_col, $existing_columns);
        $status = $exists ? "<span class='success'>✅ EXISTS</span>" : "<span class='error'>❌ MISSING</span>";
        $type = '';
        
        if ($exists) {
            foreach ($columns as $col) {
                if ($col['Field'] === $req_col) {
                    $type = $col['Type'];
                    break;
                }
            }
        }
        
        echo "<tr><td>$req_col</td><td>$type</td><td>$status</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking table structure: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Cart Session Test
echo "<div class='test-section'>";
echo "<h2>🔍 Test 4: Cart Session Test</h2>";
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<p class='success'>✅ Cart session exists with " . count($_SESSION['cart']) . " items</p>";
    echo "<table>";
    echo "<tr><th>Cart Key</th><th>Product ID</th><th>Variant ID</th><th>Quantity</th></tr>";
    foreach ($_SESSION['cart'] as $key => $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($item['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['variant_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ No cart session found</p>";
    echo "<p><a href='products.php' class='btn btn-primary'>Add Products to Cart</a></p>";
}
echo "</div>";

// Test 5: Product Data Validation
echo "<div class='test-section'>";
echo "<h2>🔍 Test 5: Product Data Validation</h2>";
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = [];
    foreach ($_SESSION['cart'] as $item) {
        if (!in_array($item['product_id'], $productIds)) {
            $productIds[] = $item['product_id'];
        }
    }
    
    if (!empty($productIds)) {
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "SELECT product_id, name, price FROM products WHERE product_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='info'>📋 Products in cart validation:</p>";
        echo "<table>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Status</th></tr>";
        
        foreach ($productIds as $pid) {
            $found = false;
            foreach ($products as $product) {
                if ($product['product_id'] === $pid) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                    echo "<td>₹" . number_format($product['price'], 2) . "</td>";
                    echo "<td><span class='success'>✅ VALID</span></td>";
                    echo "</tr>";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($pid) . "</td>";
                echo "<td colspan='2'>Product not found</td>";
                echo "<td><span class='error'>❌ INVALID</span></td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
} else {
    echo "<p class='warning'>⚠️ No cart items to validate</p>";
}
echo "</div>";

// Test 6: Order Processing Simulation
echo "<div class='test-section'>";
echo "<h2>🔍 Test 6: Order Processing Simulation</h2>";
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<p class='info'>🧪 Simulating order processing logic...</p>";
    
    try {
        // Simulate the order processing logic from process-order.php
        $cartItems = $_SESSION['cart'];
        $productIds = [];
        foreach ($cartItems as $item) {
            if (!in_array($item['product_id'], $productIds)) {
                $productIds[] = $item['product_id'];
            }
        }
        
        // Get product details
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "SELECT p.product_id, p.name, p.price FROM products p WHERE p.product_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($products)) {
            throw new Exception("No valid products found in cart.");
        }
        
        // Calculate order total
        $totalAmount = 0;
        $orderItems = [];
        $productLookup = [];
        
        foreach ($products as $product) {
            $productLookup[$product['product_id']] = $product;
        }
        
        foreach ($cartItems as $cartKey => $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            
            if (!isset($productLookup[$productId])) {
                continue;
            }
            
            $product = $productLookup[$productId];
            $price = floatval($product['price']);
            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal
            ];
        }
        
        echo "<p class='success'>✅ Order calculation successful</p>";
        echo "<p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>";
        echo "<p><strong>Order Items:</strong> " . count($orderItems) . "</p>";
        
        echo "<table>";
        echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr>";
        foreach ($orderItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>₹" . number_format($item['price'], 2) . "</td>";
            echo "<td>₹" . number_format($item['total'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Order processing simulation failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Cannot simulate without cart items</p>";
}
echo "</div>";

// Test 7: File Permissions and Access
echo "<div class='test-section'>";
echo "<h2>🔍 Test 7: File Access Check</h2>";
$critical_files = ['process-order.php', 'checkout.php', 'order-success.php', 'cart-handler.php'];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p class='success'>✅ File '$file': Accessible</p>";
        } else {
            echo "<p class='error'>❌ File '$file': Not readable</p>";
        }
    } else {
        echo "<p class='error'>❌ File '$file': Not found</p>";
    }
}
echo "</div>";

// Action Buttons
echo "<div class='test-section'>";
echo "<h2>🔧 Quick Actions</h2>";
echo "<a href='cart.php' class='btn btn-primary'>View Cart</a>";
echo "<a href='checkout.php' class='btn btn-success'>Go to Checkout</a>";
echo "<a href='products.php' class='btn btn-primary'>Add Products</a>";
echo "<a href='debug-order-total.php' class='btn btn-warning'>Debug Order Total</a>";
echo "<a href='check-table-structure.php' class='btn btn-warning'>Check Tables</a>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📋 Summary</h2>";
echo "<p>This diagnostic script checks the key components of the order processing system.</p>";
echo "<p>If you see any ❌ errors above, those need to be fixed for proper order processing.</p>";
echo "<p>If everything shows ✅, the order processing system should be working correctly.</p>";
echo "</div>";
?>
