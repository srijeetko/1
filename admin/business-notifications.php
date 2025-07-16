<?php
session_start();
require_once '../includes/db_connection.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Business Notifications";
$message = '';
$messageType = '';

// Handle template status toggle
if (isset($_POST['toggle_template'])) {
    try {
        $templateId = $_POST['template_id'];
        $newStatus = $_POST['current_status'] === 'true' ? 'false' : 'true';

        $stmt = $pdo->prepare("UPDATE business_notification_templates SET is_active = ? WHERE template_id = ?");
        $stmt->execute([$newStatus === 'true' ? 1 : 0, $templateId]);

        $message = 'Template status updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error updating template: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle auto-send toggle
if (isset($_POST['toggle_auto_send'])) {
    try {
        $templateId = $_POST['template_id'];
        $newStatus = $_POST['current_auto_send'] === 'true' ? 'false' : 'true';

        $stmt = $pdo->prepare("UPDATE business_notification_templates SET auto_send = ? WHERE template_id = ?");
        $stmt->execute([$newStatus === 'true' ? 1 : 0, $templateId]);

        $message = 'Auto-send setting updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error updating auto-send: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get templates
try {
    $stmt = $pdo->query("
        SELECT bnt.*, au.username as created_by_name
        FROM business_notification_templates bnt
        LEFT JOIN admin_users au ON bnt.created_by = au.admin_id
        ORDER BY bnt.category, bnt.name
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get notification queue stats
    $stmt = $pdo->query("
        SELECT
            status,
            COUNT(*) as count
        FROM notification_queue
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY status
    ");
    $queueStats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $queueStats[$row['status']] = $row['count'];
    }

} catch (Exception $e) {
    $error = "Error loading templates: " . $e->getMessage();
    $templates = [];
    $queueStats = [];
}

include 'includes/admin-header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-bell"></i> Business Notifications</h1>
        <p>Manage automated WhatsApp notifications for orders, marketing, and customer engagement</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Notification Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($queueStats['pending'] ?? 0); ?></h3>
                <p>Pending</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($queueStats['sent'] ?? 0); ?></h3>
                <p>Sent (7 days)</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-times"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($queueStats['failed'] ?? 0); ?></h3>
                <p>Failed</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(count($templates)); ?></h3>
                <p>Templates</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="notification-template-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Template
            </a>
            <a href="notification-queue.php" class="btn btn-info">
                <i class="fas fa-list"></i> View Queue
            </a>
            <a href="notification-analytics.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="support-settings.php" class="btn btn-dark">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>