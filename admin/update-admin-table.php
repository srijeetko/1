<?php
// Update admin_users table to add password tracking
include '../includes/db_connection.php';

try {
    // Check if password_changed_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'password_changed_at'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add password_changed_at column
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN password_changed_at TIMESTAMP NULL DEFAULT NULL AFTER password");
        echo "✅ Added password_changed_at column to admin_users table<br>";
    } else {
        echo "✅ password_changed_at column already exists<br>";
    }
    
    // Check if login_attempts column exists (for future security features)
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'login_attempts'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add login_attempts column for brute force protection
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN login_attempts INT DEFAULT 0 AFTER password_changed_at");
        echo "✅ Added login_attempts column to admin_users table<br>";
    } else {
        echo "✅ login_attempts column already exists<br>";
    }
    
    // Check if last_login_attempt column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'last_login_attempt'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add last_login_attempt column
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN last_login_attempt TIMESTAMP NULL DEFAULT NULL AFTER login_attempts");
        echo "✅ Added last_login_attempt column to admin_users table<br>";
    } else {
        echo "✅ last_login_attempt column already exists<br>";
    }
    
    echo "<br><strong>Admin table update completed successfully!</strong><br>";
    echo "<a href='change-password.php'>← Go to Change Password</a><br>";
    echo "<a href='index.php'>← Back to Dashboard</a>";
    
} catch (Exception $e) {
    echo "❌ Error updating admin table: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
</style>
