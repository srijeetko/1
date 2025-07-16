<?php
// User Authentication Functions
require_once 'db_connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class UserAuth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
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
}

// Initialize auth class
$auth = new UserAuth($pdo);
?>
