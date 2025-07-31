<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: account.php');
    exit();
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'login';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'login') {
        // Step 1: Request OTP
        $identifier = trim($_POST['identifier'] ?? '');

        if (empty($identifier)) {
            $error = 'Please enter your phone number or email';
        } else {
            $result = $auth->loginWithOTP($identifier);
            
            if ($result['success']) {
                $success = $result['message'];
                $step = 'verify';
                $_SESSION['login_identifier'] = $identifier;
                $_SESSION['login_method'] = $result['method'];
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($step === 'verify') {
        // Step 2: OTP Verification
        $identifier = $_SESSION['login_identifier'] ?? '';
        $otpCode = trim($_POST['otp_code'] ?? '');

        if (empty($identifier) || empty($otpCode)) {
            $error = 'Please enter the OTP code';
        } else {
            $result = $auth->completeOTPLogin($identifier, $otpCode);
            
            if ($result['success']) {
                unset($_SESSION['login_identifier']);
                unset($_SESSION['login_method']);
                
                // Redirect to intended page or account
                $redirect = $_SESSION['redirect_after_login'] ?? 'account.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend']) && isset($_SESSION['login_identifier'])) {
    $result = $auth->resendOTP($_SESSION['login_identifier'], 'login');
    if ($result['success']) {
        $success = 'OTP resent successfully';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with OTP - Alpha Nutrition</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .otp-container {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .step.active {
            background: #ff6b35;
            color: white;
        }

        .step.inactive {
            background: #f0f0f0;
            color: #666;
        }

        .otp-input {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }

        .otp-digit {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }

        .otp-digit:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .identifier-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
            font-weight: 600;
        }

        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .login-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .login-option {
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .login-option:hover {
            border-color: #ff6b35;
            background: #fff5f2;
        }

        .login-option.active {
            border-color: #ff6b35;
            background: #ff6b35;
            color: white;
        }

        @media (max-width: 768px) {
            .otp-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .otp-digit {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .login-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="otp-container">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step === 'login' ? 'active' : 'inactive'; ?>">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Enter Details</span>
                </div>
                <div class="step <?php echo $step === 'verify' ? 'active' : 'inactive'; ?>">
                    <i class="fas fa-key"></i>
                    <span>Verify OTP</span>
                </div>
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
                </div>
            <?php endif; ?>

            <?php if ($step === 'login'): ?>
                <!-- Login Form -->
                <div class="auth-header">
                    <h1>Login with OTP</h1>
                    <p>Enter your phone number or email to receive an OTP</p>
                </div>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="identifier">Phone Number or Email</label>
                        <input type="text" id="identifier" name="identifier" 
                               value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" 
                               placeholder="Enter phone number or email"
                               required autocomplete="username">
                        <small>We'll send an OTP to verify your identity</small>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send OTP
                    </button>
                </form>

                <div class="login-options">
                    <a href="login.php" class="login-option">
                        <i class="fas fa-lock"></i>
                        <div>Password Login</div>
                        <small>Use email & password</small>
                    </a>
                    <a href="register-otp.php" class="login-option">
                        <i class="fas fa-user-plus"></i>
                        <div>Register</div>
                        <small>Create new account</small>
                    </a>
                </div>

            <?php elseif ($step === 'verify'): ?>
                <!-- OTP Verification Form -->
                <div class="auth-header">
                    <h1>Verify OTP</h1>
                    <p>Enter the 6-digit OTP sent to you</p>
                </div>

                <div class="identifier-display">
                    <i class="fas fa-<?php echo ($_SESSION['login_method'] ?? 'whatsapp') === 'whatsapp' ? 'mobile-alt' : 'envelope'; ?>"></i>
                    <?php echo htmlspecialchars($_SESSION['login_identifier'] ?? ''); ?>
                    
                    <?php if (isset($_SESSION['login_method'])): ?>
                        <div class="method-badge">
                            <i class="fab fa-<?php echo $_SESSION['login_method'] === 'whatsapp' ? 'whatsapp' : 'envelope'; ?>"></i>
                            <?php echo ucfirst($_SESSION['login_method']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="auth-form" id="otpForm">
                    <div class="form-group">
                        <label>Enter OTP Code</label>
                        <div class="otp-input">
                            <input type="text" class="otp-digit" maxlength="1" data-index="0">
                            <input type="text" class="otp-digit" maxlength="1" data-index="1">
                            <input type="text" class="otp-digit" maxlength="1" data-index="2">
                            <input type="text" class="otp-digit" maxlength="1" data-index="3">
                            <input type="text" class="otp-digit" maxlength="1" data-index="4">
                            <input type="text" class="otp-digit" maxlength="1" data-index="5">
                        </div>
                        <input type="hidden" name="otp_code" id="otpCode">
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Verify & Login
                    </button>
                </form>

                <div class="resend-section">
                    <p>Didn't receive the OTP?</p>
                    <a href="?step=verify&resend=1" class="resend-link" id="resendLink">
                        <i class="fas fa-redo"></i> Resend OTP
                    </a>
                    <div class="countdown" id="countdown" style="display: none;"></div>
                </div>

                <div class="auth-footer">
                    <a href="?step=login" class="back-link">
                        <i class="fas fa-arrow-left"></i> Change Phone/Email
                    </a>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // OTP input handling
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-digit');
            const otpCodeInput = document.getElementById('otpCode');

            if (otpInputs.length > 0) {
                // Focus first input
                otpInputs[0].focus();

                otpInputs.forEach((input, index) => {
                    input.addEventListener('input', function(e) {
                        const value = e.target.value;

                        // Only allow numbers
                        if (!/^\d$/.test(value)) {
                            e.target.value = '';
                            return;
                        }

                        // Move to next input
                        if (value && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }

                        // Update hidden input
                        updateOTPCode();
                    });

                    input.addEventListener('keydown', function(e) {
                        // Handle backspace
                        if (e.key === 'Backspace' && !e.target.value && index > 0) {
                            otpInputs[index - 1].focus();
                        }

                        // Handle paste
                        if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                            e.preventDefault();
                            navigator.clipboard.readText().then(text => {
                                const digits = text.replace(/\D/g, '').slice(0, 6);
                                digits.split('').forEach((digit, i) => {
                                    if (otpInputs[i]) {
                                        otpInputs[i].value = digit;
                                    }
                                });
                                updateOTPCode();
                                if (digits.length === 6) {
                                    otpInputs[5].focus();
                                }
                            });
                        }
                    });
                });

                function updateOTPCode() {
                    const code = Array.from(otpInputs).map(input => input.value).join('');
                    if (otpCodeInput) {
                        otpCodeInput.value = code;
                    }
                }
            }

            // Countdown timer for resend
            let countdown = 30;
            const countdownElement = document.getElementById('countdown');
            const resendLink = document.getElementById('resendLink');

            if (countdownElement && resendLink) {
                function startCountdown() {
                    countdownElement.style.display = 'block';
                    resendLink.style.display = 'none';

                    const timer = setInterval(() => {
                        countdownElement.textContent = `Resend available in ${countdown}s`;
                        countdown--;

                        if (countdown < 0) {
                            clearInterval(timer);
                            countdownElement.style.display = 'none';
                            resendLink.style.display = 'inline-block';
                            countdown = 30;
                        }
                    }, 1000);
                }

                // Start countdown if on verify step
                if (document.querySelector('.step.active i.fa-key')) {
                    startCountdown();
                }

                // Restart countdown on resend click
                resendLink.addEventListener('click', function() {
                    countdown = 30;
                    startCountdown();
                });
            }
        });

        // Smart identifier detection and formatting
        document.addEventListener('DOMContentLoaded', function() {
            const identifierInput = document.getElementById('identifier');
            if (identifierInput) {
                identifierInput.addEventListener('input', function(e) {
                    let value = e.target.value.trim();

                    // If it looks like a phone number, format it
                    if (/^\d/.test(value)) {
                        value = value.replace(/\D/g, '');

                        // Remove country code if present
                        if (value.startsWith('91') && value.length > 10) {
                            value = value.substring(2);
                        }

                        // Limit to 10 digits
                        value = value.substring(0, 10);

                        e.target.value = value;
                        e.target.type = 'tel';
                        e.target.placeholder = 'Enter 10-digit mobile number';
                    } else if (value.includes('@')) {
                        e.target.type = 'email';
                        e.target.placeholder = 'Enter email address';
                    } else {
                        e.target.type = 'text';
                        e.target.placeholder = 'Enter phone number or email';
                    }
                });
            }
        });
    </script>
</body>
</html>
