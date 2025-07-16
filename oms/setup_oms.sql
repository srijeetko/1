-- Alpha Nutrition OMS Database Setup
-- Order Management System Tables

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- Create Delivery Partners table
CREATE TABLE IF NOT EXISTS `delivery_partners` (
    `partner_id` CHAR(36) NOT NULL PRIMARY KEY,
    `partner_name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100),
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255),
    `address` TEXT,
    `service_areas` JSON,
    `delivery_charges` JSON,
    `is_active` TINYINT(1) DEFAULT 1,
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `total_deliveries` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create Delivery Assignments table
CREATE TABLE IF NOT EXISTS `delivery_assignments` (
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`partner_id`) REFERENCES `delivery_partners`(`partner_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`assigned_by`) REFERENCES `admin_users`(`admin_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create Payment Transactions table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `transaction_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `payment_gateway` VARCHAR(50),
    `gateway_transaction_id` VARCHAR(100),
    `payment_method` ENUM('card', 'upi', 'netbanking', 'wallet', 'cod') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'INR',
    `transaction_status` ENUM('pending', 'processing', 'success', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    `gateway_response` JSON,
    `failure_reason` TEXT,
    `processed_at` DATETIME,
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `refund_date` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create Order Status History table
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `history_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `previous_status` VARCHAR(50),
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` CHAR(36),
    `change_reason` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `admin_users`(`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create Customer Communications table
CREATE TABLE IF NOT EXISTS `customer_communications` (
    `communication_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `communication_type` ENUM('email', 'sms', 'whatsapp', 'call') NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255),
    `message` TEXT NOT NULL,
    `template_used` VARCHAR(100),
    `sent_by` CHAR(36),
    `sent_at` DATETIME,
    `delivery_status` ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    `response_received` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`sent_by`) REFERENCES `admin_users`(`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create OMS Settings table
CREATE TABLE IF NOT EXISTS `oms_settings` (
    `setting_id` CHAR(36) NOT NULL PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT,
    `is_editable` TINYINT(1) DEFAULT 1,
    `updated_by` CHAR(36),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `admin_users`(`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create indexes for better performance
CREATE INDEX `idx_delivery_assignments_order` ON `delivery_assignments`(`order_id`);
CREATE INDEX `idx_delivery_assignments_partner` ON `delivery_assignments`(`partner_id`);
CREATE INDEX `idx_delivery_assignments_status` ON `delivery_assignments`(`delivery_status`);
CREATE INDEX `idx_payment_transactions_order` ON `payment_transactions`(`order_id`);
CREATE INDEX `idx_payment_transactions_status` ON `payment_transactions`(`transaction_status`);
CREATE INDEX `idx_payment_transactions_gateway` ON `payment_transactions`(`payment_gateway`);
CREATE INDEX `idx_order_status_history_order` ON `order_status_history`(`order_id`);
CREATE INDEX `idx_customer_communications_order` ON `customer_communications`(`order_id`);
CREATE INDEX `idx_customer_communications_type` ON `customer_communications`(`communication_type`);
CREATE INDEX `idx_activity_log_admin` ON `activity_log`(`admin_id`);
CREATE INDEX `idx_activity_log_action` ON `activity_log`(`action_type`);
CREATE INDEX `idx_activity_log_table` ON `activity_log`(`affected_table`);
CREATE INDEX `idx_activity_log_created` ON `activity_log`(`created_at`);

-- Function to generate UUID
DELIMITER $$
CREATE FUNCTION IF NOT EXISTS GENERATE_UUID() RETURNS CHAR(36)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN LOWER(CONCAT(
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        '4', LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
        HEX(FLOOR(RAND() * 4 + 8)), LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFFFFFF)), 12, '0')
    ));
END$$
DELIMITER ;

-- Note: Using existing admin_users table - no new admin creation needed
-- Your existing admin@example.com / abcd@1234 credentials will work

-- Create Activity Log table
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert sample delivery partners (Updated with real delivery services)
INSERT INTO `delivery_partners` (`partner_id`, `partner_name`, `contact_person`, `phone`, `email`, `service_areas`, `delivery_charges`) VALUES
(GENERATE_UUID(), 'Delhivery', 'Customer Support', '+91-124-4646444', 'support@delhivery.com', '["All India", "International"]', '{"surface": 40, "express": 80, "same_day": 150, "cod": 25}'),
(GENERATE_UUID(), 'Shiprocket', 'Support Team', '+91-11-4084-4444', 'support@shiprocket.in', '["All India", "International"]', '{"surface": 35, "express": 75, "same_day": 140, "cod": 20}'),
(GENERATE_UUID(), 'RapidShyp', 'Customer Care', '+91-80-4718-4718', 'care@rapidshyp.com', '["All India"]', '{"surface": 38, "express": 78, "same_day": 145, "cod": 22}');

-- Insert default OMS settings
INSERT INTO `oms_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`) VALUES
(GENERATE_UUID(), 'auto_assign_delivery', 'true', 'boolean', 'Automatically assign delivery partners based on location'),
(GENERATE_UUID(), 'order_confirmation_email', 'true', 'boolean', 'Send order confirmation emails automatically'),
(GENERATE_UUID(), 'sms_notifications', 'true', 'boolean', 'Send SMS notifications for order updates'),
(GENERATE_UUID(), 'delivery_sla_hours', '48', 'number', 'Standard delivery SLA in hours'),
(GENERATE_UUID(), 'payment_timeout_minutes', '15', 'number', 'Payment timeout in minutes'),
(GENERATE_UUID(), 'cod_limit', '5000', 'number', 'Maximum COD order amount'),
(GENERATE_UUID(), 'free_shipping_threshold', '999', 'number', 'Minimum order amount for free shipping');

COMMIT;

-- Success message
SELECT 'OMS Database setup completed successfully!' as message;
SELECT 'Use existing admin credentials: admin@example.com / abcd@1234' as credentials;
SELECT 'Access OMS at: /oms/login.php' as access_url;
