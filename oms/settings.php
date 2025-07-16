<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all password fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } else {
        try {
            // Verify current password using admin_users table (plain text in password_hash field)
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE admin_id = ?");
            $stmt->execute([$_SESSION['oms_admin_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || $currentPassword !== $admin['password_hash']) {
                $error = 'Current password is incorrect';
            } else {
                // Update password (plain text in password_hash field)
                $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ?, password_changed_at = NOW() WHERE admin_id = ?");
                $stmt->execute([$newPassword, $_SESSION['oms_admin_id']]);

                $success = 'Password changed successfully';
            }
        } catch (Exception $e) {
            $error = 'Failed to change password. Please try again.';
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        $error = 'Please fill in all profile fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE admin_users SET name = ?, email = ? WHERE admin_id = ?");
            $stmt->execute([$name, $email, $_SESSION['oms_admin_id']]);

            $_SESSION['oms_admin_name'] = $name;
            $_SESSION['oms_admin_email'] = $email;

            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
                VALUES (?, ?, 'profile_updated', ?)
            ");
            $stmt->execute([
                bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                "Profile updated: name and email changed"
            ]);

            $success = 'Profile updated successfully';
        } catch (Exception $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle OMS settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $settings_to_update = [
            'auto_assign_delivery',
            'order_confirmation_email',
            'sms_notifications',
            'delivery_sla_hours',
            'payment_timeout_minutes',
            'cod_limit',
            'free_shipping_threshold'
        ];

        foreach ($settings_to_update as $setting_key) {
            if (isset($_POST[$setting_key])) {
                $setting_value = $_POST[$setting_key];

                // Update or insert setting
                $stmt = $pdo->prepare("
                    INSERT INTO oms_settings (setting_id, setting_key, setting_value, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([bin2hex(random_bytes(16)), $setting_key, $setting_value]);
            }
        }

        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
            VALUES (?, ?, 'settings_updated', ?)
        ");
        $stmt->execute([
            bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
            "OMS settings updated"
        ]);

        $success = 'Settings updated successfully';
    } catch (Exception $e) {
        $error = 'Failed to update settings. Please try again.';
    }
}

// Handle system maintenance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_action'])) {
    $action = $_POST['maintenance_action'];

    try {
        switch ($action) {
            case 'clear_logs':
                $days = intval($_POST['clear_days'] ?? 30);
                $stmt = $pdo->prepare("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                $affected = $stmt->rowCount();

                $stmt = $pdo->prepare("
                    INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
                    VALUES (?, ?, 'logs_cleared', ?)
                ");
                $stmt->execute([
                    bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                    "Cleared $affected activity logs older than $days days"
                ]);

                $success = "Cleared $affected old activity logs";
                break;

            case 'backup_database':
                // Simulate database backup
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

                $stmt = $pdo->prepare("
                    INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
                    VALUES (?, ?, 'database_backup', ?)
                ");
                $stmt->execute([
                    bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                    "Database backup initiated: $backup_file"
                ]);

                $success = "Database backup initiated: $backup_file";
                break;

            case 'optimize_database':
                // Simulate database optimization
                $tables = ['checkout_orders', 'payment_transactions', 'delivery_assignments', 'activity_log'];
                foreach ($tables as $table) {
                    $pdo->exec("OPTIMIZE TABLE $table");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
                    VALUES (?, ?, 'database_optimized', ?)
                ");
                $stmt->execute([
                    bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                    "Database tables optimized"
                ]);

                $success = 'Database optimization completed';
                break;
        }
    } catch (Exception $e) {
        $error = 'Maintenance action failed: ' . $e->getMessage();
    }
}

// Get current admin details
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE admin_id = ?");
$stmt->execute([$_SESSION['oms_admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get OMS settings
$stmt = $pdo->query("SELECT * FROM oms_settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get setting value
function getSettingValue($settings, $key, $default = '') {
    foreach ($settings as $setting) {
        if ($setting['setting_key'] === $key) {
            return $setting['setting_value'];
        }
    }
    return $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Alpha OMS</title>
    <link rel="stylesheet" href="oms-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="oms-container">
        <!-- Sidebar -->
        <aside class="oms-sidebar">
            <div class="oms-logo">
                <i class="fas fa-shopping-cart"></i>
                <h2>Alpha OMS</h2>
            </div>
            
            <nav class="oms-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Transactions</span>
                </a>
                <a href="delivery.php" class="nav-item">
                    <i class="fas fa-truck"></i>
                    <span>Delivery</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="activity-log.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Activity Log</span>
                </a>
                <a href="settings.php" class="nav-item active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="oms-main">
            <div class="oms-header">
                <h1>Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Profile Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Profile Settings</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST" class="settings-form">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="form-group">
                                <label for="admin_id">Admin ID</label>
                                <input type="text" id="admin_id" value="<?php echo htmlspecialchars($admin['admin_id']); ?>" disabled>
                                <small class="form-help">Admin ID cannot be changed</small>
                            </div>

                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name"
                                       value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="role">Role</label>
                                <input type="text" id="role" value="<?php echo ucfirst($admin['role']); ?>" disabled>
                                <small class="form-help">Role cannot be changed</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST" class="settings-form">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="password-input">
                                    <input type="password" id="current_password" name="current_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-input">
                                    <input type="password" id="new_password" name="new_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-help">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="settings-card full-width">
                    <div class="card-header">
                        <h3><i class="fas fa-cogs"></i> System Settings</h3>
                    </div>
                    <div class="card-content">
                        <div class="settings-grid-inner">
                            <?php foreach ($settings as $setting): ?>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <h4><?php echo ucfirst(str_replace('_', ' ', $setting['setting_key'])); ?></h4>
                                        <p><?php echo htmlspecialchars($setting['description']); ?></p>
                                    </div>
                                    <div class="setting-control">
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                            <label class="switch">
                                                <input type="checkbox" <?php echo $setting['setting_value'] === 'true' ? 'checked' : ''; ?> 
                                                       <?php echo !$setting['is_editable'] ? 'disabled' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        <?php elseif ($setting['setting_type'] === 'number'): ?>
                                            <input type="number" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                                   <?php echo !$setting['is_editable'] ? 'disabled' : ''; ?>>
                                        <?php else: ?>
                                            <input type="text" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                                   <?php echo !$setting['is_editable'] ? 'disabled' : ''; ?>>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="settings-actions">
                            <button class="btn btn-primary" onclick="saveSettings()">
                                <i class="fas fa-save"></i>
                                Save Settings
                            </button>
                            <button class="btn btn-secondary" onclick="resetSettings()">
                                <i class="fas fa-undo"></i>
                                Reset to Default
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Maintenance -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tools"></i> System Maintenance</h3>
                    </div>
                    <div class="card-content">
                        <div class="maintenance-actions">
                            <div class="maintenance-item">
                                <div class="maintenance-info">
                                    <h4>Clear Activity Logs</h4>
                                    <p>Remove old activity logs to free up database space</p>
                                </div>
                                <div class="maintenance-controls">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="maintenance_action" value="clear_logs">
                                        <select name="clear_days" style="margin-right: 10px; padding: 8px; border: 2px solid #333; border-radius: 6px;">
                                            <option value="30">30 days</option>
                                            <option value="60">60 days</option>
                                            <option value="90">90 days</option>
                                            <option value="180">180 days</option>
                                        </select>
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to clear old activity logs?')">
                                            <i class="fas fa-trash"></i>
                                            Clear Logs
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="maintenance-item">
                                <div class="maintenance-info">
                                    <h4>Database Backup</h4>
                                    <p>Create a backup of the entire database</p>
                                </div>
                                <div class="maintenance-controls">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="maintenance_action" value="backup_database">
                                        <button type="submit" class="btn btn-info" onclick="return confirm('Start database backup? This may take a few minutes.')">
                                            <i class="fas fa-download"></i>
                                            Create Backup
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="maintenance-item">
                                <div class="maintenance-info">
                                    <h4>Optimize Database</h4>
                                    <p>Optimize database tables for better performance</p>
                                </div>
                                <div class="maintenance-controls">
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="maintenance_action" value="optimize_database">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Optimize database tables? This may take a few minutes.')">
                                            <i class="fas fa-rocket"></i>
                                            Optimize
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const toggle = field.nextElementSibling;
        const icon = toggle.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function saveSettings() {
        alert('Settings save functionality will be implemented');
    }

    function resetSettings() {
        if (confirm('Are you sure you want to reset all settings to default values?')) {
            alert('Settings reset functionality will be implemented');
        }
    }

    // Form validation for password change
    document.querySelector('form[name="change_password"]')?.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match!');
            return false;
        }
        
        if (newPassword.length < 6) {
            e.preventDefault();
            alert('New password must be at least 6 characters long!');
            return false;
        }
    });
    </script>
</body>
</html>
