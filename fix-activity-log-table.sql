-- Create the missing activity_log table for OMS
-- This fixes the "Table 'alphanutrition_db.activity_log' doesn't exist" error

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_activity_log_admin` ON `activity_log`(`admin_id`);
CREATE INDEX IF NOT EXISTS `idx_activity_log_action` ON `activity_log`(`action_type`);
CREATE INDEX IF NOT EXISTS `idx_activity_log_table` ON `activity_log`(`affected_table`);
CREATE INDEX IF NOT EXISTS `idx_activity_log_created` ON `activity_log`(`created_at`);

-- Insert a test log entry to verify the table works
INSERT INTO `activity_log` (
    `log_id`, 
    `action_type`, 
    `action_description`, 
    `ip_address`
) VALUES (
    REPLACE(UUID(), '-', ''),
    'system',
    'Activity log table created successfully',
    '127.0.0.1'
);

-- Verify the table was created
SELECT 'activity_log table created successfully!' as message;
SELECT COUNT(*) as total_logs FROM activity_log;
