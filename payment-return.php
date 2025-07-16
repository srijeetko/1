<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/cashfree-handler.php';

try {
    $orderId = $_GET['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception('Order ID not found');
    }

    $cashfreeHandler = new CashfreeHandler($pdo);
    $orderStatus = $cashfreeHandler->getOrderStatus($orderId);

    // Get order details from database
    $stmt = $pdo->prepare("
        SELECT co.*, pt.transaction_id, pt.transaction_status
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
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
