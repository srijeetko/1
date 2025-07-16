<?php
session_start();
require_once 'includes/auth.php';

echo "<h1>Alpha Nutrition - User Authentication Test</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p>Current users in database: " . $userCount . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check if user is logged in
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ User is logged in!</h3>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
    echo "<p><strong>User ID:</strong> " . $user['user_id'] . "</p>";
    echo "<p><a href='account.php' style='color: blue;'>Go to Account Dashboard</a></p>";
    echo "<p><a href='?logout=1' style='color: red;'>Logout</a></p>";
    echo "</div>";
    
    if (isset($_GET['logout'])) {
        $auth->logout();
        header('Location: test-auth.php');
        exit();
    }
} else {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>ℹ️ User is not logged in</h3>";
    echo "<p><a href='register.php' style='color: blue;'>Register New Account</a></p>";
    echo "<p><a href='login.php' style='color: blue;'>Login to Existing Account</a></p>";
    echo "</div>";
}

// Quick registration form for testing
if (!$auth->isLoggedIn()) {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Quick Test Registration</h3>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_register'])) {
        $testData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $result = $auth->register($testData);
        if ($result['success']) {
            echo "<p style='color: green;'>✅ Test user registered successfully!</p>";
            echo "<p>You can now login with: test@example.com / password123</p>";
        } else {
            echo "<p style='color: red;'>❌ Registration failed: " . htmlspecialchars($result['message']) . "</p>";
        }
    }
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_register' value='1'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Create Test User (test@example.com)</button>";
    echo "</form>";
    echo "</div>";
}

// Quick login form for testing
if (!$auth->isLoggedIn()) {
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Quick Test Login</h3>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
        $result = $auth->login('test@example.com', 'password123', false);
        if ($result['success']) {
            header('Location: test-auth.php');
            exit();
        } else {
            echo "<p style='color: red;'>❌ Login failed: " . htmlspecialchars($result['message']) . "</p>";
        }
    }
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='test_login' value='1'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>Login as Test User</button>";
    echo "</form>";
    echo "</div>";
}

echo "<div style='background: #f1f3f4; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔗 Navigation Links</h3>";
echo "<ul>";
echo "<li><a href='index.php'>Home Page</a></li>";
echo "<li><a href='register.php'>Registration Page</a></li>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='account.php'>Account Dashboard</a></li>";
echo "<li><a href='forgot-password.php'>Forgot Password</a></li>";
echo "<li><a href='admin/user-management.php'>Admin User Management</a></li>";
echo "<li><a href='setup_user_tables.php'>Setup Database Tables</a></li>";
echo "</ul>";
echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ul {
    list-style-type: none;
    padding: 0;
}

li {
    margin: 8px 0;
    padding: 8px;
    background: white;
    border-radius: 4px;
}
</style>
