<?php
/**
 * Web-based OTP Database Setup
 * Visit this page in your browser to setup OTP authentication tables
 */

// Security check - only allow setup from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Setup only allowed from localhost');
}

require_once 'includes/db_connection.php';

$setupComplete = false;
$errors = [];
$messages = [];

if (isset($_POST['setup'])) {
    try {
        $messages[] = "Starting OTP Authentication System setup...";
        
        // Add OTP-related columns to users table
        try {
            $pdo->exec("ALTER TABLE `users` 
                ADD COLUMN `phone_verified` TINYINT(1) DEFAULT 0 AFTER `email_verified`,
                ADD COLUMN `phone_verification_token` VARCHAR(100) AFTER `email_verification_token`,
                ADD COLUMN `two_factor_enabled` TINYINT(1) DEFAULT 0 AFTER `phone_verified`,
                ADD COLUMN `backup_codes` JSON AFTER `two_factor_enabled`");
            $messages[] = "✓ Added OTP columns to users table";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $messages[] = "⚠ Users table already has OTP columns";
            } else {
                throw $e;
            }
        }

        // Create OTP verification table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `otp_verifications` (
            `otp_id` CHAR(36) NOT NULL PRIMARY KEY,
            `user_id` CHAR(36),
            `phone_number` VARCHAR(20) NOT NULL,
            `email` VARCHAR(255),
            `otp_code` VARCHAR(10) NOT NULL,
            `otp_type` ENUM('registration', 'login', 'password_reset', 'phone_verification', 'two_factor') NOT NULL,
            `verification_method` ENUM('sms', 'whatsapp', 'email') DEFAULT 'whatsapp',
            `is_verified` TINYINT(1) DEFAULT 0,
            `attempts` INT DEFAULT 0,
            `max_attempts` INT DEFAULT 3,
            `expires_at` DATETIME NOT NULL,
            `verified_at` DATETIME NULL,
            `ip_address` VARCHAR(45),
            `user_agent` TEXT,
            `interakt_message_id` VARCHAR(100),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            INDEX `idx_phone_otp` (`phone_number`, `otp_code`),
            INDEX `idx_email_otp` (`email`, `otp_code`),
            INDEX `idx_expires` (`expires_at`),
            INDEX `idx_type` (`otp_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $messages[] = "✓ Created otp_verifications table";

        // Create user login attempts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_login_attempts` (
            `attempt_id` CHAR(36) NOT NULL PRIMARY KEY,
            `identifier` VARCHAR(255) NOT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `attempt_type` ENUM('password', 'otp') NOT NULL,
            `success` TINYINT(1) DEFAULT 0,
            `failure_reason` VARCHAR(255),
            `user_agent` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_identifier_ip` (`identifier`, `ip_address`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $messages[] = "✓ Created user_login_attempts table";

        // Create user devices table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_devices` (
            `device_id` CHAR(36) NOT NULL PRIMARY KEY,
            `user_id` CHAR(36) NOT NULL,
            `device_name` VARCHAR(255),
            `device_fingerprint` VARCHAR(255) NOT NULL,
            `ip_address` VARCHAR(45),
            `user_agent` TEXT,
            `is_trusted` TINYINT(1) DEFAULT 0,
            `last_used` DATETIME,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_user_device` (`user_id`, `device_fingerprint`),
            INDEX `idx_fingerprint` (`device_fingerprint`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $messages[] = "✓ Created user_devices table";

        // Create authentication settings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `auth_settings` (
            `setting_id` CHAR(36) NOT NULL PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT NOT NULL,
            `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            `description` TEXT,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $messages[] = "✓ Created auth_settings table";

        // Create OTP templates table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `otp_templates` (
            `template_id` CHAR(36) NOT NULL PRIMARY KEY,
            `template_name` VARCHAR(100) NOT NULL,
            `interakt_template_name` VARCHAR(100) NOT NULL,
            `otp_type` ENUM('registration', 'login', 'password_reset', 'phone_verification', 'two_factor') NOT NULL,
            `language_code` VARCHAR(10) DEFAULT 'en',
            `template_content` TEXT NOT NULL,
            `variables` JSON,
            `is_active` TINYINT(1) DEFAULT 1,
            `usage_count` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_template_type` (`template_name`, `otp_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $messages[] = "✓ Created otp_templates table";

        // Insert default settings
        $defaultSettings = [
            ['UUID()', 'otp_expiry_minutes', '5', 'number', 'OTP expiry time in minutes'],
            ['UUID()', 'max_otp_attempts', '3', 'number', 'Maximum OTP verification attempts'],
            ['UUID()', 'otp_length', '6', 'number', 'Length of OTP code'],
            ['UUID()', 'enable_whatsapp_otp', 'true', 'boolean', 'Enable WhatsApp OTP delivery'],
            ['UUID()', 'enable_sms_otp', 'false', 'boolean', 'Enable SMS OTP delivery'],
            ['UUID()', 'enable_email_otp', 'true', 'boolean', 'Enable Email OTP delivery'],
            ['UUID()', 'require_phone_verification', 'true', 'boolean', 'Require phone verification for registration'],
            ['UUID()', 'enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication'],
            ['UUID()', 'trusted_device_days', '30', 'number', 'Days to remember trusted devices'],
            ['UUID()', 'max_login_attempts', '5', 'number', 'Maximum login attempts before lockout'],
            ['UUID()', 'lockout_duration_minutes', '15', 'number', 'Account lockout duration in minutes'],
            ['UUID()', 'password_reset_otp_only', 'false', 'boolean', 'Use only OTP for password reset (no email links)']
        ];

        foreach ($defaultSettings as $setting) {
            $pdo->exec("INSERT IGNORE INTO `auth_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`) 
                       VALUES ({$setting[0]}, '{$setting[1]}', '{$setting[2]}', '{$setting[3]}', '{$setting[4]}')");
        }
        $messages[] = "✓ Inserted default authentication settings";

        // Insert default OTP templates
        $defaultTemplates = [
            ['UUID()', 'registration_otp', 'registration_otp', 'registration',
             'Welcome! Your registration OTP is: {{otp_code}}. Valid for 5 minutes. Do not share this code with anyone.',
             '{"otp_code": "6-digit verification code", "user_name": "Customer name"}'],

            ['UUID()', 'login_otp', 'login_otp', 'login',
             'Your login OTP is: {{otp_code}}. Valid for 5 minutes. If you did not request this, please ignore.',
             '{"otp_code": "6-digit verification code"}'],

            ['UUID()', 'password_reset_otp', 'password_reset_otp', 'password_reset',
             'Your password reset OTP is: {{otp_code}}. Valid for 5 minutes. Use this to reset your password.',
             '{"otp_code": "6-digit verification code"}'],

            ['UUID()', 'phone_verification_otp', 'phone_verification_otp', 'phone_verification',
             'Verify your phone number. Your verification OTP is: {{otp_code}}. Valid for 5 minutes.',
             '{"otp_code": "6-digit verification code"}'],

            ['UUID()', 'two_factor_otp', 'two_factor_otp', 'two_factor',
             'Your two-factor authentication code is: {{otp_code}}. Valid for 5 minutes.',
             '{"otp_code": "6-digit verification code"}']
        ];

        foreach ($defaultTemplates as $template) {
            $pdo->exec("INSERT IGNORE INTO `otp_templates` (`template_id`, `template_name`, `interakt_template_name`, `otp_type`, `template_content`, `variables`) 
                       VALUES ({$template[0]}, '{$template[1]}', '{$template[2]}', '{$template[3]}', '{$template[4]}', '{$template[5]}')");
        }
        $messages[] = "✓ Inserted default OTP templates";

        // Create indexes for better performance
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_users_phone` ON `users` (`phone`)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_users_email_verified` ON `users` (`email_verified`)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_users_phone_verified` ON `users` (`phone_verified`)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_users_active` ON `users` (`is_active`)");
            $messages[] = "✓ Created performance indexes";
        } catch (PDOException $e) {
            $messages[] = "⚠ Some indexes already exist";
        }

        $messages[] = "🎉 OTP Authentication System setup completed successfully!";
        $setupComplete = true;

    } catch (Exception $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP System Setup - Alpha Nutrition</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>OTP Authentication System Setup</h1>
        
        <?php if (!$setupComplete && empty($_POST)): ?>
            <div class="info">
                <h3>About OTP Authentication System</h3>
                <p>This setup will create the necessary database tables and configurations for OTP-based authentication including:</p>
                <ul>
                    <li>OTP verification table for storing and managing OTP codes</li>
                    <li>User login attempts tracking for security</li>
                    <li>User devices management for trusted devices</li>
                    <li>Authentication settings for system configuration</li>
                    <li>OTP templates for WhatsApp/SMS messages</li>
                    <li>Additional columns in users table for phone verification</li>
                </ul>
                <p><strong>Note:</strong> This is safe to run multiple times. Existing data will not be affected.</p>
            </div>
            
            <form method="POST">
                <button type="submit" name="setup">Setup OTP Authentication System</button>
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

        <?php if ($setupComplete): ?>
            <div class="info">
                <h3>Setup Complete!</h3>
                <p>Your OTP Authentication System is now ready. You can:</p>
                <ul>
                    <li><a href="register-otp.php">Test OTP Registration</a></li>
                    <li><a href="login-otp.php">Test OTP Login</a></li>
                    <li><a href="index.php">Return to Homepage</a></li>
                </ul>
                <p><strong>Security Note:</strong> Delete this setup file (setup-otp-web.php) after setup is complete.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
