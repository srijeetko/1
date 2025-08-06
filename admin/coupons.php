<?php
session_start();
include '../includes/db_connection.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_coupon') {
        $coupon_id = bin2hex(random_bytes(16));
        $code = strtoupper(trim($_POST['code']));
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $minimum_order_amount = floatval($_POST['minimum_order_amount']);
        $usage_limit = intval($_POST['usage_limit']);
        $expires_at = $_POST['expires_at'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (coupon_id, code, description, discount_type, discount_value, minimum_order_amount, usage_limit, usage_count, expires_at, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([$coupon_id, $code, $description, $discount_type, $discount_value, $minimum_order_amount, $usage_limit, $expires_at, $is_active]);
            $success_message = "Coupon created successfully!";
        } catch (Exception $e) {
            $error_message = "Error creating coupon: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit_coupon') {
        $coupon_id = $_POST['coupon_id'];
        $code = strtoupper(trim($_POST['code']));
        $description = trim($_POST['description']);
        $discount_type = $_POST['discount_type'];
        $discount_value = floatval($_POST['discount_value']);
        $minimum_order_amount = floatval($_POST['minimum_order_amount']);
        $usage_limit = intval($_POST['usage_limit']);
        $expires_at = $_POST['expires_at'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE coupons 
                SET code = ?, description = ?, discount_type = ?, discount_value = ?, minimum_order_amount = ?, usage_limit = ?, expires_at = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                WHERE coupon_id = ?
            ");
            $stmt->execute([$code, $description, $discount_type, $discount_value, $minimum_order_amount, $usage_limit, $expires_at, $is_active, $coupon_id]);
            $success_message = "Coupon updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating coupon: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete_coupon') {
        $coupon_id = $_POST['coupon_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE coupon_id = ?");
            $stmt->execute([$coupon_id]);
            $success_message = "Coupon deleted successfully!";
        } catch (Exception $e) {
            $error_message = "Error deleting coupon: " . $e->getMessage();
        }
    }
    
    if ($action === 'toggle_status') {
        $coupon_id = $_POST['coupon_id'];
        $new_status = intval($_POST['new_status']);
        
        try {
            $stmt = $pdo->prepare("UPDATE coupons SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE coupon_id = ?");
            $stmt->execute([$new_status, $coupon_id]);
            $success_message = "Coupon status updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating coupon status: " . $e->getMessage();
        }
    }
}

// Get coupon for editing
$edit_coupon = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE coupon_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_coupon = $stmt->fetch();
}

// Fetch all coupons
$stmt = $pdo->query("
    SELECT *, 
    CASE 
        WHEN expires_at < NOW() THEN 'Expired'
        WHEN is_active = 0 THEN 'Inactive'
        ELSE 'Active'
    END as status
    FROM coupons 
    ORDER BY created_at DESC
");
$coupons = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .coupon-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .coupon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .coupon-header h1 {
            color: #1f2937;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .coupon-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .coupon-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
        }
        
        .btn-edit:hover {
            background: #d97706;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .btn-toggle {
            background: #6b7280;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #4b5563;
        }
        
        .discount-display {
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>
<body class="admin-page">
    <!-- Admin Header -->
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="coupon-container">
                <div class="coupon-header">
                    <h1><i class="fas fa-ticket-alt"></i> Coupon Management</h1>
                    <button class="btn-primary" onclick="toggleCouponForm()">
                        <i class="fas fa-plus"></i> Add New Coupon
                    </button>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Coupon Form -->
                <div id="couponForm" class="coupon-form" style="display: <?php echo $edit_coupon ? 'block' : 'none'; ?>;">
                    <h2><?php echo $edit_coupon ? 'Edit Coupon' : 'Add New Coupon'; ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_coupon ? 'edit_coupon' : 'add_coupon'; ?>">
                        <?php if ($edit_coupon): ?>
                            <input type="hidden" name="coupon_id" value="<?php echo htmlspecialchars($edit_coupon['coupon_id']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="code">Coupon Code *</label>
                                <input type="text" id="code" name="code" required 
                                       value="<?php echo $edit_coupon ? htmlspecialchars($edit_coupon['code']) : ''; ?>"
                                       placeholder="e.g., WELCOME10">
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_type">Discount Type *</label>
                                <select id="discount_type" name="discount_type" required>
                                    <option value="percentage" <?php echo ($edit_coupon && $edit_coupon['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="fixed" <?php echo ($edit_coupon && $edit_coupon['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_value">Discount Value *</label>
                                <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required
                                       value="<?php echo $edit_coupon ? $edit_coupon['discount_value'] : ''; ?>"
                                       placeholder="10.00">
                            </div>
                            
                            <div class="form-group">
                                <label for="minimum_order_amount">Minimum Order Amount (₹)</label>
                                <input type="number" id="minimum_order_amount" name="minimum_order_amount" step="0.01" min="0"
                                       value="<?php echo $edit_coupon ? $edit_coupon['minimum_order_amount'] : '0'; ?>"
                                       placeholder="0.00">
                            </div>
                            
                            <div class="form-group">
                                <label for="usage_limit">Usage Limit</label>
                                <input type="number" id="usage_limit" name="usage_limit" min="1"
                                       value="<?php echo $edit_coupon ? $edit_coupon['usage_limit'] : ''; ?>"
                                       placeholder="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="expires_at">Expiry Date *</label>
                                <input type="datetime-local" id="expires_at" name="expires_at" required
                                       value="<?php echo $edit_coupon ? date('Y-m-d\TH:i', strtotime($edit_coupon['expires_at'])) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Brief description of the coupon"><?php echo $edit_coupon ? htmlspecialchars($edit_coupon['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (!$edit_coupon || $edit_coupon['is_active']) ? 'checked' : ''; ?>>
                            <label for="is_active">Active</label>
                        </div>
                        
                        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_coupon ? 'Update Coupon' : 'Create Coupon'; ?>
                            </button>
                            <button type="button" class="btn-sm btn-toggle" onclick="cancelEdit()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Coupons Table -->
                <div class="coupon-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Min. Order</th>
                                <th>Usage</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <br>No coupons found. Create your first coupon!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #1f2937; font-family: monospace;">
                                                <?php echo htmlspecialchars($coupon['code']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($coupon['description'] ?: 'No description'); ?>
                                        </td>
                                        <td>
                                            <span class="discount-display">
                                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                    <?php echo $coupon['discount_value']; ?>%
                                                <?php else: ?>
                                                    ₹<?php echo number_format($coupon['discount_value'], 2); ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($coupon['minimum_order_amount'] > 0): ?>
                                                ₹<?php echo number_format($coupon['minimum_order_amount'], 2); ?>
                                            <?php else: ?>
                                                <span style="color: #6b7280;">No minimum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $coupon['usage_count']; ?> /
                                            <?php echo $coupon['usage_limit'] ?: '∞'; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($coupon['expires_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($coupon['status']); ?>">
                                                <?php echo $coupon['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit=<?php echo $coupon['coupon_id']; ?>" class="btn-sm btn-edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <form method="POST" style="display: inline;"
                                                      onsubmit="return confirm('Are you sure you want to <?php echo $coupon['is_active'] ? 'deactivate' : 'activate'; ?> this coupon?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['coupon_id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $coupon['is_active'] ? 0 : 1; ?>">
                                                    <button type="submit" class="btn-sm btn-toggle" title="<?php echo $coupon['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $coupon['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" style="display: inline;"
                                                      onsubmit="return confirm('Are you sure you want to delete this coupon? This action cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_coupon">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['coupon_id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCouponForm() {
            const form = document.getElementById('couponForm');
            const isVisible = form.style.display !== 'none';
            form.style.display = isVisible ? 'none' : 'block';

            if (!isVisible) {
                // Reset form if showing
                form.querySelector('form').reset();
                form.querySelector('input[name="action"]').value = 'add_coupon';
                form.querySelector('h2').textContent = 'Add New Coupon';

                // Remove coupon_id input if exists
                const couponIdInput = form.querySelector('input[name="coupon_id"]');
                if (couponIdInput) {
                    couponIdInput.remove();
                }

                // Scroll to form
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function cancelEdit() {
            const form = document.getElementById('couponForm');
            form.style.display = 'none';

            // Remove edit parameter from URL
            const url = new URL(window.location);
            url.searchParams.delete('edit');
            window.history.replaceState({}, document.title, url);
        }

        // Auto-generate coupon code
        document.getElementById('code').addEventListener('focus', function() {
            if (!this.value) {
                const randomCode = 'COUPON' + Math.random().toString(36).substr(2, 6).toUpperCase();
                this.value = randomCode;
            }
        });

        // Update discount value placeholder based on type
        document.getElementById('discount_type').addEventListener('change', function() {
            const discountValue = document.getElementById('discount_value');
            if (this.value === 'percentage') {
                discountValue.placeholder = '10 (for 10%)';
                discountValue.max = '100';
            } else {
                discountValue.placeholder = '50.00 (for ₹50)';
                discountValue.removeAttribute('max');
            }
        });

        // Set minimum expiry date to today
        document.addEventListener('DOMContentLoaded', function() {
            const expiryInput = document.getElementById('expires_at');
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            expiryInput.min = now.toISOString().slice(0, 16);

            // Set default expiry to 30 days from now if empty
            if (!expiryInput.value) {
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 30);
                futureDate.setMinutes(futureDate.getMinutes() - futureDate.getTimezoneOffset());
                expiryInput.value = futureDate.toISOString().slice(0, 16);
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const discountType = document.getElementById('discount_type').value;
            const discountValue = parseFloat(document.getElementById('discount_value').value);

            if (discountType === 'percentage' && discountValue > 100) {
                e.preventDefault();
                alert('Percentage discount cannot be more than 100%');
                return false;
            }

            if (discountValue <= 0) {
                e.preventDefault();
                alert('Discount value must be greater than 0');
                return false;
            }
        });
    </script>
</body>
</html>
