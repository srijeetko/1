<?php
// Cashfree Production API Setup Guide for Development
require_once 'includes/cashfree-config.php';

echo "<h1>🚀 Cashfree Production API Setup Guide</h1>";
echo "<p><strong>Status:</strong> Your Cashfree integration is now configured to use the PRODUCTION API even during development.</p>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #28a745;'>";
echo "<h3>✅ What's Already Configured:</h3>";
echo "<ul>";
echo "<li><strong>Production App ID:</strong> 667364de1dbc524e0b260a7c3c463766</li>";
echo "<li><strong>Production Secret Key:</strong> cfsk_ma_prod_6331e813da65e12110f3edc596329a3d_2879154f</li>";
echo "<li><strong>API Environment:</strong> PRODUCTION (real payments)</li>";
echo "<li><strong>API Base URL:</strong> " . CASHFREE_API_BASE_URL . "</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
echo "<h3>⚠️ Important: HTTPS Required for Production API</h3>";
echo "<p>Since you're using the production Cashfree API, <strong>HTTPS is mandatory</strong> for webhooks and return URLs, even during development.</p>";
echo "</div>";

echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #007bff;'>";
echo "<h3>🔧 Setup Steps for Development:</h3>";

echo "<h4>Option 1: Using ngrok (Recommended)</h4>";
echo "<ol>";
echo "<li><strong>Install ngrok:</strong> Download from <a href='https://ngrok.com/download' target='_blank'>https://ngrok.com/download</a></li>";
echo "<li><strong>Start your local server:</strong> Make sure your website is running on localhost</li>";
echo "<li><strong>Run ngrok:</strong> Open terminal and run: <code>ngrok http 80</code> (or your port number)</li>";
echo "<li><strong>Copy HTTPS URL:</strong> ngrok will give you a URL like <code>https://abc123.ngrok.io</code></li>";
echo "<li><strong>Update config:</strong> Edit <code>includes/cashfree-config.php</code> line 51 and replace <code>https://your-ngrok-url.ngrok.io</code> with your actual ngrok URL</li>";
echo "</ol>";

echo "<h4>Option 2: Use HTTP (Not Recommended)</h4>";
echo "<p>You can uncomment line 54 in <code>includes/cashfree-config.php</code> to use HTTP, but this may cause issues with production API webhooks.</p>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
echo "<h3>🚨 IMPORTANT WARNINGS:</h3>";
echo "<ul>";
echo "<li><strong>Real Money:</strong> You're using production API - any successful payments will be REAL transactions</li>";
echo "<li><strong>Test Carefully:</strong> Use small amounts for testing (₹1-10)</li>";
echo "<li><strong>Refunds:</strong> You'll need to process refunds through Cashfree dashboard for real payments</li>";
echo "<li><strong>Webhooks:</strong> Make sure your ngrok tunnel is running when testing payments</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🧪 Testing Your Setup:</h3>";
echo "<ol>";
echo "<li>Set up ngrok and update the base URL</li>";
echo "<li>Visit: <a href='verify-cashfree-production.php'>verify-cashfree-production.php</a> to check configuration</li>";
echo "<li>Add products to cart and go to checkout</li>";
echo "<li>Use a real payment method (small amount recommended)</li>";
echo "<li>Check if payment completes successfully</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📋 Current Configuration:</h3>";
echo "<strong>Environment:</strong> " . CASHFREE_API_ENV . "<br>";
echo "<strong>Base URL:</strong> " . CASHFREE_BASE_URL . "<br>";
echo "<strong>Return URL:</strong> " . CASHFREE_BASE_URL . "/payment-return.php<br>";
echo "<strong>Webhook URL:</strong> " . CASHFREE_BASE_URL . "/payment-webhook.php<br>";
echo "</div>";

echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔄 To Switch Back to Test Mode:</h3>";
echo "<p>If you want to switch back to sandbox/test mode later:</p>";
echo "<ol>";
echo "<li>Edit <code>includes/cashfree-config.php</code></li>";
echo "<li>Comment out lines 7-9 (production credentials)</li>";
echo "<li>Uncomment the test section (lines 11-28)</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✅ Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Set up ngrok</strong> and update the base URL in config</li>";
echo "<li><strong>Test with small amount</strong> (₹1-10) to verify everything works</li>";
echo "<li><strong>Configure Cashfree dashboard</strong> with your webhook URLs</li>";
echo "<li><strong>Monitor transactions</strong> in Cashfree merchant dashboard</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
