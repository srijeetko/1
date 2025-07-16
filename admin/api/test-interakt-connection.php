<?php
session_start();
require_once '../../includes/db_connection.php';
require_once '../../includes/interakt-handler.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Initialize Interakt handler
    $interaktHandler = new InteraktHandler($pdo);

    // Test API connection by trying to track a test user
    $result = $interaktHandler->trackUser('9999999999', [
        'name' => 'API Test User',
        'email' => 'test@example.com',
        'test' => true
    ]);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'API connection successful',
            'details' => 'Successfully connected to Interakt API'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>