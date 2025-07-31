<?php
/**
 * Update Interakt API Key
 * Run this script to update the Interakt API key in the database
 */

require_once 'includes/db_connection.php';

try {
    // The provided API key (decoded from base64)
    $apiKey = 'ggj0d4F7veunjjxkRShKgh6AGcckReF-dtjuMdhPgUI:';
    
    echo "Updating Interakt API Key...\n";
    
    // Check if support_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_settings'");
    if ($stmt->rowCount() == 0) {
        echo "Creating support_settings table...\n";
        
        // Create the table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS `support_settings` (
            `setting_id` CHAR(36) NOT NULL PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT NOT NULL,
            `description` TEXT,
            `updated_by` CHAR(36),
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        
        echo "✓ Created support_settings table\n";
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
    
    echo "✓ Updated Interakt API key successfully!\n";
    
    // Also update the base URL if needed
    $stmt = $pdo->prepare("
        INSERT INTO support_settings (setting_id, setting_key, setting_value, description, updated_at)
        VALUES (UUID(), 'interakt_base_url', 'https://api.interakt.ai/v1/public', 'Interakt API base URL', NOW())
        ON DUPLICATE KEY UPDATE
        setting_value = VALUES(setting_value),
        updated_at = NOW()
    ");
    
    $stmt->execute();
    
    echo "✓ Updated Interakt base URL\n";
    
    // Verify the settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM support_settings WHERE setting_key IN ('interakt_api_key', 'interakt_base_url')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Current Interakt Settings:\n";
    foreach ($settings as $setting) {
        $value = $setting['setting_key'] === 'interakt_api_key' ? 
                 substr($setting['setting_value'], 0, 10) . '...' : 
                 $setting['setting_value'];
        echo "  {$setting['setting_key']}: {$value}\n";
    }
    
    echo "\n🎉 Interakt API configuration completed successfully!\n";
    echo "The OTP system can now send WhatsApp messages via Interakt API.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error updating API key: " . $e->getMessage() . "\n";
}
?>
