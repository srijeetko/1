<?php
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-handler.php';

try {
    // Get webhook payload
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);
    
    // Verify webhook signature
    $webhookSignature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
    $computedSignature = hash_hmac('sha256', $payload, CASHFREE_SECRET_KEY);
    
    if ($webhookSignature !== $computedSignature) {
        http_response_code(400);
        echo json_encode(['status' => 'ERROR', 'message' => 'Invalid signature']);
        exit;
    }

    $cashfreeHandler = new CashfreeHandler($pdo);
    
    // Handle different event types (updated for new API)
    $eventType = $data['type'] ?? $data['event'] ?? '';
    $orderId = '';

    // Extract order ID from different possible locations
    if (isset($data['data']['order']['order_id'])) {
        $orderId = $data['data']['order']['order_id'];
    } elseif (isset($data['order']['order_id'])) {
        $orderId = $data['order']['order_id'];
    }

    switch ($eventType) {
        case 'PAYMENT_SUCCESS_WEBHOOK':
        case 'ORDER_PAID':
            if (!$orderId) break;

            // Get order details
            $stmt = $pdo->prepare("
                SELECT co.*, pt.transaction_id
                FROM checkout_orders co
                LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
                WHERE co.order_number = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order && $order['payment_status'] !== 'paid') {
                // Update transaction status
                $cashfreeHandler->updateTransactionStatus($order['transaction_id'], 'success', $data);

                // Update order status
                $stmt = $pdo->prepare("
                    UPDATE checkout_orders
                    SET payment_status = 'paid',
                        order_status = 'confirmed'
                    WHERE order_number = ?
                ");
                $stmt->execute([$orderId]);
            }
            break;

        case 'PAYMENT_FAILED_WEBHOOK':
        case 'ORDER_FAILED':
            if (!$orderId) break;

            // Handle failed payment
            $stmt = $pdo->prepare("
                UPDATE checkout_orders
                SET payment_status = 'failed'
                WHERE order_number = ?
            ");
            $stmt->execute([$orderId]);
            break;

        case 'PAYMENT_REFUND_WEBHOOK':
        case 'PAYMENT_REFUNDED':
            if (!$orderId) break;

            // Handle refund
            $refundAmount = $data['data']['refund']['refund_amount'] ?? $data['refund']['refund_amount'] ?? 0;
            $transactionId = $data['data']['payment']['cf_payment_id'] ?? $data['transaction_id'] ?? '';

            if ($transactionId) {
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions
                    SET transaction_status = 'refunded',
                        refund_amount = ?,
                        refund_date = NOW(),
                        updated_at = NOW(),
                        gateway_response = ?
                    WHERE gateway_transaction_id = ? OR transaction_id = ?
                ");
                $stmt->execute([
                    $refundAmount,
                    json_encode($data),
                    $transactionId,
                    $transactionId
                ]);
            }
            break;
    }

    echo json_encode(['status' => 'SUCCESS']);

} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
}
