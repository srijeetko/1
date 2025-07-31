<?php
/**
 * Check OTP System Setup Status
 * Verify if all required tables and data exist
 */

require_once 'includes/db_connection.php';

echo "<h2>OTP System Setup Status</h2>";

try {
    // Check if OTP tables exist
    $tables = [
        'otp_verifications' => 'OTP Verification Records',
        'user_login_attempts' => 'Login Attempt Tracking',
        'user_devices' => 'User Device Management',
        'auth_settings' => 'Authentication Settings',
        'otp_templates' => 'OTP Message Templates',
        'support_settings' => 'Interakt API Configuration'
    ];
    
    echo "<h3>Database Tables:</h3>";
    foreach ($tables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $table - $description<br>";
        } else {
            echo "❌ $table - $description (MISSING)<br>";
        }
    }
    
    // Check OTP templates
    echo "<h3>OTP Templates:</h3>";
    try {
        $stmt = $pdo->query("SELECT otp_type, template_name, interakt_template_name, is_active FROM otp_templates");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($templates)) {
            echo "❌ No OTP templates found<br>";
        } else {
            foreach ($templates as $template) {
                $status = $template['is_active'] ? '✅' : '❌';
                echo "$status {$template['otp_type']} - {$template['template_name']} ({$template['interakt_template_name']})<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking templates: " . $e->getMessage() . "<br>";
    }
    
    // Check Interakt API configuration
    echo "<h3>Interakt API Configuration:</h3>";
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM support_settings WHERE setting_key IN ('interakt_api_key', 'interakt_base_url')");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $apiKeySet = false;
        $baseUrlSet = false;
        
        foreach ($settings as $setting) {
            if ($setting['setting_key'] === 'interakt_api_key') {
                $apiKeySet = !empty($setting['setting_value']);
                echo ($apiKeySet ? '✅' : '❌') . " API Key: " . ($apiKeySet ? 'Configured' : 'Not set') . "<br>";
            }
            if ($setting['setting_key'] === 'interakt_base_url') {
                $baseUrlSet = !empty($setting['setting_value']);
                echo ($baseUrlSet ? '✅' : '❌') . " Base URL: " . ($baseUrlSet ? $setting['setting_value'] : 'Not set') . "<br>";
            }
        }
        
        if (!$apiKeySet) {
            echo "<p style='color: red;'>⚠️ Interakt API key not configured. <a href='update-api-key-web.php'>Update API Key</a></p>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking API configuration: " . $e->getMessage() . "<br>";
    }
    
    // Check users table for OTP columns
    echo "<h3>Users Table OTP Columns:</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $otpColumns = ['phone_verified', 'phone_verification_token', 'two_factor_enabled'];
        foreach ($otpColumns as $column) {
            $found = false;
            foreach ($columns as $col) {
                if ($col['Field'] === $column) {
                    $found = true;
                    break;
                }
            }
            echo ($found ? '✅' : '❌') . " $column<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking users table: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>Setup Actions:</h3>";
    echo "<a href='setup-otp-web.php' style='background: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px;'>Setup OTP Database</a>";
    echo "<a href='update-api-key-web.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px;'>Update API Key</a>";
    echo "<a href='register-otp.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px;'>Test Registration</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
