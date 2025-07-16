<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // Validate inputs
        if (empty($current_password)) {
            throw new Exception('Current password is required');
        }
        
        if (empty($new_password)) {
            throw new Exception('New password is required');
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('New password and confirmation do not match');
        }
        
        // Check if new password is different from current
        if ($current_password === $new_password) {
            throw new Exception('New password must be different from current password');
        }
        
        // Verify current password
        $stmt = $pdo->prepare('SELECT password FROM admin_users WHERE admin_id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            throw new Exception('Admin user not found');
        }
        
        if (!password_verify($current_password, $admin['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Hash new password with strong options
        $new_password_hash = password_hash($new_password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);

        // Update password in database
        $stmt = $pdo->prepare('UPDATE admin_users SET password = ?, password_changed_at = NOW(), updated_at = NOW() WHERE admin_id = ?');
        $result = $stmt->execute([$new_password_hash, $_SESSION['admin_id']]);

        if ($result) {
            $success = 'Password changed successfully! Please remember to keep your new password secure.';

            // Log the password change with IP address
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            error_log("Password changed for admin ID: {$_SESSION['admin_id']} from IP: {$ip_address} at " . date('Y-m-d H:i:s'));

            // Optional: Send email notification (if email system is set up)
            // sendPasswordChangeNotification($_SESSION['admin_id'], $ip_address);

            // Clear form data
            $_POST = [];
        } else {
            throw new Exception('Failed to update password. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get admin info for display
$stmt = $pdo->prepare('SELECT name, email, password_changed_at, created_at FROM admin_users WHERE admin_id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin_info = $stmt->fetch();

// Calculate password age
$password_age_days = 0;
if ($admin_info['password_changed_at']) {
    $password_changed = new DateTime($admin_info['password_changed_at']);
    $now = new DateTime();
    $password_age_days = $now->diff($password_changed)->days;
} else if ($admin_info['created_at']) {
    $account_created = new DateTime($admin_info['created_at']);
    $now = new DateTime();
    $password_age_days = $now->diff($account_created)->days;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="admin-content-header">
                <h1><i class="fas fa-key"></i> Change Password</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="index.php" class="button button-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; max-width: 1200px;">
                <!-- Change Password Form -->
                <div class="admin-form">
                    <h2 style="margin-top: 0; color: #1f2937; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-lock" style="color: #3b82f6;"></i>
                        Change Password
                    </h2>
                    
                    <form method="POST" id="password-form">
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <div style="position: relative;">
                                <input type="password" id="current_password" name="current_password" required 
                                       placeholder="Enter your current password"
                                       style="padding-right: 3rem;">
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password')"
                                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <div style="position: relative;">
                                <input type="password" id="new_password" name="new_password" required 
                                       placeholder="Enter new password (min. 8 characters)"
                                       style="padding-right: 3rem;"
                                       onkeyup="checkPasswordStrength()">
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')"
                                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="password-strength" style="margin-top: 0.5rem; font-size: 0.875rem;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <div style="position: relative;">
                                <input type="password" id="confirm_password" name="confirm_password" required 
                                       placeholder="Confirm your new password"
                                       style="padding-right: 3rem;"
                                       onkeyup="checkPasswordMatch()">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')"
                                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="password-match" style="margin-top: 0.5rem; font-size: 0.875rem;"></div>
                        </div>
                        
                        <div style="margin: 1.5rem 0;">
                            <button type="button" class="button button-secondary" onclick="generatePassword()" style="width: 100%;">
                                <i class="fas fa-magic"></i> Generate Strong Password
                            </button>
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0 0 0; text-align: center;">
                                Click to generate a secure password automatically
                            </p>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="button" id="submit-btn">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                            <button type="button" class="button button-secondary" onclick="clearForm()">
                                <i class="fas fa-times"></i> Clear Form
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Information & Security Tips -->
                <div>
                    <!-- Account Info -->
                    <div class="admin-form" style="margin-bottom: 2rem;">
                        <h3 style="margin-top: 0; color: #1f2937; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-user-circle" style="color: #3b82f6;"></i>
                            Account Information
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label style="font-weight: 600; color: #374151; font-size: 0.9rem;">Name</label>
                                <p style="margin: 0.25rem 0 0 0; color: #1f2937; font-size: 1rem;">
                                    <?php echo htmlspecialchars($admin_info['name'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label style="font-weight: 600; color: #374151; font-size: 0.9rem;">Email</label>
                                <p style="margin: 0.25rem 0 0 0; color: #1f2937; font-size: 1rem;">
                                    <?php echo htmlspecialchars($admin_info['email'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label style="font-weight: 600; color: #374151; font-size: 0.9rem;">Current Session</label>
                                <p style="margin: 0.25rem 0 0 0; color: #1f2937; font-size: 1rem;">
                                    <?php echo date('M j, Y \a\t g:i A'); ?>
                                </p>
                            </div>

                            <div>
                                <label style="font-weight: 600; color: #374151; font-size: 0.9rem;">Password Age</label>
                                <p style="margin: 0.25rem 0 0 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <?php
                                    if ($password_age_days == 0) {
                                        echo '<span style="color: #10b981;">Just changed</span>';
                                    } else if ($password_age_days < 30) {
                                        echo '<span style="color: #10b981;">' . $password_age_days . ' days</span>';
                                    } else if ($password_age_days < 90) {
                                        echo '<span style="color: #f59e0b;">' . $password_age_days . ' days</span>';
                                    } else {
                                        echo '<span style="color: #ef4444;">' . $password_age_days . ' days</span>';
                                        echo '<i class="fas fa-exclamation-triangle" style="color: #ef4444;" title="Consider changing your password"></i>';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tips -->
                    <div class="admin-form">
                        <h3 style="margin-top: 0; color: #1f2937; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-shield-alt" style="color: #10b981;"></i>
                            Security Tips
                        </h3>
                        
                        <ul style="margin: 0; padding-left: 1.5rem; color: #4b5563; line-height: 1.6;">
                            <li style="margin-bottom: 0.75rem;">
                                <strong>Use a strong password:</strong> At least 8 characters with a mix of letters, numbers, and symbols
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <strong>Make it unique:</strong> Don't reuse passwords from other accounts
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <strong>Change regularly:</strong> Update your password every 3-6 months
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <strong>Keep it private:</strong> Never share your admin password with anyone
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <strong>Use a password manager:</strong> Consider using a password manager for better security
                            </li>
                        </ul>
                        
                        <div style="margin-top: 1.5rem; padding: 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                            <p style="margin: 0; color: #166534; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Remember:</strong> Your password protects sensitive business data. Choose wisely!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) strength++;
            else feedback.push('at least 8 characters');
            
            // Uppercase check
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            // Lowercase check
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            // Number check
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('number');
            
            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('special character');
            
            let strengthText = '';
            let strengthColor = '';
            
            if (strength < 2) {
                strengthText = 'Weak';
                strengthColor = '#ef4444';
            } else if (strength < 4) {
                strengthText = 'Medium';
                strengthColor = '#f59e0b';
            } else {
                strengthText = 'Strong';
                strengthColor = '#10b981';
            }
            
            let html = `<span style="color: ${strengthColor}; font-weight: 600;">Password Strength: ${strengthText}</span>`;
            
            if (feedback.length > 0) {
                html += `<br><span style="color: #6b7280;">Add: ${feedback.join(', ')}</span>`;
            }
            
            strengthDiv.innerHTML = html;
        }

        // Check password match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<span style="color: #10b981; font-weight: 600;"><i class="fas fa-check"></i> Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span style="color: #ef4444; font-weight: 600;"><i class="fas fa-times"></i> Passwords do not match</span>';
            }
        }

        // Generate strong password
        function generatePassword() {
            const length = 16;
            const charset = {
                lowercase: 'abcdefghijklmnopqrstuvwxyz',
                uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                numbers: '0123456789',
                symbols: '!@#$%^&*()_+-=[]{}|;:,.<>?'
            };

            let password = '';

            // Ensure at least one character from each category
            password += charset.lowercase[Math.floor(Math.random() * charset.lowercase.length)];
            password += charset.uppercase[Math.floor(Math.random() * charset.uppercase.length)];
            password += charset.numbers[Math.floor(Math.random() * charset.numbers.length)];
            password += charset.symbols[Math.floor(Math.random() * charset.symbols.length)];

            // Fill the rest randomly
            const allChars = charset.lowercase + charset.uppercase + charset.numbers + charset.symbols;
            for (let i = password.length; i < length; i++) {
                password += allChars[Math.floor(Math.random() * allChars.length)];
            }

            // Shuffle the password
            password = password.split('').sort(() => Math.random() - 0.5).join('');

            // Set the generated password
            document.getElementById('new_password').value = password;
            document.getElementById('confirm_password').value = password;

            // Update strength and match indicators
            checkPasswordStrength();
            checkPasswordMatch();

            // Show the password temporarily
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const originalType = newPasswordField.type;

            newPasswordField.type = 'text';
            confirmPasswordField.type = 'text';

            // Highlight the fields
            newPasswordField.style.backgroundColor = '#f0fdf4';
            confirmPasswordField.style.backgroundColor = '#f0fdf4';

            // Show success message
            alert('Strong password generated! The password is temporarily visible. Please copy it to a secure location if needed.');

            // Hide password after 10 seconds
            setTimeout(() => {
                newPasswordField.type = originalType;
                confirmPasswordField.type = originalType;
                newPasswordField.style.backgroundColor = '';
                confirmPasswordField.style.backgroundColor = '';
            }, 10000);
        }

        // Clear form
        function clearForm() {
            if (confirm('Are you sure you want to clear the form?')) {
                document.getElementById('password-form').reset();
                document.getElementById('password-strength').innerHTML = '';
                document.getElementById('password-match').innerHTML = '';
            }
        }

        // Form submission validation
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing Password...';
            submitBtn.disabled = true;
        });
    </script>

    <style>
    .password-toggle:hover {
        color: #374151 !important;
    }
    
    .password-toggle:focus {
        outline: none;
    }
    
    .form-group input:focus + .password-toggle {
        color: #3b82f6 !important;
    }
    </style>
</body>
</html>
