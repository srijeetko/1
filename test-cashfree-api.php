<?php
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-handler.php';
require_once 'includes/cashfree-config.php';

header('Content-Type: application/json');

try {
    $cashfreeHandler = new CashfreeHandler($pdo);
    
    // Test order data
    $testOrderData = [
        'order_number' => 'TEST-' . time(),
        'amount' => 100.00,
        'email' => 'test@example.com',
        'phone' => '9999999999',
        'customer_name' => 'Test Customer',
        'user_id' => 'TEST_USER_' . time(),
        'return_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-return.php',
        'notify_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-webhook.php'
    ];
    
    echo json_encode([
        'status' => 'testing',
        'message' => 'Testing Cashfree API with updated configuration...',
        'config' => [
            'api_version' => CASHFREE_API_VERSION,
            'environment' => CASHFREE_API_ENV,
            'base_url' => CASHFREE_API_BASE_URL
        ]
    ]);
    
    // Test order creation
    $result = $cashfreeHandler->createOrder($testOrderData);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Cashfree API is working correctly!',
        'order_data' => $result,
        'test_order' => $testOrderData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'config' => [
            'api_version' => CASHFREE_API_VERSION,
            'environment' => CASHFREE_API_ENV,
            'base_url' => CASHFREE_API_BASE_URL
        ]
    ]);
}
?>
