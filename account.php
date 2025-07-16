<?php
require_once 'includes/auth.php';

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
$success = '';
$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php?logged_out=1');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updateData = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'gender' => $_POST['gender'] ?? null
    ];
    
    if (empty($updateData['first_name']) || empty($updateData['last_name'])) {
        $error = 'First name and last name are required';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, gender = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([
                $updateData['first_name'],
                $updateData['last_name'],
                $updateData['phone'],
                $updateData['date_of_birth'] ?: null,
                $updateData['gender'] ?: null,
                $user['user_id']
            ]);
            
            $success = 'Profile updated successfully!';
            $user = $auth->getCurrentUser(); // Refresh user data
        } catch (Exception $e) {
            $error = 'Failed to update profile';
        }
    }
}

// Get user orders
try {
    $stmt = $pdo->prepare("
        SELECT order_id, order_number, total_amount, order_status, created_at 
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['user_id']]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
}

// Get user addresses
try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $addresses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Alpha Nutrition</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="account-container">
        <div class="account-sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <nav class="account-nav">
                <a href="#dashboard" class="nav-item active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="#profile" class="nav-item" onclick="showSection('profile')">
                    <i class="fas fa-user-edit"></i>
                    Profile
                </a>
                <a href="#orders" class="nav-item" onclick="showSection('orders')">
                    <i class="fas fa-shopping-bag"></i>
                    Orders
                </a>
                <a href="#addresses" class="nav-item" onclick="showSection('addresses')">
                    <i class="fas fa-map-marker-alt"></i>
                    Addresses
                </a>
                <a href="#preferences" class="nav-item" onclick="showSection('preferences')">
                    <i class="fas fa-cog"></i>
                    Preferences
                </a>
                <a href="?logout=1" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>

        <div class="account-content">
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

            <!-- Dashboard Section -->
            <div id="dashboard" class="account-section active">
                <h2>Dashboard</h2>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($recentOrders); ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($addresses); ?></h3>
                            <p>Saved Addresses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>0</h3>
                            <p>Wishlist Items</p>
                        </div>
                    </div>
                </div>

                <div class="recent-orders">
                    <h3>Recent Orders</h3>
                    <?php if (empty($recentOrders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <p>No orders yet</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <h4>Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                                        <p>₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <span class="order-status status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </div>
                                    <div class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Section -->
            <div id="profile" class="account-section">
                <h2>Profile Information</h2>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small class="form-help">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Orders Section -->
            <div id="orders" class="account-section">
                <h2>Order History</h2>
                
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <p>No orders found</p>
                        <a href="products.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="order-status status-<?php echo $order['order_status']; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Addresses Section -->
            <div id="addresses" class="account-section">
                <h2>Saved Addresses</h2>
                
                <div class="addresses-grid">
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-card">
                            <div class="address-header">
                                <h4><?php echo ucfirst($address['address_type']); ?> Address</h4>
                                <?php if ($address['is_default']): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                            </div>
                            <div class="address-details">
                                <p><strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong></p>
                                <p><?php echo htmlspecialchars($address['address_line_1']); ?></p>
                                <?php if ($address['address_line_2']): ?>
                                    <p><?php echo htmlspecialchars($address['address_line_2']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?></p>
                                <?php if ($address['phone']): ?>
                                    <p>Phone: <?php echo htmlspecialchars($address['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="address-actions">
                                <button class="btn btn-sm">Edit</button>
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="address-card add-new">
                        <div class="add-address-content">
                            <i class="fas fa-plus"></i>
                            <h4>Add New Address</h4>
                            <p>Add a new delivery address</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preferences Section -->
            <div id="preferences" class="account-section">
                <h2>Account Preferences</h2>
                
                <div class="preferences-form">
                    <div class="preference-group">
                        <h3>Notifications</h3>
                        <div class="preference-item">
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                            <div class="preference-info">
                                <h4>Email Notifications</h4>
                                <p>Receive order updates and promotional emails</p>
                            </div>
                        </div>
                        
                        <div class="preference-item">
                            <label class="switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                            <div class="preference-info">
                                <h4>SMS Notifications</h4>
                                <p>Receive order updates via SMS</p>
                            </div>
                        </div>
                    </div>

                    <div class="preference-group">
                        <h3>Privacy</h3>
                        <div class="preference-item">
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                            <div class="preference-info">
                                <h4>Newsletter Subscription</h4>
                                <p>Receive health tips and product updates</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.account-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show selected section
        document.getElementById(sectionId).classList.add('active');
        
        // Add active class to clicked nav item
        event.target.classList.add('active');
    }
    </script>
</body>
</html>
