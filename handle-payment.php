<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cashfreeHandler = new CashfreeHandler($pdo);
        
        if (isset($_POST['create_order'])) {
            // Create new Cashfree order
            $orderData = [
                'order_number' => $_POST['order_number'],
                'amount' => $_POST['amount'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'customer_name' => $_POST['customer_name'],
                'user_id' => $_POST['user_id'] ?? null,
                'return_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-return.php',
                'notify_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-webhook.php'
            ];

            $cashfreeOrder = $cashfreeHandler->createOrder($orderData);
            echo json_encode(['success' => true, 'order' => $cashfreeOrder]);
            exit;
        }

        if (isset($_POST['verify_payment'])) {
            // Verify payment callback
            $orderId = $_POST['order_id'];
            $orderAmount = $_POST['order_amount'];
            $referenceId = $_POST['reference_id'];
            $txStatus = $_POST['tx_status'];
            $paymentMode = $_POST['payment_mode'];
            $txMsg = $_POST['tx_msg'];
            $txTime = $_POST['tx_time'];
            $signature = $_POST['signature'];
            $transactionId = $_POST['transaction_id'];

            if ($cashfreeHandler->verifySignature($orderId, $orderAmount, $referenceId, $txStatus, $paymentMode, $txMsg, $txTime, $signature)) {
                // Double-check order status with Cashfree
                $orderStatus = $cashfreeHandler->getOrderStatus($orderId);
                
                if ($orderStatus['order_status'] === 'PAID') {
                    // Update transaction status
                    $cashfreeHandler->updateTransactionStatus($transactionId, 'success', [
                        'reference_id' => $referenceId,
                        'payment_mode' => $paymentMode,
                        'order_status' => $orderStatus
                    ]);

                    // Update order status
                    $stmt = $pdo->prepare("
                        UPDATE checkout_orders 
                        SET payment_status = 'paid', 
                            order_status = 'confirmed' 
                        WHERE order_number = ?
                    ");
                    $stmt->execute([$orderId]);

                    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Payment not completed']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid signature']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
