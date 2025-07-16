<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h1>Cart and Order Testing Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 5px; display: inline-block; cursor: pointer; border: none; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-warning { background: #ffc107; color: black; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_test_products':
            // Add some test products to cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Get some sample products from database
            $stmt = $pdo->query("SELECT product_id, name, price FROM products LIMIT 3");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($products)) {
                foreach ($products as $product) {
                    $cartKey = $product['product_id'] . '_default';
                    $_SESSION['cart'][$cartKey] = [
                        'product_id' => $product['product_id'],
                        'variant_id' => null,
                        'quantity' => 1,
                        'added_at' => date('Y-m-d H:i:s')
                    ];
                }
                echo "<div class='test-section'><p class='success'>✅ Added " . count($products) . " test products to cart</p></div>";
            } else {
                echo "<div class='test-section'><p class='error'>❌ No products found in database</p></div>";
            }
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            echo "<div class='test-section'><p class='success'>✅ Cart cleared</p></div>";
            break;
            
        case 'test_order_processing':
            // Test the order processing logic
            if (empty($_SESSION['cart'])) {
                echo "<div class='test-section'><p class='error'>❌ Cart is empty. Add products first.</p></div>";
                break;
            }
            
            try {
                // Simulate order processing
                $cartItems = $_SESSION['cart'];
                $productIds = [];
                foreach ($cartItems as $item) {
                    if (!in_array($item['product_id'], $productIds)) {
                        $productIds[] = $item['product_id'];
                    }
                }
                
                $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                $sql = "SELECT product_id, name, price FROM products WHERE product_id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($productIds);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($products)) {
                    throw new Exception("No valid products found");
                }
                
                $totalAmount = 0;
                $productLookup = [];
                foreach ($products as $product) {
                    $productLookup[$product['product_id']] = $product;
                }
                
                foreach ($cartItems as $item) {
                    if (isset($productLookup[$item['product_id']])) {
                        $price = floatval($productLookup[$item['product_id']]['price']);
                        $totalAmount += $price * $item['quantity'];
                    }
                }
                
                echo "<div class='test-section'>";
                echo "<p class='success'>✅ Order processing test successful</p>";
                echo "<p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>";
                echo "<p><strong>Products:</strong> " . count($products) . "</p>";
                echo "<p><strong>Cart Items:</strong> " . count($cartItems) . "</p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='test-section'><p class='error'>❌ Order processing test failed: " . $e->getMessage() . "</p></div>";
            }
            break;
    }
}

// Display current cart status
echo "<div class='test-section'>";
echo "<h2>🛒 Current Cart Status</h2>";
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<p class='success'>✅ Cart has " . count($_SESSION['cart']) . " items</p>";
    echo "<table>";
    echo "<tr><th>Product ID</th><th>Variant ID</th><th>Quantity</th><th>Added At</th></tr>";
    foreach ($_SESSION['cart'] as $key => $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['variant_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($item['added_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ Cart is empty</p>";
}
echo "</div>";

// Display available products
echo "<div class='test-section'>";
echo "<h2>📦 Available Products</h2>";
try {
    $stmt = $pdo->query("SELECT product_id, name, price FROM products LIMIT 10");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        echo "<table>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Price</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>₹" . number_format($product['price'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No products found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching products: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test Actions
echo "<div class='test-section'>";
echo "<h2>🔧 Test Actions</h2>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='add_test_products'>";
echo "<button type='submit' class='btn btn-primary'>Add Test Products to Cart</button>";
echo "</form>";

echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='clear_cart'>";
echo "<button type='submit' class='btn btn-danger'>Clear Cart</button>";
echo "</form>";

echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='test_order_processing'>";
echo "<button type='submit' class='btn btn-success'>Test Order Processing</button>";
echo "</form>";
echo "</div>";

// Navigation Links
echo "<div class='test-section'>";
echo "<h2>🔗 Navigation</h2>";
echo "<a href='cart.php' class='btn btn-primary'>View Cart Page</a>";
echo "<a href='checkout.php' class='btn btn-success'>Go to Checkout</a>";
echo "<a href='products.php' class='btn btn-primary'>Products Page</a>";
echo "<a href='test-order-processing.php' class='btn btn-warning'>Full Diagnostic</a>";
echo "</div>";

// Database Status
echo "<div class='test-section'>";
echo "<h2>🗄️ Database Status</h2>";
try {
    // Check key tables
    $tables = ['products', 'checkout_orders', 'order_items', 'payment_transactions'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ Table '$table': " . $result['count'] . " records</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Table '$table': " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Session Info
echo "<div class='test-section'>";
echo "<h2>🔐 Session Info</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Cart Items:</strong> " . (isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0) . "</p>";
echo "<p><strong>User Logged In:</strong> " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "</p>";
echo "</div>";
?>
