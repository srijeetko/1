<?php
session_start();
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $result = $auth->requestPasswordReset($email);
        
        if ($result['success']) {
            $success = 'Password reset link has been sent to your email address.';
            // In a real application, you would send an email here
            // For demo purposes, we'll just show the token
            $resetToken = $result['reset_token'];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Alpha Nutrition</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Forgot Password?</h1>
                <p>Enter your email address and we'll send you a link to reset your password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <?php if (isset($resetToken)): ?>
                        <br><br>
                        <strong>Demo Reset Link:</strong><br>
                        <a href="reset-password.php?token=<?php echo urlencode($resetToken); ?>" 
                           style="color: #333; text-decoration: underline;">
                            Click here to reset your password
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required autocomplete="email">
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Link
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Auto-focus email field
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.focus();
        }
    });
    </script>
</body>
</html>
