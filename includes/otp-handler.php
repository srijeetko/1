<?php
/**
 * OTP Handler Class
 * Manages OTP generation, verification, and delivery via Interakt API
 * Alpha Nutrition - User Authentication System
 */

require_once 'db_connection.php';
require_once 'interakt-handler.php';

class OTPHandler {
    private $pdo;
    private $interaktHandler;
    private $settings;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->interaktHandler = new InteraktHandler($pdo);
        $this->loadSettings();
    }

    /**
     * Load OTP settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_key, setting_value, setting_type
                FROM auth_settings
                WHERE is_active = 1
            ");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->settings = [];
            foreach ($settings as $setting) {
                $value = $setting['setting_value'];
                
                // Convert based on type
                switch ($setting['setting_type']) {
                    case 'boolean':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'number':
                        $value = is_numeric($value) ? (int)$value : 0;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $this->settings[$setting['setting_key']] = $value;
            }
        } catch (Exception $e) {
            error_log("OTP settings load error: " . $e->getMessage());
            // Set default values
            $this->settings = [
                'otp_expiry_minutes' => 5,
                'max_otp_attempts' => 3,
                'otp_length' => 6,
                'enable_whatsapp_otp' => true,
                'enable_sms_otp' => false,
                'enable_email_otp' => true
            ];
        }
    }

    /**
     * Generate and send OTP
     */
    public function generateOTP($identifier, $otpType, $userId = null, $method = 'whatsapp') {
        try {
            // Validate input
            if (empty($identifier) || empty($otpType)) {
                return ['success' => false, 'message' => 'Invalid parameters'];
            }

            // Check if method is enabled
            $methodKey = "enable_{$method}_otp";
            if (!($this->settings[$methodKey] ?? false)) {
                return ['success' => false, 'message' => ucfirst($method) . ' OTP is not enabled'];
            }

            // Determine if identifier is phone or email
            $isPhone = $this->isPhoneNumber($identifier);
            $phoneNumber = $isPhone ? $identifier : null;
            $email = !$isPhone ? $identifier : null;

            // Check rate limiting
            if (!$this->checkRateLimit($identifier)) {
                return ['success' => false, 'message' => 'Too many OTP requests. Please try again later.'];
            }

            // Generate OTP code
            $otpCode = $this->generateOTPCode();
            $expiresAt = date('Y-m-d H:i:s', time() + ($this->settings['otp_expiry_minutes'] * 60));

            // Store OTP in database
            $otpId = $this->generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO otp_verifications (
                    otp_id, user_id, phone_number, email, otp_code, otp_type, 
                    verification_method, expires_at, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $otpId,
                $userId,
                $phoneNumber,
                $email,
                $otpCode,
                $otpType,
                $method,
                $expiresAt,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // Send OTP based on method
            $sendResult = $this->sendOTP($identifier, $otpCode, $otpType, $method);

            if ($sendResult['success']) {
                // Update with message ID if available
                if (isset($sendResult['message_id'])) {
                    $stmt = $this->pdo->prepare("
                        UPDATE otp_verifications 
                        SET interakt_message_id = ? 
                        WHERE otp_id = ?
                    ");
                    $stmt->execute([$sendResult['message_id'], $otpId]);
                }

                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'otp_id' => $otpId,
                    'expires_in' => $this->settings['otp_expiry_minutes'] * 60,
                    'method' => $method
                ];
            } else {
                // Delete failed OTP record
                $stmt = $this->pdo->prepare("DELETE FROM otp_verifications WHERE otp_id = ?");
                $stmt->execute([$otpId]);

                return [
                    'success' => false,
                    'message' => 'Failed to send OTP: ' . ($sendResult['error'] ?? 'Unknown error')
                ];
            }

        } catch (Exception $e) {
            error_log("OTP generation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'OTP generation failed'];
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP($identifier, $otpCode, $otpType) {
        try {
            // Determine if identifier is phone or email
            $isPhone = $this->isPhoneNumber($identifier);
            
            $whereClause = $isPhone ? "phone_number = ?" : "email = ?";
            
            // Find valid OTP
            $stmt = $this->pdo->prepare("
                SELECT otp_id, user_id, otp_code, attempts, max_attempts, expires_at, is_verified
                FROM otp_verifications
                WHERE $whereClause AND otp_type = ? AND is_verified = 0
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$identifier, $otpType]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRecord) {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }

            // Check if OTP is expired
            if (strtotime($otpRecord['expires_at']) < time()) {
                return ['success' => false, 'message' => 'OTP has expired'];
            }

            // Check attempts
            if ($otpRecord['attempts'] >= $otpRecord['max_attempts']) {
                return ['success' => false, 'message' => 'Maximum verification attempts exceeded'];
            }

            // Increment attempts
            $stmt = $this->pdo->prepare("
                UPDATE otp_verifications 
                SET attempts = attempts + 1 
                WHERE otp_id = ?
            ");
            $stmt->execute([$otpRecord['otp_id']]);

            // Verify OTP code
            if ($otpCode === $otpRecord['otp_code']) {
                // Mark as verified
                $stmt = $this->pdo->prepare("
                    UPDATE otp_verifications 
                    SET is_verified = 1, verified_at = NOW() 
                    WHERE otp_id = ?
                ");
                $stmt->execute([$otpRecord['otp_id']]);

                // Log successful attempt
                $this->logLoginAttempt($identifier, 'otp', true);

                return [
                    'success' => true,
                    'message' => 'OTP verified successfully',
                    'otp_id' => $otpRecord['otp_id'],
                    'user_id' => $otpRecord['user_id']
                ];
            } else {
                // Log failed attempt
                $this->logLoginAttempt($identifier, 'otp', false, 'Invalid OTP code');

                $remainingAttempts = $otpRecord['max_attempts'] - ($otpRecord['attempts'] + 1);
                return [
                    'success' => false,
                    'message' => "Invalid OTP code. $remainingAttempts attempts remaining."
                ];
            }

        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'OTP verification failed'];
        }
    }

    /**
     * Send OTP via specified method
     */
    private function sendOTP($identifier, $otpCode, $otpType, $method) {
        try {
            switch ($method) {
                case 'whatsapp':
                    return $this->sendWhatsAppOTP($identifier, $otpCode, $otpType);
                case 'sms':
                    return $this->sendSMSOTP($identifier, $otpCode, $otpType);
                case 'email':
                    return $this->sendEmailOTP($identifier, $otpCode, $otpType);
                default:
                    return ['success' => false, 'error' => 'Invalid delivery method'];
            }
        } catch (Exception $e) {
            error_log("Send OTP error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send OTP via WhatsApp using Interakt
     */
    private function sendWhatsAppOTP($phoneNumber, $otpCode, $otpType) {
        try {
            // Get template for OTP type
            $stmt = $this->pdo->prepare("
                SELECT interakt_template_name, template_content
                FROM otp_templates
                WHERE otp_type = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$otpType]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                return ['success' => false, 'error' => 'OTP template not found'];
            }

            // Send via Interakt
            $result = $this->interaktHandler->sendTemplateMessage(
                $phoneNumber,
                $template['interakt_template_name'],
                [
                    'otp_code' => $otpCode,
                    'user_name' => 'Customer'
                ]
            );

            return $result;

        } catch (Exception $e) {
            error_log("WhatsApp OTP send error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send OTP via SMS (placeholder - implement with SMS provider)
     */
    private function sendSMSOTP($phoneNumber, $otpCode, $otpType) {
        // Implement SMS sending logic here
        // This is a placeholder for SMS provider integration
        return ['success' => false, 'error' => 'SMS OTP not implemented yet'];
    }

    /**
     * Send OTP via Email (placeholder - implement with email service)
     */
    private function sendEmailOTP($email, $otpCode, $otpType) {
        // Implement email sending logic here
        // This is a placeholder for email service integration
        return ['success' => false, 'error' => 'Email OTP not implemented yet'];
    }

    /**
     * Check rate limiting for OTP requests
     */
    private function checkRateLimit($identifier) {
        try {
            // Check if there are too many recent OTP requests
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM otp_verifications
                WHERE (phone_number = ? OR email = ?)
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$identifier, $identifier]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Allow max 2 OTP requests per minute
            return $result['count'] < 2;

        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow on error
        }
    }

    /**
     * Generate OTP code
     */
    private function generateOTPCode() {
        $length = $this->settings['otp_length'] ?? 6;
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return str_pad(random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Check if string is a phone number
     */
    private function isPhoneNumber($identifier) {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);

        // Check if it looks like an Indian phone number
        return preg_match('/^[6-9][0-9]{9}$/', $cleaned) ||
               preg_match('/^91[6-9][0-9]{9}$/', $cleaned);
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($identifier, $type, $success, $reason = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_login_attempts (
                    attempt_id, identifier, ip_address, attempt_type,
                    success, failure_reason, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $this->generateUUID(),
                $identifier,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $type,
                $success ? 1 : 0,
                $reason,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

        } catch (Exception $e) {
            error_log("Login attempt log error: " . $e->getMessage());
        }
    }

    /**
     * Generate UUID
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Clean up expired OTPs
     */
    public function cleanupExpiredOTPs() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM otp_verifications
                WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();

            return ['success' => true, 'deleted' => $stmt->rowCount()];

        } catch (Exception $e) {
            error_log("OTP cleanup error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get OTP statistics
     */
    public function getOTPStats($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    otp_type,
                    verification_method,
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
                    AVG(attempts) as avg_attempts
                FROM otp_verifications
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY otp_type, verification_method
                ORDER BY total_sent DESC
            ");
            $stmt->execute([$days]);

            return ['success' => true, 'stats' => $stmt->fetchAll(PDO::FETCH_ASSOC)];

        } catch (Exception $e) {
            error_log("OTP stats error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP($identifier, $otpType) {
        try {
            // Check if there's a recent unverified OTP
            $isPhone = $this->isPhoneNumber($identifier);
            $whereClause = $isPhone ? "phone_number = ?" : "email = ?";

            $stmt = $this->pdo->prepare("
                SELECT otp_id, verification_method, created_at
                FROM otp_verifications
                WHERE $whereClause AND otp_type = ? AND is_verified = 0
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$identifier, $otpType]);
            $lastOTP = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastOTP) {
                // Check if enough time has passed (at least 30 seconds)
                $timeDiff = time() - strtotime($lastOTP['created_at']);
                if ($timeDiff < 30) {
                    return [
                        'success' => false,
                        'message' => 'Please wait ' . (30 - $timeDiff) . ' seconds before requesting a new OTP'
                    ];
                }

                // Mark old OTP as expired
                $stmt = $this->pdo->prepare("
                    UPDATE otp_verifications
                    SET expires_at = NOW()
                    WHERE otp_id = ?
                ");
                $stmt->execute([$lastOTP['otp_id']]);

                // Generate new OTP with same method
                return $this->generateOTP($identifier, $otpType, null, $lastOTP['verification_method']);
            } else {
                // No previous OTP found, generate new one
                return $this->generateOTP($identifier, $otpType);
            }

        } catch (Exception $e) {
            error_log("OTP resend error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resend OTP'];
        }
    }
}
?>
