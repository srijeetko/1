<?php
/**
 * Test Interakt API Connection
 * Verify API key and check available templates
 */

require_once 'includes/db_connection.php';

// Get API key from database
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM support_settings WHERE setting_key = 'interakt_api_key'");
    $stmt->execute();
    $apiKey = $stmt->fetch()['setting_value'] ?? '';
    
    if (empty($apiKey)) {
        die('❌ API key not found in database. Please run update-api-key-web.php first.');
    }
} catch (Exception $e) {
    die('❌ Error getting API key: ' . $e->getMessage());
}

echo "<h2>Interakt API Test</h2>";
echo "<p><strong>API Key:</strong> " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . "</p>";

// Test 1: Basic API connection
echo "<h3>Test 1: API Connection</h3>";
testApiConnection($apiKey);

// Test 2: List available templates
echo "<h3>Test 2: Available Templates</h3>";
listTemplates($apiKey);

// Test 3: Send test OTP
echo "<h3>Test 3: Send Test OTP</h3>";
echo "<p>⚠️ This will send an actual WhatsApp message. Only test with your own number.</p>";
if (isset($_POST['test_phone'])) {
    sendTestOTP($apiKey, $_POST['test_phone']);
} else {
    echo '<form method="POST">
        <input type="tel" name="test_phone" placeholder="Enter your phone number (10 digits)" pattern="[0-9]{10}" required>
        <button type="submit">Send Test OTP</button>
    </form>';
}

function testApiConnection($apiKey) {
    $url = 'https://api.interakt.ai/v1/public/track/users/';
    
    $headers = [
        'Authorization: Basic ' . base64_encode($apiKey),
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $testData = [
        'phoneNumber' => '1234567890',
        'countryCode' => '+91',
        'traits' => ['test' => true]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testData),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error<br>";
        return false;
    }
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";
    
    if ($httpCode === 200 || $httpCode === 201) {
        echo "✅ API connection successful!<br>";
        return true;
    } elseif ($httpCode === 401) {
        echo "❌ Authentication failed. Check your API key.<br>";
        return false;
    } else {
        echo "⚠️ Unexpected response code: $httpCode<br>";
        return false;
    }
}

function listTemplates($apiKey) {
    // Try to get templates (this endpoint might not exist, but let's try)
    $url = 'https://api.interakt.ai/v1/public/templates/';
    
    $headers = [
        'Authorization: Basic ' . base64_encode($apiKey),
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error<br>";
        return;
    }
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    
    if ($httpCode === 200) {
        echo "✅ Templates endpoint accessible<br>";
        echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";
    } elseif ($httpCode === 404) {
        echo "⚠️ Templates endpoint not found. This is normal for some Interakt accounts.<br>";
        echo "<p>You'll need to check your templates in the Interakt dashboard manually.</p>";
    } else {
        echo "⚠️ Templates check returned HTTP $httpCode<br>";
        echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";
    }
    
    echo "<h4>Required Templates for OTP System:</h4>";
    echo "<ul>";
    echo "<li><strong>registration_otp</strong> - For user registration</li>";
    echo "<li><strong>login_otp</strong> - For passwordless login</li>";
    echo "<li><strong>password_reset_otp</strong> - For password reset</li>";
    echo "<li><strong>phone_verification_otp</strong> - For phone verification</li>";
    echo "</ul>";
    echo "<p>⚠️ These templates must be created and approved in your Interakt dashboard.</p>";
}

function sendTestOTP($apiKey, $phoneNumber) {
    // Format phone number
    $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
    if (strlen($phoneNumber) === 10) {
        $phoneNumber = '91' . $phoneNumber; // Add India country code
    }
    
    $url = 'https://api.interakt.ai/v1/public/message/';
    
    $headers = [
        'Authorization: Basic ' . base64_encode($apiKey),
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    // Try with a simple template first
    $testData = [
        'countryCode' => '+91',
        'phoneNumber' => $phoneNumber,
        'type' => 'Template',
        'template' => [
            'name' => 'registration_otp', // This template must exist
            'languageCode' => 'en',
            'bodyValues' => ['123456'] // Test OTP
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testData),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error<br>";
        return;
    }
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre></p>";
    
    if ($httpCode === 200 || $httpCode === 201) {
        echo "✅ Test OTP sent successfully!<br>";
    } elseif ($httpCode === 400) {
        echo "❌ Bad request. Likely template not found or incorrect format.<br>";
    } elseif ($httpCode === 401) {
        echo "❌ Authentication failed.<br>";
    } else {
        echo "⚠️ Unexpected response code: $httpCode<br>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
pre { background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; }
form { margin: 1rem 0; }
input, button { padding: 0.5rem; margin: 0.25rem; }
button { background: #ff6b35; color: white; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #e55a2b; }
ul { background: #f0f8ff; padding: 1rem; border-radius: 4px; }
</style>
