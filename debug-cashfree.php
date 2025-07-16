<?php
// Debug script to test Cashfree configuration
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

echo "<h2>Cashfree Configuration Debug</h2>";

echo "<h3>Configuration Values:</h3>";
echo "App ID: " . CASHFREE_APP_ID . "<br>";
echo "Secret Key: " . substr(CASHFREE_SECRET_KEY, 0, 10) . "..." . "<br>";
echo "Environment: " . CASHFREE_API_ENV . "<br>";
echo "API Base URL: " . CASHFREE_API_BASE_URL . "<br>";
echo "API Version: " . CASHFREE_API_VERSION . "<br>";

echo "<h3>Testing Order Creation:</h3>";

try {
    $cashfreeHandler = new CashfreeHandler($pdo);
    
    $testOrderData = [
        'order_number' => 'TEST-' . time(),
        'amount' => 100.00,
        'email' => 'test@example.com',
        'phone' => '9999999999',
        'customer_name' => 'Test Customer',
        'user_id' => 'TEST-USER',
        'return_url' => 'http://localhost/payment-return.php',
        'notify_url' => 'http://localhost/payment-webhook.php'
    ];
    
    echo "Creating test order with data:<br>";
    echo "<pre>" . print_r($testOrderData, true) . "</pre>";
    
    $result = $cashfreeHandler->createOrder($testOrderData);
    
    echo "<h4>Success! Order created:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<h4>Error:</h4>";
    echo "<div style='color: red;'>" . $e->getMessage() . "</div>";
}
?>
