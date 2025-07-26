<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-config.php';
require_once 'includes/cashfree-handler.php';

try {
    $orderId = $_GET['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception('Order ID not found');
    }

    // Enhanced logging for debugging
    error_log("Payment return - Order ID received: " . $orderId);
    $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - Order ID received: $orderId\n";
    file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);

    $cashfreeHandler = new CashfreeHandler($pdo);

    // Get order status with error handling
    try {
        $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
        error_log("Payment return - Cashfree status: " . json_encode($orderStatus));
        $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - Cashfree status retrieved: " . json_encode($orderStatus) . "\n";
        file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Payment return - Failed to get Cashfree status: " . $e->getMessage());
        $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - ERROR - Failed to get Cashfree status: " . $e->getMessage() . "\n";
        file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);
        throw new Exception('Unable to verify payment status: ' . $e->getMessage());
    }

    // Get order details from database
    $stmt = $pdo->prepare("
        SELECT co.*, pt.transaction_id, pt.transaction_status
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enhanced database query result logging
    error_log("Payment return - Order found in DB: " . ($order ? 'Yes' : 'No'));
    $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - Database lookup - Order found: " . ($order ? 'Yes' : 'No') . "\n";
    file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);

    if (!$order) {
        // CRITICAL: Check if payment was successful but order missing
        $isPaidCheck = false;
        if (isset($orderStatus['order_status']) && $orderStatus['order_status'] === 'PAID') {
            $isPaidCheck = true;
        } elseif (isset($orderStatus['order']['order_status']) && $orderStatus['order']['order_status'] === 'PAID') {
            $isPaidCheck = true;
        }

        if ($isPaidCheck) {
            // Payment successful but order missing - try to recover
            error_log("CRITICAL: Payment successful but order missing - Order ID: $orderId");
            $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - CRITICAL - Payment successful but order missing: $orderId\n";
            file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);

            // Try to extract customer details and create missing order
            try {
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

                    error_log("Successfully recovered missing order - Original: $orderId, New: $order_id");
                    $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - RECOVERY SUCCESS - Original: $orderId, New: $order_id\n";
                    file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);

                    // Redirect to success page with recovery notice
                    header('Location: order-success.php?order_id=' . $order_id . '&recovered=1');
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Failed to recover missing order: " . $e->getMessage());
                $logEntry = date('Y-m-d H:i:s') . " - PAYMENT_RETURN - RECOVERY FAILED - " . $e->getMessage() . "\n";
                file_put_contents('logs/payment_returns.log', $logEntry, FILE_APPEND | LOCK_EX);
            }
        }

        throw new Exception('Order not found. Please contact support with Order ID: ' . $orderId);
    }

    // Check for both old and new API response formats
    $isPaid = false;
    if (isset($orderStatus['order_status']) && $orderStatus['order_status'] === 'PAID') {
        $isPaid = true;
    } elseif (isset($orderStatus['order']['order_status']) && $orderStatus['order']['order_status'] === 'PAID') {
        $isPaid = true;
    }

    if ($isPaid && $order['payment_status'] !== 'paid') {
        // Update transaction status
        $cashfreeHandler->updateTransactionStatus($order['transaction_id'], 'success', $orderStatus);

        // Update order status
        $stmt = $pdo->prepare("
            UPDATE checkout_orders
            SET payment_status = 'paid',
                order_status = 'confirmed'
            WHERE order_number = ?
        ");
        $stmt->execute([$orderId]);
    }

    // Redirect to appropriate page based on status
    if ($isPaid) {
        header('Location: order-success.php?order_id=' . $order['order_id']);
    } else {
        header('Location: checkout.php?error=payment_failed');
    }
    exit;

} catch (Exception $e) {
    error_log('Payment return error: ' . $e->getMessage());
    header('Location: checkout.php?error=' . urlencode($e->getMessage()));
    exit;
}
