<?php
// Cashfree Setup Helper
require_once 'includes/cashfree-config.php';

echo "<h2>Cashfree Payment Gateway Setup</h2>";

echo "<h3>Current Configuration:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Environment:</strong> " . CASHFREE_API_ENV . "<br>";
echo "<strong>App ID:</strong> " . CASHFREE_APP_ID . "<br>";
echo "<strong>API Base URL:</strong> " . CASHFREE_API_BASE_URL . "<br>";
echo "<strong>Base URL:</strong> " . CASHFREE_BASE_URL . "<br>";
echo "<strong>Return URL:</strong> " . CASHFREE_BASE_URL . "/payment-return.php<br>";
echo "<strong>Webhook URL:</strong> " . CASHFREE_BASE_URL . "/payment-webhook.php<br>";
echo "</div>";

$isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

if ($isLocalhost) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🚨 Local Development Detected</h4>";
    echo "<p>You're running on localhost. Here are your options:</p>";
    
    if (CASHFREE_API_ENV === 'TEST') {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px; margin: 5px 0;'>";
        echo "✅ <strong>Currently using TEST environment</strong> - HTTP URLs are allowed<br>";
        echo "You can test payments with test credentials without HTTPS.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 3px; margin: 5px 0;'>";
        echo "❌ <strong>Using PROD environment</strong> - HTTPS URLs required<br>";
        echo "You need to set up ngrok or use test environment for local development.";
        echo "</div>";
    }
    
    echo "<h5>Setup Options:</h5>";
    echo "<ol>";
    echo "<li><strong>Use Test Environment (Recommended for development):</strong><br>";
    echo "   - Already configured automatically for localhost<br>";
    echo "   - Uses test credentials and allows HTTP URLs<br>";
    echo "   - No real money transactions</li>";
    echo "<li><strong>Use ngrok for Production Testing:</strong><br>";
    echo "   - Install ngrok: <a href='https://ngrok.com/' target='_blank'>https://ngrok.com/</a><br>";
    echo "   - Run: <code>ngrok http 80</code><br>";
    echo "   - Update CASHFREE_BASE_URL in config with ngrok HTTPS URL</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ Production Environment</h4>";
    echo "<p>Make sure to update CASHFREE_BASE_URL with your actual domain in the config file.</p>";
    echo "</div>";
}

echo "<h3>Test Payment Integration:</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p>To test the payment integration:</p>";
echo "<ol>";
echo "<li>Add some products to your cart</li>";
echo "<li>Go to checkout page</li>";
echo "<li>Fill in the form and select 'Online Payment'</li>";
echo "<li>Click 'Place Order'</li>";
echo "</ol>";

if (CASHFREE_API_ENV === 'TEST') {
    echo "<h4>Test Card Details:</h4>";
    echo "<ul>";
    echo "<li><strong>Card Number:</strong> 4111 1111 1111 1111</li>";
    echo "<li><strong>Expiry:</strong> Any future date</li>";
    echo "<li><strong>CVV:</strong> 123</li>";
    echo "<li><strong>Name:</strong> Any name</li>";
    echo "</ul>";
}
echo "</div>";

echo "<h3>Troubleshooting:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<ul>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Check server error logs for PHP errors</li>";
echo "<li>Verify database tables exist (checkout_orders, payment_transactions)</li>";
echo "<li>Ensure all required fields are filled in checkout form</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='checkout.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Checkout</a></p>";
echo "<p><a href='debug-cashfree.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test API Connection</a></p>";
?>
