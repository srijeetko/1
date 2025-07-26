<?php
// Improved Payment Return Handler with Better Error Recovery
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

// Enhanced logging function
function logPaymentReturn($message, $context = []) {
    $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - " . $message;
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context);
    }
    error_log($logEntry);
    
    // Also log to specific file
    file_put_contents('logs/payment_returns.log', $logEntry . "\n", FILE_APPEND | LOCK_EX);
}

try {
    $orderId = $_GET['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception('Order ID not found in return URL');
    }

    logPaymentReturn("Payment return initiated", ['order_id' => $orderId]);

    $cashfreeHandler = new CashfreeHandler($pdo);
    
    // Get order status from Cashfree
    try {
        $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
        logPaymentReturn("Cashfree status retrieved", [
            'order_id' => $orderId,
            'status' => $orderStatus
        ]);
    } catch (Exception $e) {
        logPaymentReturn("Failed to get Cashfree status", [
            'order_id' => $orderId,
            'error' => $e->getMessage()
        ]);
        throw new Exception('Unable to verify payment status: ' . $e->getMessage());
    }

    // Get order details from database
    $stmt = $pdo->prepare("
        SELECT co.*, pt.transaction_id, pt.transaction_status, pt.gateway_response
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    logPaymentReturn("Database order lookup", [
        'order_id' => $orderId,
        'found' => $order ? 'yes' : 'no',
        'order_data' => $order ? [
            'internal_order_id' => $order['order_id'],
            'payment_status' => $order['payment_status'],
            'order_status' => $order['order_status']
        ] : null
    ]);

    // Check for both old and new API response formats
    $isPaid = false;
    $paymentDetails = null;
    
    if (isset($orderStatus['order_status']) && $orderStatus['order_status'] === 'PAID') {
        $isPaid = true;
        $paymentDetails = $orderStatus;
    } elseif (isset($orderStatus['order']['order_status']) && $orderStatus['order']['order_status'] === 'PAID') {
        $isPaid = true;
        $paymentDetails = $orderStatus['order'];
    }

    logPaymentReturn("Payment status determined", [
        'order_id' => $orderId,
        'is_paid' => $isPaid,
        'cashfree_status' => $orderStatus['order_status'] ?? $orderStatus['order']['order_status'] ?? 'unknown'
    ]);

    if (!$order) {
        // CRITICAL: Payment successful but order missing from database
        if ($isPaid) {
            logPaymentReturn("CRITICAL: Payment successful but order missing", [
                'order_id' => $orderId,
                'cashfree_response' => $orderStatus
            ]);
            
            // Try to recover by creating missing order
            try {
                // Extract customer details from Cashfree response
                $customerDetails = $orderStatus['customer_details'] ?? $orderStatus['order']['customer_details'] ?? null;
                $orderAmount = $orderStatus['order_amount'] ?? $orderStatus['order']['order_amount'] ?? 0;
                
                if ($customerDetails && $orderAmount > 0) {
                    $pdo->beginTransaction();
                    
                    // Create missing order
                    $order_id = bin2hex(random_bytes(16));
                    $stmt = $pdo->prepare("
                        INSERT INTO checkout_orders (
                            order_id, order_number, first_name, last_name, email, phone,
                            address, city, state, pincode, total_amount, payment_method,
                            order_status, payment_status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 'Address not available', 'City not available', 
                                 'State not available', '000000', ?, 'cashfree', 'confirmed', 'paid', NOW())
                    ");
                    
                    $nameParts = explode(' ', $customerDetails['customer_name'] ?? 'Unknown Customer', 2);
                    $firstName = $nameParts[0] ?? 'Unknown';
                    $lastName = $nameParts[1] ?? 'Customer';
                    
                    $stmt->execute([
                        $order_id, $orderId, $firstName, $lastName,
                        $customerDetails['customer_email'] ?? 'unknown@email.com',
                        $customerDetails['customer_phone'] ?? '0000000000',
                        $orderAmount
                    ]);
                    
                    // Create transaction record
                    $transaction_id = bin2hex(random_bytes(16));
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_transactions (
                            transaction_id, order_id, payment_gateway, payment_method,
                            amount, currency, transaction_status, gateway_response,
                            processed_at, created_at
                        ) VALUES (?, ?, 'cashfree', 'upi', ?, 'INR', 'success', ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $transaction_id, $order_id, $orderAmount, json_encode($orderStatus)
                    ]);
                    
                    $pdo->commit();
                    
                    logPaymentReturn("Successfully recovered missing order", [
                        'original_order_id' => $orderId,
                        'new_internal_order_id' => $order_id,
                        'amount' => $orderAmount
                    ]);
                    
                    // Redirect to success page
                    header('Location: order-success.php?order_id=' . $order_id . '&recovered=1');
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                logPaymentReturn("Failed to recover missing order", [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // If we can't recover, show error
        throw new Exception('Order not found. Please contact support with Order ID: ' . $orderId);
    }

    // Order exists - update status if payment successful
    if ($isPaid && $order['payment_status'] !== 'paid') {
        try {
            $pdo->beginTransaction();
            
            // Update transaction status
            if ($order['transaction_id']) {
                $cashfreeHandler->updateTransactionStatus($order['transaction_id'], 'success', $orderStatus);
            }

            // Update order status
            $stmt = $pdo->prepare("
                UPDATE checkout_orders
                SET payment_status = 'paid',
                    order_status = 'confirmed',
                    updated_at = NOW()
                WHERE order_number = ?
            ");
            $stmt->execute([$orderId]);
            
            $pdo->commit();
            
            logPaymentReturn("Order status updated successfully", [
                'order_id' => $orderId,
                'internal_order_id' => $order['order_id']
            ]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            logPaymentReturn("Failed to update order status", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            // Continue anyway - payment was successful
        }
    }

    // Redirect to appropriate page based on status
    if ($isPaid) {
        logPaymentReturn("Redirecting to success page", [
            'order_id' => $orderId,
            'internal_order_id' => $order['order_id']
        ]);
        header('Location: order-success.php?order_id=' . $order['order_id']);
    } else {
        logPaymentReturn("Redirecting to failure page", [
            'order_id' => $orderId,
            'cashfree_status' => $orderStatus['order_status'] ?? 'unknown'
        ]);
        header('Location: checkout.php?error=payment_failed&order_id=' . $orderId);
    }
    exit;

} catch (Exception $e) {
    logPaymentReturn("Payment return error", [
        'order_id' => $orderId ?? 'unknown',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Redirect with error message
    $errorMsg = urlencode($e->getMessage());
    header('Location: checkout.php?error=' . $errorMsg . '&order_id=' . ($orderId ?? ''));
    exit;
}
?>
