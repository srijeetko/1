<?php
require_once 'db_connection.php';
require_once 'razorpay-config.php';
require_once '../vendor/autoload.php';

use Razorpay\Api\Api;

class RazorpayHandler {
    private $api;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    }

    public function createOrder($amount, $receipt, $notes = []) {
        try {
            $orderData = [
                'receipt' => $receipt,
                'amount' => $amount * 100, // Razorpay expects amount in paise
                'currency' => RAZORPAY_CURRENCY,
                'notes' => $notes
            ];

            $razorpayOrder = $this->api->order->create($orderData);
            return $razorpayOrder;
        } catch (Exception $e) {
            error_log('Razorpay order creation failed: ' . $e->getMessage());
            throw new Exception('Payment initialization failed');
        }
    }

    public function verifyPayment($paymentId, $orderId, $signature) {
        try {
            $attributes = [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature
            ];

            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            error_log('Razorpay signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateTransactionStatus($transactionId, $status, $gatewayResponse = []) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE payment_transactions 
                SET transaction_status = ?,
                    gateway_response = ?,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE transaction_id = ?
            ");
            return $stmt->execute([$status, json_encode($gatewayResponse), $transactionId]);
        } catch (Exception $e) {
            error_log('Failed to update transaction: ' . $e->getMessage());
            return false;
        }
    }
}
