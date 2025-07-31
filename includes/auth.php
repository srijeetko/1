<?php
// User Authentication Functions with OTP Support
require_once 'db_connection.php';
require_once 'otp-handler.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class UserAuth {
    private $pdo;
    private $otpHandler;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->otpHandler = new OTPHandler($pdo);
    }
    
    // Generate UUID function
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // Register new user
    public function register($userData) {
        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Generate UUID for user
            $userId = $this->generateUUID();

            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

            // Generate email verification token
            $verificationToken = bin2hex(random_bytes(32));

            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (user_id, first_name, last_name, email, phone, password_hash, email_verification_token)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $userData['first_name'],
                $userData['last_name'],
                $userData['email'],
                $userData['phone'] ?? null,
                $passwordHash,
                $verificationToken
            ]);

            // Create default user preferences
            $this->createDefaultPreferences($userId);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
                'verification_token' => $verificationToken
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    // Login user
    public function login($email, $password, $rememberMe = false) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, first_name, last_name, email, password_hash, is_active, email_verified 
                FROM users WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Update last login
            $this->updateLastLogin($user['user_id']);
            
            // Create session
            $this->createSession($user, $rememberMe);
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'user' => [
                    'user_id' => $user['user_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'email_verified' => $user['email_verified']
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    // Create user session
    private function createSession($user, $rememberMe = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['is_logged_in'] = true;
        
        // Set session expiry
        $expiryTime = $rememberMe ? time() + (30 * 24 * 60 * 60) : time() + (24 * 60 * 60); // 30 days or 1 day
        
        // Store session in database
        $sessionId = session_id();
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $sessionId,
            $user['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            date('Y-m-d H:i:s', $expiryTime)
        ]);
        
        if ($rememberMe) {
            setcookie('remember_token', $sessionId, $expiryTime, '/', '', false, true);
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']) {
            return true;
        }

        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }

        return false;
    }
    
    // Get current user
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT user_id, first_name, last_name, email, phone, profile_image, email_verified
            FROM users WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Logout user
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Remove session from database
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
            $stmt->execute([session_id()]);
        }

        // Clear session
        session_destroy();

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    // Update last login
    private function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    // Create default user preferences
    private function createDefaultPreferences($userId) {
        $preferenceId = $this->generateUUID();
        $stmt = $this->pdo->prepare("
            INSERT INTO user_preferences (preference_id, user_id) VALUES (?, ?)
        ");
        $stmt->execute([$preferenceId, $userId]);
    }
    
    // Validate remember token
    private function validateRememberToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT u.user_id, u.first_name, u.last_name, u.email
            FROM user_sessions us
            JOIN users u ON us.user_id = u.user_id
            WHERE us.session_id = ? AND us.expires_at > NOW() AND us.is_active = 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['is_logged_in'] = true;
            return true;
        }

        return false;
    }
    
    // Password reset functionality
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            $resetToken = bin2hex(random_bytes(32));
            $expiryTime = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password_reset_token = ?, password_reset_expires = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$resetToken, $expiryTime, $user['user_id']]);
            
            return [
                'success' => true, 
                'message' => 'Password reset link sent',
                'reset_token' => $resetToken
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to process request'];
        }
    }
    
    // Reset password
    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM users 
                WHERE password_reset_token = ? AND password_reset_expires > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL 
                WHERE user_id = ?
            ");
            $stmt->execute([$passwordHash, $user['user_id']]);
            
            return ['success' => true, 'message' => 'Password reset successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }

    // =====================================================
    // OTP-BASED AUTHENTICATION METHODS
    // =====================================================

    /**
     * Register user with OTP verification
     */
    public function registerWithOTP($userData) {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'phone', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Check if phone already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE phone = ?");
            $stmt->execute([$userData['phone']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Phone number already registered'];
            }

            // Check if email already exists (if provided)
            if (!empty($userData['email'])) {
                $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$userData['email']]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }
            }

            // Generate OTP for phone verification
            $otpResult = $this->otpHandler->generateOTP($userData['phone'], 'registration');

            if (!$otpResult['success']) {
                return $otpResult;
            }

            // Store user data temporarily (in session or database)
            $_SESSION['pending_registration'] = [
                'user_data' => $userData,
                'otp_id' => $otpResult['otp_id'],
                'expires_at' => time() + 300 // 5 minutes
            ];

            return [
                'success' => true,
                'message' => 'OTP sent to your phone number',
                'otp_id' => $otpResult['otp_id'],
                'next_step' => 'verify_otp'
            ];

        } catch (Exception $e) {
            error_log("Registration with OTP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Complete registration after OTP verification
     */
    public function completeRegistration($phone, $otpCode) {
        try {
            // Check if there's pending registration
            if (!isset($_SESSION['pending_registration'])) {
                return ['success' => false, 'message' => 'No pending registration found'];
            }

            $pendingData = $_SESSION['pending_registration'];

            // Check if session expired
            if (time() > $pendingData['expires_at']) {
                unset($_SESSION['pending_registration']);
                return ['success' => false, 'message' => 'Registration session expired'];
            }

            // Verify OTP
            $verifyResult = $this->otpHandler->verifyOTP($phone, $otpCode, 'registration');

            if (!$verifyResult['success']) {
                return $verifyResult;
            }

            // Create user account
            $userData = $pendingData['user_data'];
            $userId = $this->generateUUID();
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("
                INSERT INTO users (
                    user_id, first_name, last_name, email, phone,
                    password_hash, phone_verified, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 1)
            ");

            $stmt->execute([
                $userId,
                $userData['first_name'],
                $userData['last_name'],
                $userData['email'] ?? null,
                $userData['phone'],
                $passwordHash
            ]);

            // Create default preferences
            $this->createDefaultPreferences($userId);

            // Clean up session
            unset($_SESSION['pending_registration']);

            // Auto-login user
            $this->createSession([
                'user_id' => $userId,
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'] ?? '',
                'phone_verified' => 1
            ]);

            return [
                'success' => true,
                'message' => 'Registration completed successfully',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("Complete registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration completion failed'];
        }
    }

    /**
     * Login with OTP (passwordless login)
     */
    public function loginWithOTP($identifier) {
        try {
            // Check if identifier is phone or email
            $isPhone = $this->isPhoneNumber($identifier);
            $field = $isPhone ? 'phone' : 'email';

            // Check if user exists
            $stmt = $this->pdo->prepare("
                SELECT user_id, first_name, last_name, email, phone, is_active, phone_verified
                FROM users WHERE $field = ?
            ");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }

            // For phone login, check if phone is verified
            if ($isPhone && !$user['phone_verified']) {
                return ['success' => false, 'message' => 'Phone number not verified'];
            }

            // Generate OTP
            $otpResult = $this->otpHandler->generateOTP($identifier, 'login', $user['user_id']);

            if (!$otpResult['success']) {
                return $otpResult;
            }

            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'otp_id' => $otpResult['otp_id'],
                'method' => $otpResult['method']
            ];

        } catch (Exception $e) {
            error_log("Login with OTP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }

    /**
     * Complete OTP login
     */
    public function completeOTPLogin($identifier, $otpCode) {
        try {
            // Verify OTP
            $verifyResult = $this->otpHandler->verifyOTP($identifier, $otpCode, 'login');

            if (!$verifyResult['success']) {
                return $verifyResult;
            }

            // Get user details
            $isPhone = $this->isPhoneNumber($identifier);
            $field = $isPhone ? 'phone' : 'email';

            $stmt = $this->pdo->prepare("
                SELECT user_id, first_name, last_name, email, phone, phone_verified, email_verified
                FROM users WHERE $field = ? AND is_active = 1
            ");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Update last login
            $this->updateLastLogin($user['user_id']);

            // Create session
            $this->createSession($user);

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'user_id' => $user['user_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'phone_verified' => $user['phone_verified'],
                    'email_verified' => $user['email_verified']
                ]
            ];

        } catch (Exception $e) {
            error_log("Complete OTP login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login completion failed'];
        }
    }

    /**
     * Password reset with OTP
     */
    public function requestPasswordResetOTP($identifier) {
        try {
            // Check if user exists
            $isPhone = $this->isPhoneNumber($identifier);
            $field = $isPhone ? 'phone' : 'email';

            $stmt = $this->pdo->prepare("
                SELECT user_id, first_name, last_name
                FROM users WHERE $field = ? AND is_active = 1
            ");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Generate OTP
            $otpResult = $this->otpHandler->generateOTP($identifier, 'password_reset', $user['user_id']);

            if (!$otpResult['success']) {
                return $otpResult;
            }

            return [
                'success' => true,
                'message' => 'Password reset OTP sent',
                'otp_id' => $otpResult['otp_id']
            ];

        } catch (Exception $e) {
            error_log("Password reset OTP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset request failed'];
        }
    }

    /**
     * Reset password with OTP verification
     */
    public function resetPasswordWithOTP($identifier, $otpCode, $newPassword) {
        try {
            // Verify OTP
            $verifyResult = $this->otpHandler->verifyOTP($identifier, $otpCode, 'password_reset');

            if (!$verifyResult['success']) {
                return $verifyResult;
            }

            // Get user
            $isPhone = $this->isPhoneNumber($identifier);
            $field = $isPhone ? 'phone' : 'email';

            $stmt = $this->pdo->prepare("
                SELECT user_id FROM users WHERE $field = ? AND is_active = 1
            ");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL
                WHERE user_id = ?
            ");
            $stmt->execute([$passwordHash, $user['user_id']]);

            return ['success' => true, 'message' => 'Password reset successful'];

        } catch (Exception $e) {
            error_log("Reset password with OTP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }

    /**
     * Check if string is a phone number
     */
    private function isPhoneNumber($identifier) {
        $cleaned = preg_replace('/[^0-9]/', '', $identifier);
        return preg_match('/^[6-9][0-9]{9}$/', $cleaned) ||
               preg_match('/^91[6-9][0-9]{9}$/', $cleaned);
    }

    /**
     * Resend OTP
     */
    public function resendOTP($identifier, $otpType) {
        return $this->otpHandler->resendOTP($identifier, $otpType);
    }
}

// Initialize auth class
$auth = new UserAuth($pdo);
?>
