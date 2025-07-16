<?php
session_start();
require_once '../includes/db_connection.php';

// Redirect if already logged in
if (isset($_SESSION['oms_admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Check admin credentials using existing admin_users table
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && $password === $admin['password_hash']) {
                // Login successful
                $_SESSION['oms_admin_id'] = $admin['admin_id'];
                $_SESSION['oms_admin_email'] = $admin['email'];
                $_SESSION['oms_admin_name'] = $admin['name'];
                $_SESSION['oms_admin_role'] = $admin['role'];

                // Update last login attempt
                $stmt = $pdo->prepare("UPDATE admin_users SET last_login_attempt = NOW(), login_attempts = 0 WHERE admin_id = ?");
                $stmt->execute([$admin['admin_id']]);

                header('Location: index.php');
                exit();
            } else {
                // Update failed login attempts
                if ($admin) {
                    $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = login_attempts + 1, last_login_attempt = NOW() WHERE admin_id = ?");
                    $stmt->execute([$admin['admin_id']]);
                }
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMS Login - Alpha Nutrition</title>
    <link rel="stylesheet" href="oms-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-shopping-cart"></i>
                    <h1>Alpha OMS</h1>
                </div>
                <h2>Order Management System</h2>
                <p>Sign in to access the order management dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="username" name="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required autocomplete="email" placeholder="admin@alphanutrition.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In to OMS
                </button>
            </form>

            <div class="login-footer">
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure Admin Access</span>
                </div>
                <div class="support-info">
                    <p>Need help? Contact IT Support</p>
                </div>
            </div>
        </div>

        <div class="login-bg">
            <div class="bg-pattern"></div>
            <div class="feature-list">
                <h3>Order Management Features</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Real-time Order Tracking</li>
                    <li><i class="fas fa-check"></i> Payment Management</li>
                    <li><i class="fas fa-check"></i> Delivery Coordination</li>
                    <li><i class="fas fa-check"></i> Advanced Analytics</li>
                    <li><i class="fas fa-check"></i> Customer Communication</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const toggleBtn = document.querySelector('.password-toggle i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleBtn.classList.remove('fa-eye');
            toggleBtn.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleBtn.classList.remove('fa-eye-slash');
            toggleBtn.classList.add('fa-eye');
        }
    }

    // Auto-focus username field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });

    // Form validation
    document.querySelector('.login-form').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Please fill in all fields!');
            return false;
        }
    });

    // Add loading state on form submit
    document.querySelector('.login-form').addEventListener('submit', function() {
        const submitBtn = document.querySelector('.login-btn');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;
    });
    </script>
</body>
</html>
