<?php
/**
 * Interakt WhatsApp Webhook Handler
 * Receives and processes webhooks from Interakt WhatsApp API
 * Alpha Nutrition Customer Support System
 */

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);

require_once '../includes/db_connection.php';
require_once '../includes/interakt-handler.php';

// Function to send JSON response
function sendResponse($success, $message = '', $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

// Function to log webhook for debugging
function logWebhookRequest($method, $headers, $body) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $method,
        'headers' => $headers,
        'body' => $body,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];

    error_log("Interakt Webhook: " . json_encode($logData));
}

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Only POST requests are allowed');
    }

    // Get request headers
    $headers = getallheaders();

    // Get raw POST data
    $rawInput = file_get_contents('php://input');

    // Log the webhook request for debugging
    logWebhookRequest($_SERVER['REQUEST_METHOD'], $headers, $rawInput);

    // Validate that we have data
    if (empty($rawInput)) {
        sendResponse(false, 'No data received');
    }

    // Decode JSON data
    $webhookData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(false, 'Invalid JSON data: ' . json_last_error_msg());
    }

    // Basic validation
    if (!is_array($webhookData)) {
        sendResponse(false, 'Webhook data must be an array');
    }

    // Initialize Interakt handler
    $interaktHandler = new InteraktHandler($pdo);

    // Process the webhook
    $result = $interaktHandler->processWebhook($webhookData);

    if ($result['success']) {
        sendResponse(true, 'Webhook processed successfully', $result);
    } else {
        sendResponse(false, 'Webhook processing failed: ' . $result['error']);
    }

} catch (Exception $e) {
    error_log("Interakt webhook error: " . $e->getMessage());
    sendResponse(false, 'Internal server error: ' . $e->getMessage());
}
?>