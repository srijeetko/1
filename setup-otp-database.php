<?php
/**
 * Setup OTP Database Tables
 * Run this script once to create OTP authentication tables
 */

require_once 'includes/db_connection.php';

try {
    echo "Setting up OTP Authentication System...\n";
    
    // Read SQL file
    $sql = file_get_contents('setup-otp-system.sql');
    
    if (!$sql) {
        throw new Exception("Could not read setup-otp-system.sql file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt) &&
                   !preg_match('/^DELIMITER/', $stmt);
        }
    );
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr(trim($statement), 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "⚠ Skipped (already exists): " . substr(trim($statement), 0, 50) . "...\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo "\n✅ OTP Authentication System setup completed successfully!\n";
    echo "\nTables created/updated:\n";
    echo "- otp_verifications\n";
    echo "- user_login_attempts\n";
    echo "- user_devices\n";
    echo "- auth_settings\n";
    echo "- otp_templates\n";
    echo "\nUsers table updated with OTP-related columns\n";
    echo "\nYou can now use OTP-based authentication!\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Error setting up OTP system: " . $e->getMessage() . "\n";
    echo "Please check the error and try again.\n";
}
?>
