<?php
session_start();
require_once '../includes/db_connection.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Support Settings";
$message = '';
$messageType = '';

// Add custom CSS and JS to head
$customCSS = '<link rel="stylesheet" href="css/support-admin.css">';
$customJS = '
<style>
/* Inline CSS for immediate loading */
html { scroll-behavior: smooth; }
.settings-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}
.settings-nav .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.settings-nav .nav-link:hover,
.settings-nav .nav-link.active {
    background: #007bff;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}
.scroll-to-top.show {
    opacity: 1;
    visibility: visible;
}
.scroll-to-top:hover {
    background: #0056b3;
    transform: translateY(-2px);
}
.settings-section {
    scroll-margin-top: 20px;
}
</style>';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'interakt_api_key' => $_POST['interakt_api_key'] ?? '',
            'interakt_base_url' => $_POST['interakt_base_url'] ?? 'https://api.interakt.ai/v1/public',
            'auto_assign_tickets' => isset($_POST['auto_assign_tickets']) ? 'true' : 'false',
            'business_hours_start' => $_POST['business_hours_start'] ?? '09:00',
            'business_hours_end' => $_POST['business_hours_end'] ?? '18:00',
            'auto_response_enabled' => isset($_POST['auto_response_enabled']) ? 'true' : 'false',
            'max_response_time_minutes' => $_POST['max_response_time_minutes'] ?? '30',
            'customer_satisfaction_enabled' => isset($_POST['customer_satisfaction_enabled']) ? 'true' : 'false',
            'business_notifications_enabled' => isset($_POST['business_notifications_enabled']) ? 'true' : 'false',
            'cart_abandonment_delay_hours' => $_POST['cart_abandonment_delay_hours'] ?? '2',
            'feedback_request_delay_days' => $_POST['feedback_request_delay_days'] ?? '3'
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO support_settings (setting_id, setting_key, setting_value, updated_by, updated_at)
                VALUES (UUID(), ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $_SESSION['admin_id']]);
        }

        $message = 'Settings saved successfully!';
        $messageType = 'success';

    } catch (Exception $e) {
        $message = 'Error saving settings: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Load current settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM support_settings");
    $currentSettings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $currentSettings = [];
}

include 'includes/admin-header.php';
?>

<?php echo $customJS; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-cog"></i> Support System Settings</h1>
        <p>Configure Interakt WhatsApp API and support system preferences</p>

        <!-- Quick Navigation -->
        <div class="settings-nav">
            <a href="#api-config" class="nav-link"><i class="fab fa-whatsapp"></i> API Configuration</a>
            <a href="#automation" class="nav-link"><i class="fas fa-robot"></i> Automation</a>
            <a href="#business-hours" class="nav-link"><i class="fas fa-clock"></i> Business Hours</a>
            <a href="#notifications" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
            <a href="#api-test" class="nav-link"><i class="fas fa-vial"></i> API Test</a>
            <a href="#webhook-config" class="nav-link"><i class="fas fa-webhook"></i> Webhooks</a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- API Configuration Instructions -->
    <div class="settings-section" id="api-config">
        <h2><i class="fab fa-whatsapp"></i> Interakt WhatsApp API Configuration</h2>

        <div class="alert alert-info">
            <h4><i class="fas fa-info-circle"></i> How to Get Your Interakt API Key:</h4>
            <ol>
                <li><strong>Sign up for Interakt:</strong> Visit <a href="https://www.interakt.shop" target="_blank">interakt.shop</a> and create an account</li>
                <li><strong>Apply for WhatsApp Business API:</strong> Complete the WhatsApp Business API application process</li>
                <li><strong>Get API Key:</strong> Once approved, go to Settings → Developer Settings in your Interakt dashboard</li>
                <li><strong>Copy API Key:</strong> Copy your API key and paste it in the field below</li>
                <li><strong>Configure Webhooks:</strong> Set webhook URL to: <code><?php echo (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/api/interakt-webhook.php</code></li>
            </ol>
            <p><strong>Note:</strong> You'll need a Growth or Advanced plan to use webhooks and APIs.</p>
        </div>

        <form method="POST" class="settings-form">
            <div class="form-group">
                <label for="interakt_api_key">
                    <i class="fas fa-key"></i> Interakt API Key *
                </label>
                <input type="password"
                       id="interakt_api_key"
                       name="interakt_api_key"
                       class="form-control"
                       value="<?php echo htmlspecialchars($currentSettings['interakt_api_key'] ?? ''); ?>"
                       placeholder="Enter your Interakt API key">
                <small class="form-text">Get this from your Interakt dashboard → Settings → Developer Settings</small>
            </div>

            <div class="form-group">
                <label for="interakt_base_url">
                    <i class="fas fa-link"></i> Interakt API Base URL
                </label>
                <input type="url"
                       id="interakt_base_url"
                       name="interakt_base_url"
                       class="form-control"
                       value="<?php echo htmlspecialchars($currentSettings['interakt_base_url'] ?? 'https://api.interakt.ai/v1/public'); ?>">
                <small class="form-text">Default: https://api.interakt.ai/v1/public</small>
            </div>

            <h3 id="automation"><i class="fas fa-robot"></i> Automation Settings</h3>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox"
                           id="auto_assign_tickets"
                           name="auto_assign_tickets"
                           class="form-check-input"
                           <?php echo ($currentSettings['auto_assign_tickets'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label for="auto_assign_tickets" class="form-check-label">
                        Auto-assign tickets to available agents
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox"
                           id="auto_response_enabled"
                           name="auto_response_enabled"
                           class="form-check-input"
                           <?php echo ($currentSettings['auto_response_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label for="auto_response_enabled" class="form-check-label">
                        Enable automated responses for common queries
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox"
                           id="business_notifications_enabled"
                           name="business_notifications_enabled"
                           class="form-check-input"
                           <?php echo ($currentSettings['business_notifications_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label for="business_notifications_enabled" class="form-check-label">
                        Enable automated business notifications (order updates, etc.)
                    </label>
                </div>
            </div>

            <h3 id="business-hours"><i class="fas fa-clock"></i> Business Hours & Timing</h3>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="business_hours_start">
                        <i class="fas fa-sun"></i> Business Hours Start
                    </label>
                    <input type="time"
                           id="business_hours_start"
                           name="business_hours_start"
                           class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['business_hours_start'] ?? '09:00'); ?>">
                </div>

                <div class="form-group col-md-6">
                    <label for="business_hours_end">
                        <i class="fas fa-moon"></i> Business Hours End
                    </label>
                    <input type="time"
                           id="business_hours_end"
                           name="business_hours_end"
                           class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['business_hours_end'] ?? '18:00'); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="max_response_time_minutes">
                    <i class="fas fa-stopwatch"></i> Maximum Response Time (minutes)
                </label>
                <input type="number"
                       id="max_response_time_minutes"
                       name="max_response_time_minutes"
                       class="form-control"
                       value="<?php echo htmlspecialchars($currentSettings['max_response_time_minutes'] ?? '30'); ?>"
                       min="1" max="1440">
                <small class="form-text">Target response time for support tickets</small>
            </div>

            <h3 id="notifications"><i class="fas fa-bell"></i> Notification Timing</h3>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cart_abandonment_delay_hours">
                        <i class="fas fa-shopping-cart"></i> Cart Abandonment Delay (hours)
                    </label>
                    <input type="number"
                           id="cart_abandonment_delay_hours"
                           name="cart_abandonment_delay_hours"
                           class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['cart_abandonment_delay_hours'] ?? '2'); ?>"
                           min="1" max="72">
                    <small class="form-text">Hours to wait before sending cart abandonment reminder</small>
                </div>

                <div class="form-group col-md-6">
                    <label for="feedback_request_delay_days">
                        <i class="fas fa-star"></i> Feedback Request Delay (days)
                    </label>
                    <input type="number"
                           id="feedback_request_delay_days"
                           name="feedback_request_delay_days"
                           class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['feedback_request_delay_days'] ?? '3'); ?>"
                           min="1" max="30">
                    <small class="form-text">Days to wait after delivery before requesting feedback</small>
                </div>
            </div>

            <h3><i class="fas fa-chart-line"></i> Customer Experience</h3>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox"
                           id="customer_satisfaction_enabled"
                           name="customer_satisfaction_enabled"
                           class="form-check-input"
                           <?php echo ($currentSettings['customer_satisfaction_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                    <label for="customer_satisfaction_enabled" class="form-check-label">
                        Enable customer satisfaction surveys after ticket resolution
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="support-dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>

    <!-- API Testing Section -->
    <div class="settings-section" id="api-test">
        <h2><i class="fas fa-vial"></i> API Connection Test</h2>
        <p>Test your Interakt API connection after saving your settings.</p>

        <div id="api-test-result" class="alert" style="display: none;"></div>

        <button type="button" id="test-api-btn" class="btn btn-info" onclick="testApiConnection()">
            <i class="fas fa-plug"></i> Test API Connection
        </button>
    </div>

    <!-- Webhook Configuration -->
    <div class="settings-section" id="webhook-config">
        <h2><i class="fas fa-webhook"></i> Webhook Configuration</h2>

        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle"></i> Important: Configure Webhooks in Interakt</h4>
            <p>To receive real-time WhatsApp messages, you must configure webhooks in your Interakt dashboard:</p>
            <ol>
                <li>Login to your Interakt dashboard</li>
                <li>Go to <strong>Settings → Webhooks</strong></li>
                <li>Add the following webhook URL: <code><?php echo (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/api/interakt-webhook.php</code></li>
                <li>Select events: <strong>Message Received</strong> and <strong>Message Status</strong></li>
                <li>Save the webhook configuration</li>
            </ol>
        </div>

        <div class="webhook-info">
            <h4>Webhook Details:</h4>
            <table class="table table-bordered">
                <tr>
                    <td><strong>Webhook URL:</strong></td>
                    <td><code><?php echo (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/api/interakt-webhook.php</code></td>
                </tr>
                <tr>
                    <td><strong>Method:</strong></td>
                    <td>POST</td>
                </tr>
                <tr>
                    <td><strong>Content-Type:</strong></td>
                    <td>application/json</td>
                </tr>
                <tr>
                    <td><strong>Events:</strong></td>
                    <td>Message Received, Message Status Updates</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Scroll to Top Functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/Hide Scroll to Top Button
window.addEventListener('scroll', function() {
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.add('show');
    } else {
        scrollToTopBtn.classList.remove('show');
    }
});

// Smooth Scroll for Navigation Links
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.settings-nav .nav-link');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                const offsetTop = targetElement.offsetTop - 20;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });

                // Add active state to clicked link
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    // Highlight current section in navigation
    window.addEventListener('scroll', function() {
        const sections = ['api-config', 'automation', 'business-hours', 'notifications', 'api-test', 'webhook-config'];
        const scrollPos = window.pageYOffset + 100;

        sections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            const navLink = document.querySelector(`a[href="#${sectionId}"]`);

            if (section && navLink) {
                const sectionTop = section.offsetTop;
                const sectionBottom = sectionTop + section.offsetHeight;

                if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                    navLinks.forEach(l => l.classList.remove('active'));
                    navLink.classList.add('active');
                }
            }
        });
    });
});

function testApiConnection() {
    const btn = document.getElementById('test-api-btn');
    const result = document.getElementById('api-test-result');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';

    fetch('api/test-interakt-connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        result.style.display = 'block';
        if (data.success) {
            result.className = 'alert alert-success';
            result.innerHTML = '<i class="fas fa-check-circle"></i> API connection successful!';
        } else {
            result.className = 'alert alert-danger';
            result.innerHTML = '<i class="fas fa-times-circle"></i> API connection failed: ' + data.error;
        }
    })
    .catch(error => {
        result.style.display = 'block';
        result.className = 'alert alert-danger';
        result.innerHTML = '<i class="fas fa-times-circle"></i> Test failed: ' + error.message;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Test API Connection';
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>