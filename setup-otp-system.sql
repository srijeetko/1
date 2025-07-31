-- =====================================================
-- OTP AUTHENTICATION SYSTEM SETUP
-- Alpha Nutrition - User Authentication with OTP
-- =====================================================

-- Add OTP-related columns to users table
ALTER TABLE `users` 
ADD COLUMN `phone_verified` TINYINT(1) DEFAULT 0 AFTER `email_verified`,
ADD COLUMN `phone_verification_token` VARCHAR(100) AFTER `email_verification_token`,
ADD COLUMN `two_factor_enabled` TINYINT(1) DEFAULT 0 AFTER `phone_verified`,
ADD COLUMN `backup_codes` JSON AFTER `two_factor_enabled`;

-- Create OTP verification table
CREATE TABLE IF NOT EXISTS `otp_verifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user login attempts table for security
CREATE TABLE IF NOT EXISTS `user_login_attempts` (
    `attempt_id` CHAR(36) NOT NULL PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL, -- email or phone
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_type` ENUM('password', 'otp') NOT NULL,
    `success` TINYINT(1) DEFAULT 0,
    `failure_reason` VARCHAR(255),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_identifier_ip` (`identifier`, `ip_address`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user devices table for trusted devices
CREATE TABLE IF NOT EXISTS `user_devices` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create authentication settings table
CREATE TABLE IF NOT EXISTS `auth_settings` (
    `setting_id` CHAR(36) NOT NULL PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default authentication settings
INSERT INTO `auth_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`) VALUES
(UUID(), 'otp_expiry_minutes', '5', 'number', 'OTP expiry time in minutes'),
(UUID(), 'max_otp_attempts', '3', 'number', 'Maximum OTP verification attempts'),
(UUID(), 'otp_length', '6', 'number', 'Length of OTP code'),
(UUID(), 'enable_whatsapp_otp', 'true', 'boolean', 'Enable WhatsApp OTP delivery'),
(UUID(), 'enable_sms_otp', 'false', 'boolean', 'Enable SMS OTP delivery'),
(UUID(), 'enable_email_otp', 'true', 'boolean', 'Enable Email OTP delivery'),
(UUID(), 'require_phone_verification', 'true', 'boolean', 'Require phone verification for registration'),
(UUID(), 'enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication'),
(UUID(), 'trusted_device_days', '30', 'number', 'Days to remember trusted devices'),
(UUID(), 'max_login_attempts', '5', 'number', 'Maximum login attempts before lockout'),
(UUID(), 'lockout_duration_minutes', '15', 'number', 'Account lockout duration in minutes'),
(UUID(), 'password_reset_otp_only', 'false', 'boolean', 'Use only OTP for password reset (no email links)');

-- Create OTP templates for Interakt
CREATE TABLE IF NOT EXISTS `otp_templates` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default OTP templates
INSERT INTO `otp_templates` (`template_id`, `template_name`, `interakt_template_name`, `otp_type`, `template_content`, `variables`) VALUES
(UUID(), 'registration_otp', 'alpha_nutrition_registration_otp', 'registration', 
'Welcome to Alpha Nutrition! Your registration OTP is: {{otp_code}}. Valid for 5 minutes. Do not share this code with anyone.', 
'{"otp_code": "6-digit verification code", "user_name": "Customer name"}'),

(UUID(), 'login_otp', 'alpha_nutrition_login_otp', 'login', 
'Your Alpha Nutrition login OTP is: {{otp_code}}. Valid for 5 minutes. If you did not request this, please ignore.', 
'{"otp_code": "6-digit verification code"}'),

(UUID(), 'password_reset_otp', 'alpha_nutrition_password_reset_otp', 'password_reset', 
'Your Alpha Nutrition password reset OTP is: {{otp_code}}. Valid for 5 minutes. Use this to reset your password.', 
'{"otp_code": "6-digit verification code"}'),

(UUID(), 'phone_verification_otp', 'alpha_nutrition_phone_verification_otp', 'phone_verification', 
'Verify your phone number for Alpha Nutrition. Your verification OTP is: {{otp_code}}. Valid for 5 minutes.', 
'{"otp_code": "6-digit verification code"}'),

(UUID(), 'two_factor_otp', 'alpha_nutrition_two_factor_otp', 'two_factor', 
'Your Alpha Nutrition two-factor authentication code is: {{otp_code}}. Valid for 5 minutes.', 
'{"otp_code": "6-digit verification code"}');

-- Create function to generate UUID (if not exists)
DELIMITER //
CREATE FUNCTION IF NOT EXISTS GENERATE_UUID() 
RETURNS CHAR(36)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN UUID();
END //
DELIMITER ;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_users_phone` ON `users` (`phone`);
CREATE INDEX IF NOT EXISTS `idx_users_email_verified` ON `users` (`email_verified`);
CREATE INDEX IF NOT EXISTS `idx_users_phone_verified` ON `users` (`phone_verified`);
CREATE INDEX IF NOT EXISTS `idx_users_active` ON `users` (`is_active`);

-- Clean up expired OTP records (older than 24 hours)
DELETE FROM `otp_verifications` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Clean up old login attempts (older than 30 days)
DELETE FROM `user_login_attempts` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);

COMMIT;

-- Success message
SELECT 'OTP Authentication System setup completed successfully!' as message;
SELECT 'Tables created: otp_verifications, user_login_attempts, user_devices, auth_settings, otp_templates' as tables_created;
SELECT 'Users table updated with OTP-related columns' as users_table_updated;
SELECT 'Default settings and templates inserted' as default_data;
SELECT 'System ready for OTP-based authentication with Interakt API' as status;
