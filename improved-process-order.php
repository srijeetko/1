<?php
// Improved Order Processing with Better Error Handling
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';

// Enhanced error logging function
function logOrderError($message, $context = []) {
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context);
    }
    error_log($logEntry);
    
    // Also log to a specific file for order issues
    file_put_contents('logs/order_errors.log', $logEntry . "\n", FILE_APPEND | LOCK_EX);
}

// Enhanced order processing with transaction safety
function processOrderSafely($pdo, $orderData, $paymentMethod) {
    try {
        // Start database transaction
        $pdo->beginTransaction();
        
        // Generate order ID and order number
        $order_id = bin2hex(random_bytes(16));
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($order_id, 0, 6));
        
        logOrderError("Starting order creation", [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'payment_method' => $paymentMethod,
            'amount' => $orderData['total_amount']
        ]);
        
        // Insert order into checkout_orders table
        $stmt = $pdo->prepare("
            INSERT INTO checkout_orders (
                order_id, order_number, user_id, first_name, last_name, email, phone,
                address, city, state, pincode, total_amount, payment_method,
                order_status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
        ");

        $result = $stmt->execute([
            $order_id, $order_number, $orderData['user_id'], $orderData['first_name'], 
            $orderData['last_name'], $orderData['email'], $orderData['phone'],
            $orderData['address'], $orderData['city'], $orderData['state'], 
            $orderData['pincode'], $orderData['total_amount'], $paymentMethod
        ]);

        if (!$result) {
            throw new Exception('Failed to create order in database');
        }
        
        logOrderError("Order created in database successfully", ['order_id' => $order_id]);
        
        // Handle payment processing based on method
        if ($paymentMethod === 'cod') {
            // Cash on Delivery - Mark as confirmed
            $stmt = $pdo->prepare("UPDATE checkout_orders SET order_status = 'confirmed' WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Create a transaction record for COD
            $transaction_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (
                    transaction_id, order_id, payment_method, amount, currency, 
                    transaction_status, created_at
                ) VALUES (?, ?, 'cod', ?, 'INR', 'pending', NOW())
            ");
            $stmt->execute([$transaction_id, $order_id, $orderData['total_amount']]);
            
            // Commit transaction for COD
            $pdo->commit();
            
            return [
                'success' => true,
                'payment_required' => false,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'redirect_url' => 'order-success.php?order_id=' . $order_id
            ];
            
        } else if ($paymentMethod === 'cashfree') {
            // Create Cashfree order with enhanced error handling
            require_once 'includes/cashfree-handler.php';
            $cashfreeHandler = new CashfreeHandler($pdo);

            try {
                // Create transaction record FIRST
                $transaction_id = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("
                    INSERT INTO payment_transactions (
                        transaction_id, order_id, payment_gateway, payment_method,
                        amount, currency, transaction_status, created_at
                    ) VALUES (?, ?, 'cashfree', 'upi', ?, 'INR', 'pending', NOW())
                ");
                $stmt->execute([$transaction_id, $order_id, $orderData['total_amount']]);

                logOrderError("Transaction record created", [
                    'transaction_id' => $transaction_id,
                    'order_id' => $order_id
                ]);

                // Prepare order data for Cashfree
                $cashfreeOrderData = [
                    'order_number' => $order_number,
                    'amount' => $orderData['total_amount'],
                    'email' => $orderData['email'],
                    'phone' => $orderData['phone'],
                    'customer_name' => $orderData['first_name'] . ' ' . $orderData['last_name'],
                    'user_id' => $orderData['user_id'],
                    'return_url' => CASHFREE_BASE_URL . '/payment-return.php',
                    'notify_url' => CASHFREE_BASE_URL . '/payment-webhook.php'
                ];

                logOrderError("Creating Cashfree order", $cashfreeOrderData);

                // Create Cashfree order
                $cashfreeOrder = $cashfreeHandler->createOrder($cashfreeOrderData);

                if (!$cashfreeOrder || !isset($cashfreeOrder['payment_session_id'])) {
                    throw new Exception('Cashfree order creation failed - no payment session ID');
                }

                // Update transaction with Cashfree order details
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET gateway_transaction_id = ?, gateway_response = ?
                    WHERE transaction_id = ?
                ");
                $stmt->execute([
                    $cashfreeOrder['order_id'] ?? $order_number,
                    json_encode($cashfreeOrder),
                    $transaction_id
                ]);

                logOrderError("Cashfree order created successfully", [
                    'cashfree_order_id' => $cashfreeOrder['order_id'] ?? 'unknown',
                    'payment_session_id' => $cashfreeOrder['payment_session_id']
                ]);

                // Commit transaction - everything successful
                $pdo->commit();

                return [
                    'success' => true,
                    'payment_required' => true,
                    'orderData' => [
                        'payment_session_id' => $cashfreeOrder['payment_session_id'],
                        'order_id' => $cashfreeOrder['order_id'],
                        'amount' => $orderData['total_amount'],
                        'transaction_id' => $transaction_id,
                        'order_id_internal' => $order_id
                    ]
                ];

            } catch (Exception $e) {
                logOrderError("Cashfree order creation failed", [
                    'error' => $e->getMessage(),
                    'order_id' => $order_id,
                    'transaction_id' => $transaction_id ?? 'not_created'
                ]);
                
                // Rollback database changes
                $pdo->rollBack();
                
                throw new Exception('Payment initialization failed: ' . $e->getMessage());
            }
        }
        
        // If we reach here, unsupported payment method
        $pdo->rollBack();
        throw new Exception('Unsupported payment method: ' . $paymentMethod);
        
    } catch (Exception $e) {
        // Rollback any database changes
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        logOrderError("Order processing failed", [
            'error' => $e->getMessage(),
            'payment_method' => $paymentMethod,
            'order_data' => $orderData
        ]);
        
        throw $e;
    }
}

// Main processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Prepare order data
        $orderData = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
            'address' => trim($_POST['address']),
            'city' => trim($_POST['city']),
            'state' => trim($_POST['state']),
            'pincode' => trim($_POST['pincode']),
            'total_amount' => floatval($_POST['total_amount'] ?? 0)
        ];
        
        $paymentMethod = $_POST['payment_method'];
        
        // Validate total amount
        if ($orderData['total_amount'] <= 0) {
            throw new Exception('Invalid order amount');
        }
        
        // Process order safely
        $result = processOrderSafely($pdo, $orderData, $paymentMethod);
        
        // Clear any output buffer to prevent JSON corruption
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Send response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
        
    } catch (Exception $e) {
        // Clear any output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Send error response
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
} else {
    // Invalid request method
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}
?>
