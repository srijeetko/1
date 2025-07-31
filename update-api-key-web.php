<?php
/**
 * Web-based Interakt API Key Update
 * Visit this page to update the Interakt API key
 */

// Security check - only allow from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('API key update only allowed from localhost');
}

require_once 'includes/db_connection.php';

$updateComplete = false;
$errors = [];
$messages = [];

// The provided API key (decoded from base64)
$apiKey = 'ggj0d4F7veunjjxkRShKgh6AGcckReF-dtjuMdhPgUI:';

if (isset($_POST['update_api_key'])) {
    try {
        $messages[] = "Updating Interakt API Key...";
        
        // Check if support_settings table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'support_settings'");
        if ($stmt->rowCount() == 0) {
            $messages[] = "Creating support_settings table...";
            
            // Create the table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS `support_settings` (
                `setting_id` CHAR(36) NOT NULL PRIMARY KEY,
                `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                `setting_value` TEXT NOT NULL,
                `description` TEXT,
                `updated_by` CHAR(36),
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
            
            $messages[] = "✓ Created support_settings table";
        }
        
        // Update or insert the API key
        $stmt = $pdo->prepare("
            INSERT INTO support_settings (setting_id, setting_key, setting_value, description, updated_at)
            VALUES (UUID(), 'interakt_api_key', ?, 'Interakt API Key for WhatsApp integration', NOW())
            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = NOW()
        ");
        
        $stmt->execute([$apiKey]);
        $messages[] = "✓ Updated Interakt API key successfully!";
        
        // Also update the base URL if needed
        $stmt = $pdo->prepare("
            INSERT INTO support_settings (setting_id, setting_key, setting_value, description, updated_at)
            VALUES (UUID(), 'interakt_base_url', 'https://api.interakt.ai/v1/public', 'Interakt API base URL', NOW())
            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_at = NOW()
        ");
        
        $stmt->execute();
        $messages[] = "✓ Updated Interakt base URL";
        
        // Add other default settings
        $defaultSettings = [
            ['auto_assign_tickets', 'true', 'Automatically assign tickets to available agents'],
            ['business_hours_start', '09:00', 'Support business hours start time'],
            ['business_hours_end', '18:00', 'Support business hours end time'],
            ['auto_response_enabled', 'true', 'Enable automated responses for common queries'],
            ['max_response_time_minutes', '30', 'Maximum response time target in minutes'],
            ['customer_satisfaction_enabled', 'true', 'Enable customer satisfaction surveys'],
            ['business_notifications_enabled', 'true', 'Enable automated business notifications'],
            ['cart_abandonment_delay_hours', '2', 'Hours to wait before sending cart abandonment reminder'],
            ['feedback_request_delay_days', '3', 'Days to wait after delivery before requesting feedback']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $pdo->prepare("
                INSERT INTO support_settings (setting_id, setting_key, setting_value, description, updated_at)
                VALUES (UUID(), ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_at = NOW()
            ");
            $stmt->execute([$setting[0], $setting[1], $setting[2]]);
        }
        $messages[] = "✓ Added default support settings";
        
        $updateComplete = true;
        
    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Load current settings to display
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM support_settings WHERE setting_key IN ('interakt_api_key', 'interakt_base_url')");
    $stmt->execute();
    $currentSettings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $currentSettings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Interakt API Key - Alpha Nutrition</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        .container { background: #f9f9f9; padding: 2rem; border-radius: 8px; }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .message { padding: 0.5rem; margin: 0.5rem 0; border-radius: 4px; }
        .message.success { background: #e8f5e8; }
        .message.error { background: #ffeaea; }
        .message.warning { background: #fff3cd; }
        button { background: #ff6b35; color: white; padding: 1rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        button:hover { background: #e55a2b; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .info { background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
        .settings-display { background: white; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
        .setting-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .setting-key { font-weight: bold; }
        .setting-value { font-family: monospace; color: #666; }
        .api-key-masked { color: #4caf50; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Update Interakt API Key</h1>
        
        <?php if (!$updateComplete && empty($_POST)): ?>
            <div class="info">
                <h3>About Interakt API Configuration</h3>
                <p>This will update your Interakt WhatsApp Business API key for OTP delivery. The API key provided will be:</p>
                <ul>
                    <li>Stored securely in the database</li>
                    <li>Used for sending OTP messages via WhatsApp</li>
                    <li>Required for the OTP authentication system to work</li>
                </ul>
                <p><strong>API Key:</strong> <code><?php echo substr($apiKey, 0, 20) . '...'; ?></code></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="update_api_key">Update Interakt API Key</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="message error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($currentSettings)): ?>
            <div class="settings-display">
                <h3>Current Interakt Settings</h3>
                <?php foreach ($currentSettings as $key => $value): ?>
                    <div class="setting-item">
                        <span class="setting-key"><?php echo htmlspecialchars($key); ?>:</span>
                        <span class="setting-value <?php echo $key === 'interakt_api_key' ? 'api-key-masked' : ''; ?>">
                            <?php 
                            if ($key === 'interakt_api_key') {
                                echo substr($value, 0, 10) . '...' . substr($value, -5) . ' ✓';
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($updateComplete): ?>
            <div class="info">
                <h3>API Key Updated Successfully!</h3>
                <p>Your Interakt API key has been configured. You can now:</p>
                <ul>
                    <li><a href="setup-otp-web.php">Setup OTP Database Tables</a> (if not done already)</li>
                    <li><a href="register-otp.php">Test OTP Registration</a></li>
                    <li><a href="login-otp.php">Test OTP Login</a></li>
                    <li><a href="index.php">Return to Homepage</a></li>
                </ul>
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Ensure your Interakt WhatsApp templates are approved</li>
                    <li>Test the OTP system with a real phone number</li>
                    <li>Delete this setup file (update-api-key-web.php) after testing</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
