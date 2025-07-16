<?php
require_once 'includes/db_connection.php';

echo "<h2>🔧 Fix OMS Tables</h2>";

// List of required OMS tables
$required_tables = [
    'activity_log' => "
        CREATE TABLE `activity_log` (
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
    ",
    
    'delivery_partners' => "
        CREATE TABLE `delivery_partners` (
            `partner_id` CHAR(36) NOT NULL PRIMARY KEY,
            `partner_name` VARCHAR(100) NOT NULL,
            `partner_type` ENUM('delhivery', 'shiprocket', 'rapidshyp') NOT NULL,
            `api_key` VARCHAR(255),
            `api_secret` VARCHAR(255),
            `base_url` VARCHAR(255),
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ",
    
    'delivery_assignments' => "
        CREATE TABLE `delivery_assignments` (
            `assignment_id` CHAR(36) NOT NULL PRIMARY KEY,
            `order_id` CHAR(36) NOT NULL,
            `partner_id` CHAR(36) NOT NULL,
            `assigned_by` CHAR(36) NOT NULL,
            `pickup_address` TEXT NOT NULL,
            `delivery_address` TEXT NOT NULL,
            `estimated_delivery` DATETIME,
            `actual_pickup` DATETIME,
            `actual_delivery` DATETIME,
            `delivery_status` ENUM('assigned', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned') DEFAULT 'assigned',
            `tracking_number` VARCHAR(100),
            `delivery_notes` TEXT,
            `delivery_charges` DECIMAL(10,2) DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    "
];

// Check which tables exist
echo "<h3>Checking OMS Tables:</h3>";
$missing_tables = [];

foreach ($required_tables as $table_name => $create_sql) {
    try {
        $stmt = $pdo->query("DESCRIBE $table_name");
        echo "<p>✅ <strong>$table_name</strong> - exists</p>";
    } catch (Exception $e) {
        echo "<p>❌ <strong>$table_name</strong> - missing</p>";
        $missing_tables[$table_name] = $create_sql;
    }
}

if (empty($missing_tables)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ All OMS Tables Exist!</h4>";
    echo "<p>Your OMS system should work properly now.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ Missing " . count($missing_tables) . " OMS Tables</h4>";
    echo "<p>The following tables need to be created:</p>";
    echo "<ul>";
    foreach (array_keys($missing_tables) as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='?create_tables=1' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔧 Create Missing Tables</a></p>";
}

// Handle table creation
if (isset($_GET['create_tables'])) {
    echo "<h3>🔧 Creating Missing Tables...</h3>";
    
    $created_tables = [];
    $failed_tables = [];
    
    foreach ($missing_tables as $table_name => $create_sql) {
        try {
            echo "<p>Creating table: <strong>$table_name</strong>...</p>";
            $pdo->exec($create_sql);
            $created_tables[] = $table_name;
            echo "<p style='color: green;'>✅ $table_name created successfully</p>";
        } catch (Exception $e) {
            $failed_tables[$table_name] = $e->getMessage();
            echo "<p style='color: red;'>❌ Failed to create $table_name: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Create indexes
    if (!empty($created_tables)) {
        echo "<h4>Creating Indexes...</h4>";
        $indexes = [
            "CREATE INDEX IF NOT EXISTS `idx_activity_log_admin` ON `activity_log`(`admin_id`)",
            "CREATE INDEX IF NOT EXISTS `idx_activity_log_action` ON `activity_log`(`action_type`)",
            "CREATE INDEX IF NOT EXISTS `idx_activity_log_created` ON `activity_log`(`created_at`)",
            "CREATE INDEX IF NOT EXISTS `idx_delivery_partners_type` ON `delivery_partners`(`partner_type`)",
            "CREATE INDEX IF NOT EXISTS `idx_delivery_assignments_order` ON `delivery_assignments`(`order_id`)"
        ];
        
        foreach ($indexes as $index_sql) {
            try {
                $pdo->exec($index_sql);
            } catch (Exception $e) {
                // Ignore index creation errors
            }
        }
        echo "<p>✅ Indexes created</p>";
    }
    
    // Insert default delivery partners
    if (in_array('delivery_partners', $created_tables)) {
        echo "<h4>Adding Default Delivery Partners...</h4>";
        try {
            $partners = [
                ['Delhivery', 'delhivery'],
                ['Ship Rocket', 'shiprocket'],
                ['Rapid Shyp', 'rapidshyp']
            ];
            
            foreach ($partners as $partner) {
                $partner_id = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_partners (partner_id, partner_name, partner_type) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$partner_id, $partner[0], $partner[1]]);
            }
            echo "<p>✅ Default delivery partners added</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Could not add default partners: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Summary
    if (!empty($created_tables)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Tables Created Successfully!</h4>";
        echo "<p>Created " . count($created_tables) . " tables:</p>";
        echo "<ul>";
        foreach ($created_tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        echo "<p><strong>Your OMS system should now work properly!</strong></p>";
        echo "</div>";
    }
    
    if (!empty($failed_tables)) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Some Tables Failed to Create:</h4>";
        foreach ($failed_tables as $table => $error) {
            echo "<p><strong>$table:</strong> " . htmlspecialchars($error) . "</p>";
        }
        echo "</div>";
    }
    
    echo "<p><a href='fix-oms-tables.php'>🔄 Refresh to verify</a></p>";
}

echo "<hr>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<p>";
echo "<a href='oms/index.php'>OMS Dashboard</a> | ";
echo "<a href='oms/activity-log.php'>Activity Log</a> | ";
echo "<a href='checkout.php'>Checkout</a>";
echo "</p>";
?>
