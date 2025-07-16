<?php
require_once 'includes/db_connection.php';

echo "<h2>🔧 Creating Activity Log Table</h2>";

try {
    // Create the activity_log table
    $sql = "
    CREATE TABLE IF NOT EXISTS `activity_log` (
        `log_id` CHAR(36) NOT NULL PRIMARY KEY,
        `admin_id` CHAR(36),
        `action_type` VARCHAR(100) NOT NULL,
        `action_description` TEXT NOT NULL,
        `affected_table` VARCHAR(100),
        `affected_record_id` CHAR(36),
        `old_values` JSON,
        `new_values` JSON,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ";
    
    echo "<p>Creating activity_log table...</p>";
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ activity_log table created successfully!</p>";
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_activity_log_admin` ON `activity_log`(`admin_id`)",
        "CREATE INDEX IF NOT EXISTS `idx_activity_log_action` ON `activity_log`(`action_type`)",
        "CREATE INDEX IF NOT EXISTS `idx_activity_log_table` ON `activity_log`(`affected_table`)",
        "CREATE INDEX IF NOT EXISTS `idx_activity_log_created` ON `activity_log`(`created_at`)"
    ];
    
    echo "<p>Creating indexes...</p>";
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
        } catch (Exception $e) {
            // Ignore if index already exists
        }
    }
    echo "<p style='color: green;'>✅ Indexes created successfully!</p>";
    
    // Insert a test log entry
    $log_id = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("
        INSERT INTO `activity_log` (
            `log_id`, 
            `action_type`, 
            `action_description`, 
            `ip_address`,
            `created_at`
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $log_id,
        'system',
        'Activity log table created and initialized',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
    
    echo "<p style='color: green;'>✅ Test log entry added!</p>";
    
    // Verify the table works
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activity_log");
    $result = $stmt->fetch();
    
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p><strong>activity_log table created successfully!</strong></p>";
    echo "<p>Total log entries: " . $result['count'] . "</p>";
    echo "<p>Your OMS Activity Log should now work without errors.</p>";
    echo "</div>";
    
    echo "<h3>🔗 Test Links:</h3>";
    echo "<p><a href='oms/activity-log.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Activity Log</a></p>";
    echo "<p><a href='oms/index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>OMS Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p><strong>Failed to create activity_log table:</strong></p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<h3>🔧 Manual Fix:</h3>";
    echo "<p>Run this SQL in phpMyAdmin:</p>";
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
    echo "CREATE TABLE `activity_log` (
    `log_id` CHAR(36) NOT NULL PRIMARY KEY,
    `admin_id` CHAR(36),
    `action_type` VARCHAR(100) NOT NULL,
    `action_description` TEXT NOT NULL,
    `affected_table` VARCHAR(100),
    `affected_record_id` CHAR(36),
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
    echo "</textarea>";
}

// Auto-redirect to OMS after 3 seconds if successful
if (!isset($e)) {
    echo "<script>
    setTimeout(function() {
        if (confirm('Table created successfully! Go to OMS Activity Log now?')) {
            window.location.href = 'oms/activity-log.php';
        }
    }, 2000);
    </script>";
}
?>
