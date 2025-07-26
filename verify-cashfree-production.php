<?php
// Verify Cashfree Production Configuration
require_once 'includes/cashfree-config.php';

echo "<h2>🔍 Cashfree Production Configuration Verification</h2>";

// Check current environment
$currentHost = $_SERVER['HTTP_HOST'] ?? 'unknown';
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$protocol = $isSecure ? 'https' : 'http';

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Current Environment:</h3>";
echo "<strong>Host:</strong> {$currentHost}<br>";
echo "<strong>Protocol:</strong> {$protocol}<br>";
echo "<strong>Is Localhost:</strong> " . (strpos($currentHost, 'localhost') !== false ? 'Yes' : 'No') . "<br>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Cashfree Configuration:</h3>";
echo "<strong>Environment:</strong> " . CASHFREE_API_ENV . "<br>";
echo "<strong>App ID:</strong> " . CASHFREE_APP_ID . "<br>";
echo "<strong>Secret Key:</strong> " . substr(CASHFREE_SECRET_KEY, 0, 15) . "...<br>";
echo "<strong>API Base URL:</strong> " . CASHFREE_API_BASE_URL . "<br>";
echo "<strong>Base URL:</strong> " . CASHFREE_BASE_URL . "<br>";
echo "</div>";

// Status checks
$checks = [];

// Check 1: Production credentials
if (CASHFREE_APP_ID === '667364de1dbc524e0b260a7c3c463766') {
    $checks[] = ['✅', 'Production App ID is correctly configured'];
} else {
    $checks[] = ['❌', 'Production App ID is not set correctly'];
}

// Check 2: Production secret key
if (strpos(CASHFREE_SECRET_KEY, 'cfsk_ma_prod_') === 0) {
    $checks[] = ['✅', 'Production Secret Key is correctly configured'];
} else {
    $checks[] = ['❌', 'Production Secret Key is not set correctly'];
}

// Check 3: Environment
if (CASHFREE_API_ENV === 'PROD') {
    $checks[] = ['✅', 'Environment is set to PRODUCTION'];
} else {
    $checks[] = ['⚠️', 'Environment is set to TEST (this is normal for localhost)'];
}

// Check 4: HTTPS requirement
if (CASHFREE_API_ENV === 'PROD' && !$isSecure && strpos($currentHost, 'localhost') === false) {
    $checks[] = ['❌', 'HTTPS is required for production mode'];
} else if (CASHFREE_API_ENV === 'PROD' && $isSecure) {
    $checks[] = ['✅', 'HTTPS is enabled (required for production)'];
} else {
    $checks[] = ['ℹ️', 'HTTPS check skipped (localhost or test mode)'];
}

// Check 5: Base URL configuration
if (strpos(CASHFREE_BASE_URL, 'your-production-domain.com') !== false) {
    $checks[] = ['⚠️', 'Base URL needs to be updated with your actual domain'];
} else {
    $checks[] = ['✅', 'Base URL is configured'];
}

echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Configuration Checks:</h3>";
foreach ($checks as $check) {
    echo "<div style='margin: 10px 0;'>{$check[0]} {$check[1]}</div>";
}
echo "</div>";

// Next steps
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📋 Next Steps to Complete Production Setup:</h3>";
echo "<ol>";
echo "<li><strong>Update Base URL:</strong> Edit <code>includes/cashfree-config.php</code> line 43 and replace <code>https://your-production-domain.com</code> with your actual domain</li>";
echo "<li><strong>Deploy to Production:</strong> Upload your files to your live server with HTTPS enabled</li>";
echo "<li><strong>Test Payment:</strong> Make a small test payment to verify everything works</li>";
echo "<li><strong>Configure Webhooks:</strong> In your Cashfree dashboard, set webhook URL to: <code>https://yourdomain.com/payment-webhook.php</code></li>";
echo "</ol>";
echo "</div>";

// Webhook URLs
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔗 Webhook URLs for Cashfree Dashboard:</h3>";
echo "<p>Configure these URLs in your Cashfree merchant dashboard:</p>";
echo "<strong>Return URL:</strong> " . CASHFREE_BASE_URL . "/payment-return.php<br>";
echo "<strong>Webhook URL:</strong> " . CASHFREE_BASE_URL . "/payment-webhook.php<br>";
echo "</div>";

?>
