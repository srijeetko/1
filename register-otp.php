<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: account.php');
    exit();
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'register';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'register') {
        // Step 1: Registration with OTP
        $userData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        // Validation
        if (empty($userData['first_name']) || empty($userData['last_name']) || 
            empty($userData['phone']) || empty($userData['password'])) {
            $error = 'Please fill in all required fields';
        } elseif ($userData['password'] !== $userData['confirm_password']) {
            $error = 'Passwords do not match';
        } elseif (strlen($userData['password']) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            $result = $auth->registerWithOTP($userData);
            
            if ($result['success']) {
                $success = $result['message'];
                $step = 'verify';
                $_SESSION['registration_phone'] = $userData['phone'];
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($step === 'verify') {
        // Step 2: OTP Verification
        $phone = $_SESSION['registration_phone'] ?? '';
        $otpCode = trim($_POST['otp_code'] ?? '');

        if (empty($phone) || empty($otpCode)) {
            $error = 'Please enter the OTP code';
        } else {
            $result = $auth->completeRegistration($phone, $otpCode);
            
            if ($result['success']) {
                unset($_SESSION['registration_phone']);
                header('Location: account.php?registered=1');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend']) && isset($_SESSION['registration_phone'])) {
    $result = $auth->resendOTP($_SESSION['registration_phone'], 'registration');
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
    <title>Register with OTP - Alpha Nutrition</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .otp-container {
            max-width: 500px;
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

        .resend-section {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f0f0f0;
        }

        .countdown {
            font-weight: 600;
            color: #ff6b35;
        }

        .phone-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
            font-weight: 600;
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
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="otp-container">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step === 'register' ? 'active' : 'inactive'; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </div>
                <div class="step <?php echo $step === 'verify' ? 'active' : 'inactive'; ?>">
                    <i class="fas fa-mobile-alt"></i>
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

            <?php if ($step === 'register'): ?>
                <!-- Registration Form -->
                <div class="auth-header">
                    <h1>Create Your Account</h1>
                    <p>Join Alpha Nutrition for premium supplements and nutrition</p>
                </div>

                <form method="POST" class="auth-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                   required autocomplete="given-name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                   required autocomplete="family-name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                               placeholder="Enter your 10-digit mobile number"
                               required autocomplete="tel">
                        <small>We'll send an OTP to verify your phone number</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address (Optional)</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" 
                                   required autocomplete="new-password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   required autocomplete="new-password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <i class="fas fa-mobile-alt"></i>
                        Send OTP & Register
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login-otp.php">Login with OTP</a></p>
                    <p>Prefer traditional login? <a href="login.php">Login with Password</a></p>
                </div>

            <?php elseif ($step === 'verify'): ?>
                <!-- OTP Verification Form -->
                <div class="auth-header">
                    <h1>Verify Your Phone</h1>
                    <p>Enter the 6-digit OTP sent to your phone</p>
                </div>

                <div class="phone-display">
                    <i class="fas fa-mobile-alt"></i>
                    <?php echo htmlspecialchars($_SESSION['registration_phone'] ?? ''); ?>
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
                        <i class="fas fa-check"></i>
                        Verify & Complete Registration
                    </button>
                </form>

                <div class="resend-section">
                    <p>Didn't receive the OTP?</p>
                    <a href="?step=verify&resend=1" class="resend-link" id="resendLink">
                        <i class="fas fa-redo"></i> Resend OTP
                    </a>
                    <div class="countdown" id="countdown" style="display: none;"></div>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Password toggle functionality
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }

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
                if (document.querySelector('.step.active i.fa-mobile-alt')) {
                    startCountdown();
                }

                // Restart countdown on resend click
                resendLink.addEventListener('click', function() {
                    countdown = 30;
                    startCountdown();
                });
            }
        });

        // Phone number formatting
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');

                    // Remove country code if present
                    if (value.startsWith('91') && value.length > 10) {
                        value = value.substring(2);
                    }

                    // Limit to 10 digits
                    value = value.substring(0, 10);

                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html>
