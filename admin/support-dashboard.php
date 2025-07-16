<?php
session_start();
require_once '../includes/db_connection.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pageTitle = "WhatsApp Support Dashboard";
include 'includes/admin-header.php';

// Get dashboard statistics
try {
    // Total tickets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets");
    $totalTickets = $stmt->fetch()['total'];

    // Open tickets
    $stmt = $pdo->query("SELECT COUNT(*) as open FROM support_tickets WHERE status IN ('open', 'in_progress')");
    $openTickets = $stmt->fetch()['open'];

    // Active conversations
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM whatsapp_conversations WHERE status = 'active'");
    $activeConversations = $stmt->fetch()['active'];

    // Total agents
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM support_agents WHERE status = 'active'");
    $totalAgents = $stmt->fetch()['total'];

    // Messages today
    $stmt = $pdo->query("SELECT COUNT(*) as today FROM whatsapp_messages WHERE DATE(created_at) = CURDATE()");
    $messagesToday = $stmt->fetch()['today'];

    // Recent tickets
    $stmt = $pdo->prepare("
        SELECT st.*, sa.name as agent_name, u.first_name, u.last_name
        FROM support_tickets st
        LEFT JOIN support_agents sa ON st.assigned_agent_id = sa.agent_id
        LEFT JOIN users u ON st.user_id = u.user_id
        ORDER BY st.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check API configuration
    $stmt = $pdo->prepare("SELECT setting_value FROM support_settings WHERE setting_key = 'interakt_api_key'");
    $stmt->execute();
    $apiKey = $stmt->fetch()['setting_value'] ?? '';
    $apiConfigured = !empty($apiKey);

} catch (Exception $e) {
    $error = "Error loading dashboard: " . $e->getMessage();
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fab fa-whatsapp"></i> WhatsApp Support Dashboard</h1>
        <p>Manage customer support conversations and automated notifications</p>
    </div>

    <?php if (!$apiConfigured): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>API Configuration Required:</strong>
        Interakt WhatsApp API is not configured.
        <a href="support-settings.php" class="btn btn-sm btn-primary">Configure Now</a>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($totalTickets); ?></h3>
                <p>Total Tickets</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($openTickets); ?></h3>
                <p>Open Tickets</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fab fa-whatsapp"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($activeConversations); ?></h3>
                <p>Active Chats</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($totalAgents); ?></h3>
                <p>Active Agents</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($messagesToday); ?></h3>
                <p>Messages Today</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="support-conversations.php" class="btn btn-primary">
                <i class="fas fa-comments"></i> View Conversations
            </a>
            <a href="support-agents.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Manage Agents
            </a>
            <a href="support-templates.php" class="btn btn-info">
                <i class="fas fa-file-alt"></i> Message Templates
            </a>
            <a href="business-notifications.php" class="btn btn-warning">
                <i class="fas fa-bell"></i> Business Notifications
            </a>
            <a href="support-analytics.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="support-settings.php" class="btn btn-dark">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="recent-tickets">
        <h2>Recent Support Tickets</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Agent</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTickets)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No tickets found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentTickets as $ticket): ?>
                    <tr>
                        <td>
                            <span class="ticket-number"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                        </td>
                        <td>
                            <?php if ($ticket['first_name']): ?>
                                <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($ticket['customer_phone']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['subject'] ?: 'WhatsApp Support Request'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                <?php echo ucfirst($ticket['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['agent_name'] ?: 'Unassigned'); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($ticket['created_at'])); ?></td>
                        <td>
                            <a href="support-ticket-view.php?id=<?php echo $ticket['ticket_id']; ?>"
                               class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>