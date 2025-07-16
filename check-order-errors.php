<?php
echo "<h1>Order Processing Error Check</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .error-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .log-entry { background: #fff; border-left: 4px solid #dc3545; padding: 10px; margin: 5px 0; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Check PHP error log
echo "<div class='error-section'>";
echo "<h2>🔍 PHP Error Log Check</h2>";

$error_log_paths = [
    ini_get('error_log'),
    'error_log',
    '../error_log',
    '/tmp/php_errors.log',
    'C:/laragon/tmp/php_errors.log'
];

$found_log = false;
foreach ($error_log_paths as $log_path) {
    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        echo "<p class='success'>✅ Found error log: $log_path</p>";
        
        $log_content = file_get_contents($log_path);
        $lines = explode("\n", $log_content);
        $recent_lines = array_slice($lines, -50); // Last 50 lines
        
        $order_errors = [];
        foreach ($recent_lines as $line) {
            if (stripos($line, 'order') !== false || stripos($line, 'checkout') !== false || stripos($line, 'cart') !== false) {
                $order_errors[] = $line;
            }
        }
        
        if (!empty($order_errors)) {
            echo "<p class='warning'>⚠️ Found " . count($order_errors) . " order-related log entries:</p>";
            foreach ($order_errors as $error) {
                echo "<div class='log-entry'>" . htmlspecialchars($error) . "</div>";
            }
        } else {
            echo "<p class='info'>ℹ️ No order-related errors found in recent log entries</p>";
        }
        
        $found_log = true;
        break;
    }
}

if (!$found_log) {
    echo "<p class='warning'>⚠️ No accessible error log found</p>";
    echo "<p>Error logging might be disabled or logs are in a different location</p>";
}
echo "</div>";

// Check for common order processing issues
echo "<div class='error-section'>";
echo "<h2>🔍 Common Issues Check</h2>";

// Check if required files exist
$required_files = [
    'process-order.php' => 'Order processing script',
    'checkout.php' => 'Checkout page',
    'cart.php' => 'Cart page',
    'order-success.php' => 'Order success page',
    'cart-handler.php' => 'Cart handler script'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $description ($file): EXISTS</p>";
    } else {
        echo "<p class='error'>❌ $description ($file): MISSING</p>";
    }
}

// Check session configuration
echo "<h3>Session Configuration:</h3>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session ID:</strong> " . (session_id() ?: 'Not started') . "</p>";

// Check database connection
echo "<h3>Database Connection:</h3>";
try {
    require_once 'includes/db_connection.php';
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection: OK</p>";
    
    // Check if tables exist
    $tables = ['checkout_orders', 'order_items', 'payment_transactions'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            echo "<p class='success'>✅ Table $table: OK</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Table $table: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test order processing components
echo "<div class='error-section'>";
echo "<h2>🔍 Component Test</h2>";

session_start();

// Test 1: Cart functionality
echo "<h3>Cart Test:</h3>";
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add a test item
$_SESSION['cart']['test_default'] = [
    'product_id' => 'test',
    'variant_id' => null,
    'quantity' => 1,
    'added_at' => date('Y-m-d H:i:s')
];

if (isset($_SESSION['cart']['test_default'])) {
    echo "<p class='success'>✅ Cart session write: OK</p>";
    unset($_SESSION['cart']['test_default']); // Clean up
} else {
    echo "<p class='error'>❌ Cart session write: FAILED</p>";
}

// Test 2: Form processing simulation
echo "<h3>Form Processing Test:</h3>";
$required_post_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'payment_method'];
echo "<p class='info'>Required POST fields for order processing:</p>";
echo "<ul>";
foreach ($required_post_fields as $field) {
    echo "<li>$field</li>";
}
echo "</ul>";

// Test 3: Order ID generation
echo "<h3>Order ID Generation Test:</h3>";
try {
    $test_order_id = bin2hex(random_bytes(16));
    $test_order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($test_order_id, 0, 6));
    echo "<p class='success'>✅ Order ID generation: OK</p>";
    echo "<p><strong>Sample Order ID:</strong> $test_order_id</p>";
    echo "<p><strong>Sample Order Number:</strong> $test_order_number</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Order ID generation failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Recommendations
echo "<div class='error-section'>";
echo "<h2>💡 Recommendations</h2>";
echo "<ol>";
echo "<li><strong>Test the full flow:</strong> Add products to cart → Go to checkout → Fill form → Submit order</li>";
echo "<li><strong>Check browser console:</strong> Look for JavaScript errors during checkout</li>";
echo "<li><strong>Enable error reporting:</strong> Add error_reporting(E_ALL) to see all PHP errors</li>";
echo "<li><strong>Test with different browsers:</strong> Ensure compatibility</li>";
echo "<li><strong>Check database permissions:</strong> Ensure the database user can INSERT/UPDATE</li>";
echo "</ol>";
echo "</div>";

// Quick links
echo "<div class='error-section'>";
echo "<h2>🔗 Quick Actions</h2>";
echo "<a href='test-order-processing.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Full Diagnostic</a>";
echo "<a href='test-cart-and-order.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>Cart Test</a>";
echo "<a href='checkout.php' style='padding: 10px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px; margin: 5px;'>Try Checkout</a>";
echo "</div>";
?>
