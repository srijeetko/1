<?php
require_once 'db_connection.php';
require_once 'cashfree-config.php';

class CashfreeHandler {
    private $pdo;
    private $headers;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->headers = [
            'x-client-id: ' . CASHFREE_APP_ID,
            'x-client-secret: ' . CASHFREE_SECRET_KEY,
            'x-api-version: ' . CASHFREE_API_VERSION,
            'Content-Type: application/json'
        ];
    }

    public function createOrder($orderData) {
        try {
            // Updated payload format for API version 2023-08-01
            $payload = [
                'order_amount' => floatval($orderData['amount']),
                'order_currency' => CASHFREE_CURRENCY,
                'customer_details' => [
                    'customer_id' => $orderData['user_id'] ?? 'GUEST-' . time(),
                    'customer_name' => $orderData['customer_name'],
                    'customer_email' => $orderData['email'],
                    'customer_phone' => $orderData['phone']
                ],
                'order_meta' => [
                    'return_url' => $orderData['return_url'] . '?order_id={order_id}'
                ]
            ];

            // Add order_id if provided (optional in new API)
            if (isset($orderData['order_number'])) {
                $payload['order_id'] = $orderData['order_number'];
            }

            $ch = curl_init(CASHFREE_API_BASE_URL . '/orders');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception('CURL Error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                $errorResponse = json_decode($response, true);
                $errorMessage = isset($errorResponse['message']) ? $errorResponse['message'] : $response;
                throw new Exception('Cashfree API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
            }

            $responseData = json_decode($response, true);
            if (!$responseData) {
                throw new Exception('Invalid JSON response from Cashfree');
            }

            return $responseData;
        } catch (Exception $e) {
            error_log('Cashfree order creation failed: ' . $e->getMessage());
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    public function verifySignature($orderId, $orderAmount, $referenceId, $txStatus, $paymentMode, $txMsg, $txTime, $signature) {
        try {
            $data = $orderId . $orderAmount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
            $hash_hmac = hash_hmac('sha256', $data, CASHFREE_SECRET_KEY, true);
            $computedSignature = base64_encode($hash_hmac);
            
            return $signature === $computedSignature;
        } catch (Exception $e) {
            error_log('Cashfree signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getOrderStatus($orderId) {
        try {
            $ch = curl_init(CASHFREE_API_BASE_URL . '/orders/' . $orderId);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception('CURL Error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                $errorResponse = json_decode($response, true);
                $errorMessage = isset($errorResponse['message']) ? $errorResponse['message'] : $response;
                throw new Exception('Cashfree API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
            }

            $responseData = json_decode($response, true);
            if (!$responseData) {
                throw new Exception('Invalid JSON response from Cashfree');
            }

            return $responseData;
        } catch (Exception $e) {
            error_log('Failed to get Cashfree order status: ' . $e->getMessage());
            throw new Exception('Failed to check payment status: ' . $e->getMessage());
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
