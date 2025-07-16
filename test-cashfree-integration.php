<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-handler.php';
require_once 'includes/cashfree-config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashfree Integration Test - Alpha Nutrition</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .config-item:last-child {
            border-bottom: none;
        }
        .test-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
        }
        .test-button:hover {
            background-color: #0056b3;
        }
        .code-block {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            margin: 10px 0;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success { background-color: #28a745; }
        .status-error { background-color: #dc3545; }
        .status-warning { background-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Cashfree Payment Integration Test</h1>
        <p>This page tests the Cashfree payment integration to ensure everything is working properly.</p>

        <?php
        $testResults = [];
        $overallStatus = true;

        // Test 1: Configuration Check
        echo '<div class="test-section">';
        echo '<h3>1. Configuration Check</h3>';
        
        $configTests = [
            'CASHFREE_APP_ID' => CASHFREE_APP_ID,
            'CASHFREE_SECRET_KEY' => substr(CASHFREE_SECRET_KEY, 0, 10) . '...',
            'CASHFREE_API_ENV' => CASHFREE_API_ENV,
            'CASHFREE_CURRENCY' => CASHFREE_CURRENCY,
            'CASHFREE_API_BASE_URL' => CASHFREE_API_BASE_URL
        ];

        foreach ($configTests as $key => $value) {
            echo '<div class="config-item">';
            echo '<strong>' . $key . ':</strong>';
            if (!empty($value)) {
                echo '<span class="success">✓ ' . htmlspecialchars($value) . '</span>';
            } else {
                echo '<span class="error">✗ Not configured</span>';
                $overallStatus = false;
            }
            echo '</div>';
        }
        echo '</div>';

        // Test 2: Database Connection
        echo '<div class="test-section">';
        echo '<h3>2. Database Connection & Tables</h3>';
        
        try {
            // Check payment_transactions table
            $stmt = $pdo->query("DESCRIBE payment_transactions");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = ['transaction_id', 'order_id', 'payment_gateway', 'payment_method', 'amount', 'currency', 'transaction_status'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo '<div class="success">✓ payment_transactions table exists with all required columns</div>';
            } else {
                echo '<div class="error">✗ Missing columns in payment_transactions: ' . implode(', ', $missingColumns) . '</div>';
                $overallStatus = false;
            }
            
            // Check checkout_orders table
            $stmt = $pdo->query("DESCRIBE checkout_orders");
            $orderColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredOrderColumns = ['order_id', 'order_number', 'payment_method', 'payment_status'];
            $missingOrderColumns = array_diff($requiredOrderColumns, $orderColumns);
            
            if (empty($missingOrderColumns)) {
                echo '<div class="success">✓ checkout_orders table exists with all required columns</div>';
            } else {
                echo '<div class="error">✗ Missing columns in checkout_orders: ' . implode(', ', $missingOrderColumns) . '</div>';
                $overallStatus = false;
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $overallStatus = false;
        }
        echo '</div>';

        // Test 3: Cashfree Handler Class
        echo '<div class="test-section">';
        echo '<h3>3. Cashfree Handler Class</h3>';
        
        try {
            $cashfreeHandler = new CashfreeHandler($pdo);
            echo '<div class="success">✓ CashfreeHandler class instantiated successfully</div>';
            
            // Check if all required methods exist
            $requiredMethods = ['createOrder', 'verifySignature', 'getOrderStatus', 'updateTransactionStatus'];
            $classMethods = get_class_methods($cashfreeHandler);
            $missingMethods = array_diff($requiredMethods, $classMethods);
            
            if (empty($missingMethods)) {
                echo '<div class="success">✓ All required methods exist in CashfreeHandler</div>';
            } else {
                echo '<div class="error">✗ Missing methods: ' . implode(', ', $missingMethods) . '</div>';
                $overallStatus = false;
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Failed to instantiate CashfreeHandler: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $overallStatus = false;
        }
        echo '</div>';

        // Test 4: API Connectivity
        echo '<div class="test-section">';
        echo '<h3>4. Cashfree API Connectivity</h3>';
        
        try {
            // Test API connectivity with a simple request
            $headers = [
                'x-client-id: ' . CASHFREE_APP_ID,
                'x-client-secret: ' . CASHFREE_SECRET_KEY,
                'x-api-version: ' . CASHFREE_API_VERSION,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init(CASHFREE_API_BASE_URL . '/orders');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                echo '<div class="error">✗ CURL Error: ' . htmlspecialchars($curlError) . '</div>';
                $overallStatus = false;
            } else if ($httpCode == 401) {
                echo '<div class="error">✗ Authentication failed (HTTP 401) - Check your API credentials</div>';
                $overallStatus = false;
            } else if ($httpCode == 200 || $httpCode == 400) {
                echo '<div class="success">✓ API connectivity successful (HTTP ' . $httpCode . ')</div>';
                echo '<div class="info">Note: HTTP 400 is expected for GET request without parameters</div>';
            } else {
                echo '<div class="warning">⚠ Unexpected HTTP response: ' . $httpCode . '</div>';
                echo '<div class="code-block">Response: ' . htmlspecialchars(substr($response, 0, 500)) . '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ API connectivity test failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $overallStatus = false;
        }
        echo '</div>';

        // Test 5: File Dependencies
        echo '<div class="test-section">';
        echo '<h3>5. File Dependencies</h3>';
        
        $requiredFiles = [
            'includes/cashfree-config.php',
            'includes/cashfree-handler.php',
            'includes/db_connection.php',
            'process-order.php',
            'payment-webhook.php',
            'payment-return.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo '<div class="success">✓ ' . $file . ' exists</div>';
            } else {
                echo '<div class="error">✗ ' . $file . ' missing</div>';
                $overallStatus = false;
            }
        }
        echo '</div>';

        // Test 6: JavaScript Integration
        echo '<div class="test-section">';
        echo '<h3>6. Frontend JavaScript Integration</h3>';
        
        if (file_exists('checkout.php')) {
            $checkoutContent = file_get_contents('checkout.php');
            
            $jsChecks = [
                'Cashfree SDK v3' => strpos($checkoutContent, 'cashfree.js') !== false,
                'initCashfree function' => strpos($checkoutContent, 'function initCashfree') !== false,
                'Cashfree initialization' => strpos($checkoutContent, 'Cashfree({') !== false,
                'Checkout method' => strpos($checkoutContent, 'cashfree.checkout') !== false
            ];
            
            foreach ($jsChecks as $check => $result) {
                if ($result) {
                    echo '<div class="success">✓ ' . $check . ' found</div>';
                } else {
                    echo '<div class="error">✗ ' . $check . ' missing</div>';
                    $overallStatus = false;
                }
            }
        } else {
            echo '<div class="error">✗ checkout.php file not found</div>';
            $overallStatus = false;
        }
        echo '</div>';

        // Overall Status
        echo '<div class="test-section">';
        echo '<h3>Overall Status</h3>';
        if ($overallStatus) {
            echo '<div class="success">';
            echo '<span class="status-indicator status-success"></span>';
            echo '<strong>✓ Cashfree Integration appears to be working correctly!</strong>';
            echo '</div>';
            echo '<div class="info">All tests passed. You can proceed with testing actual payments.</div>';
        } else {
            echo '<div class="error">';
            echo '<span class="status-indicator status-error"></span>';
            echo '<strong>✗ Issues found with Cashfree Integration</strong>';
            echo '</div>';
            echo '<div class="warning">Please fix the issues mentioned above before using Cashfree payments.</div>';
        }
        echo '</div>';
        ?>

        <div class="test-section">
            <h3>Test Payment (Sandbox)</h3>
            <p>Click the button below to test a sample payment with Cashfree sandbox:</p>
            <button class="test-button" onclick="testPayment()">Test Sample Payment</button>
            <div id="payment-result"></div>
        </div>
    </div>

    <script>
        function testPayment() {
            const resultDiv = document.getElementById('payment-result');
            resultDiv.innerHTML = '<div class="info">Initiating test payment...</div>';
            
            // Sample order data for testing
            const testOrderData = {
                create_order: true,
                order_number: 'TEST-' + Date.now(),
                amount: 100.00,
                email: 'test@example.com',
                phone: '9999999999',
                customer_name: 'Test Customer',
                user_id: null
            };
            
            fetch('handle-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(testOrderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="success">✓ Test order created successfully!</div>' +
                                        '<div class="code-block">' + JSON.stringify(data, null, 2) + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="error">✗ Test failed: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">✗ Network error: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>
