<?php
require_once 'includes/auth.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

// Handle error messages from order processing
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
}

// Check if user is logged in
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: checkout.php');
    exit();
}

// Get user addresses if logged in
$userAddresses = [];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_addresses
            WHERE user_id = ?
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$currentUser['user_id']]);
        $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $userAddresses = [];
    }
}

// Get cart items with product details
$cartProducts = [];
$totalAmount = 0;

foreach ($_SESSION['cart'] as $cartKey => $cartData) {
    $productId = $cartData['product_id'];
    $variantId = $cartData['variant_id'] ?? null;
    $quantity = $cartData['quantity'];
    
    // Get product details
    $stmt = $pdo->prepare("SELECT p.*,
        (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p WHERE p.product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $price = $product['price'];
        $variantName = '';
        
        // Get variant details if exists
        if ($variantId) {
            $variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE variant_id = ? AND product_id = ?");
            $variantStmt->execute([$variantId, $productId]);
            $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($variant) {
                $price = $product['price'] + ($variant['price_modifier'] ?? 0);
                $variantName = $variant['size'] ?? $variant['color'] ?? 'Variant';
            }
        }
        
        $itemTotal = $price * $quantity;
        $totalAmount += $itemTotal;
        
        $cartProducts[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $itemTotal,
            'image_url' => $product['image_url'],
            'variant_name' => $variantName
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Alpha Nutrition</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Step Navigation and Form Handling Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Step navigation functions
            window.proceedToStep2 = function() {
                console.log('proceedToStep2 called');
                showStep(2);
            }

            window.proceedToStep3 = function() {
                console.log('proceedToStep3 called');
                // Validate form before proceeding
                const form = document.getElementById('checkoutForm');
                if (form.checkValidity()) {
                    showStep(3);
                } else {
                    form.reportValidity();
                }
            }

            window.backToStep1 = function() {
                console.log('backToStep1 called');
                showStep(1);
            }

            window.backToStep2 = function() {
                console.log('backToStep2 called');
                showStep(2);
            }

            function showStep(stepNumber) {
                console.log('showStep called with step:', stepNumber);
                // Hide all step contents
                document.querySelectorAll('.checkout-step-content').forEach(step => {
                    step.classList.remove('active');
                    console.log('Removed active class from:', step.id);
                });

                // Show current step content
                const targetStep = document.getElementById('step' + stepNumber);
                console.log('Target step element:', targetStep);
                if (targetStep) {
                    targetStep.classList.add('active');
                    console.log('Added active class to step:', stepNumber);
                } else {
                    console.error('Could not find step element:', 'step' + stepNumber);
                }

                // Update step indicators
                document.querySelectorAll('.step').forEach((step, index) => {
                    if (index + 1 <= stepNumber) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                });

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Address management functions
            const savedAddresses = <?php echo json_encode($userAddresses); ?>;

            window.fillAddressForm = function(addressId) {
                if (addressId === 'new') {
                    clearAddressForm();
                    return;
                }

                const address = savedAddresses.find(addr => addr.address_id === addressId);
                if (address) {
                    document.getElementById('firstName').value = address.first_name;
                    document.getElementById('lastName').value = address.last_name;
                    document.getElementById('phone').value = address.phone || '';
                    document.getElementById('address').value = address.address_line_1;
                    document.getElementById('city').value = address.city;
                    document.getElementById('state').value = address.state;
                    document.getElementById('pincode').value = address.postal_code;
                }
            }

            window.clearAddressForm = function() {
                <?php if (!$isLoggedIn): ?>
                document.getElementById('firstName').value = '';
                document.getElementById('lastName').value = '';
                document.getElementById('phone').value = '';
                <?php endif; ?>
                document.getElementById('address').value = '';
                document.getElementById('city').value = '';
                document.getElementById('state').value = '';
                document.getElementById('pincode').value = '';
            }

            // Initialize with first saved address if available
            <?php if ($isLoggedIn && !empty($userAddresses)): ?>
            fillAddressForm('<?php echo $userAddresses[0]['address_id']; ?>');
            <?php endif; ?>

            // Form validation
            const checkoutForm = document.getElementById('checkoutForm');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Always prevent default submit first
                    
                    // Check selected payment method
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    
                    // Validate form fields
                    const requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'state', 'pincode'];
                    let isValid = true;

                    requiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (!field.value.trim()) {
                            field.style.borderColor = '#dc3545';
                            isValid = false;
                        } else {
                            field.style.borderColor = '#e9ecef';
                        }
                    });

                    if (!isValid) {
                        alert('Please fill in all required fields');
                        return false;
                    }

                    // Email validation
                    const email = document.getElementById('email').value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        alert('Please enter a valid email address');
                        return false;
                    }

                    // Phone validation
                    const phone = document.getElementById('phone').value;
                    const cleanPhone = phone.replace(/[^0-9]/g, '');
                    if (cleanPhone.length < 10 || cleanPhone.length > 12) {
                        alert('Please enter a valid phone number (10-12 digits)');
                        document.getElementById('phone').style.borderColor = '#dc3545';
                        return false;
                    }

                    // Pincode validation
                    const pincode = document.getElementById('pincode').value;
                    const cleanPincode = pincode.replace(/[^0-9]/g, '');
                    if (cleanPincode.length !== 6) {
                        alert('Please enter a valid 6-digit pincode (numbers only)');
                        document.getElementById('pincode').style.borderColor = '#dc3545';
                        return false;
                    }

                    // If all validations pass, handle based on payment method
                    if (paymentMethod === 'cashfree') {
                        // For Cashfree payment, first create order
                        const formData = new FormData(checkoutForm);
                        formData.append('payment_method', 'cashfree');
                        
                        // Convert FormData to URL-encoded string
                        const searchParams = new URLSearchParams();
                        for (const [key, value] of formData) {
                            searchParams.append(key, value);
                        }

                        fetch('process-order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: searchParams
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.orderData) {
                                // Store transaction ID for later verification
                                localStorage.setItem('transactionId', data.orderData.transaction_id);
                                // Initialize Cashfree payment
                                initCashfree(data.orderData);
                            } else {
                                alert(data.message || 'Failed to create order. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to create order. Please try again.');
                        });
                    } else {
                        // For COD, submit form normally
                        checkoutForm.submit();
                    }
                });
            }
        });
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        <div class="checkout-content">
            <!-- Error Message Display -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Checkout Header -->
            <div class="checkout-header">
                <h1>Secure Checkout</h1>
                <div class="checkout-steps">
                    <div class="step active">
                        <span class="step-number">1</span>
                        <span class="step-text">Checkout Options</span>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <span class="step-text">Billing Details</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span class="step-text">Payment</span>
                    </div>
                </div>
            </div>

            <div class="checkout-layout">
                <!-- Left Side - Checkout Options & Form -->
                <div class="checkout-main">
                    <!-- Step 1: Checkout Options -->
                    <div class="checkout-step-content active" id="step1">
                        <div class="checkout-options">
                            <?php if ($isLoggedIn): ?>
                                <h2>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h2>
                                <p class="user-greeting">You're signed in and ready to checkout</p>
                            <?php else: ?>
                                <h2>How would you like to checkout?</h2>
                            <?php endif; ?>

                            <!-- Express Checkout Options -->
                            <div class="express-checkout">
                                <button class="express-btn amazon-pay" onclick="alert('Amazon Pay integration coming soon!')">
                                    <i class="fab fa-amazon"></i>
                                    <span>Amazon Pay</span>
                                </button>
                                <button class="express-btn google-pay" onclick="alert('Google Pay integration coming soon!')">
                                    <i class="fab fa-google-pay"></i>
                                    <span>Google Pay</span>
                                </button>
                                <button class="express-btn paypal" onclick="alert('PayPal integration coming soon!')">
                                    <i class="fab fa-paypal"></i>
                                    <span>PayPal</span>
                                </button>
                            </div>

                            <div class="divider">
                                <span>OR</span>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <!-- Logged in user - Continue to billing -->
                                <div class="user-checkout">
                                    <button class="user-btn" onclick="proceedToStep2()">
                                        <i class="fas fa-user-check"></i>
                                        <span>Continue with Your Account</span>
                                    </button>
                                    <p class="user-text">Use your saved addresses and preferences</p>
                                </div>

                                <div class="divider">
                                    <span>OR</span>
                                </div>

                                <!-- Logout option -->
                                <div class="logout-option">
                                    <a href="?logout=1" class="logout-btn">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Sign Out & Continue as Guest</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Guest Checkout -->
                                <div class="guest-checkout">
                                    <button class="guest-btn" onclick="proceedToStep2()">
                                        <i class="fas fa-user"></i>
                                        <span>Continue as Guest</span>
                                    </button>
                                    <p class="guest-text">No account required. Quick and easy checkout.</p>
                                </div>

                                <div class="divider">
                                    <span>OR</span>
                                </div>

                                <!-- Login Option -->
                                <div class="login-option">
                                    <a href="login.php?redirect=checkout.php" class="login-btn">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Sign In to Your Account</span>
                                    </a>
                                    <p class="login-text">Save time with saved addresses and payment methods</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Step 2: Billing Details -->
                    <div class="checkout-step-content" id="step2">
                        <form id="checkoutForm" method="POST" action="process-order.php">
                            <h2>Billing Information</h2>

                            <?php if ($isLoggedIn): ?>
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($currentUser['user_id']); ?>">
                            <?php endif; ?>

                            <?php if ($isLoggedIn && !empty($userAddresses)): ?>
                                <!-- Saved Addresses -->
                                <div class="saved-addresses">
                                    <h3>Choose from saved addresses</h3>
                                    <div class="address-options">
                                        <?php foreach ($userAddresses as $index => $address): ?>
                                            <label class="address-option">
                                                <input type="radio" name="saved_address" value="<?php echo $address['address_id']; ?>"
                                                       <?php echo $index === 0 ? 'checked' : ''; ?>
                                                       onchange="fillAddressForm(this.value)">
                                                <div class="address-card">
                                                    <h4><?php echo ucfirst($address['address_type']); ?> Address</h4>
                                                    <p><strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong></p>
                                                    <p><?php echo htmlspecialchars($address['address_line_1']); ?></p>
                                                    <?php if ($address['address_line_2']): ?>
                                                        <p><?php echo htmlspecialchars($address['address_line_2']); ?></p>
                                                    <?php endif; ?>
                                                    <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?></p>
                                                    <?php if ($address['is_default']): ?>
                                                        <span class="default-badge">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>

                                        <label class="address-option">
                                            <input type="radio" name="saved_address" value="new" onchange="clearAddressForm()">
                                            <div class="address-card new-address">
                                                <i class="fas fa-plus"></i>
                                                <h4>Use New Address</h4>
                                                <p>Enter a different address</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="divider">
                                    <span>OR EDIT DETAILS BELOW</span>
                                </div>
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="first_name"
                                           value="<?php echo $isLoggedIn ? htmlspecialchars($currentUser['first_name']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name *</label>
                                    <input type="text" id="lastName" name="last_name"
                                           value="<?php echo $isLoggedIn ? htmlspecialchars($currentUser['last_name']) : ''; ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo $isLoggedIn ? htmlspecialchars($currentUser['email']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo $isLoggedIn ? htmlspecialchars($currentUser['phone'] ?? '') : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <input type="text" id="state" name="state" required>
                                </div>
                                <div class="form-group">
                                    <label for="pincode">PIN Code *</label>
                                    <input type="text" id="pincode" name="pincode" required>
                                </div>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="save_address" value="1" checked>
                                        <span class="checkmark"></span>
                                        Save this address to my account
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <button type="button" class="btn-back" onclick="backToStep1()">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn-continue" onclick="proceedToStep3()">
                                    Continue to Payment <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 3: Payment -->
                    <div class="checkout-step-content" id="step3">
                        <h2>Payment Method</h2>

                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="payment-method-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>
                                        <strong>Cash on Delivery</strong>
                                        <p>Pay when your order arrives</p>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="cashfree">
                                <div class="payment-method-content">
                                    <i class="fas fa-credit-card"></i>
                                    <div>
                                        <strong>Pay Online (Cashfree)</strong>
                                        <p>Credit/Debit Card, UPI, Net Banking</p>
                                        <div class="payment-icons">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                            <i class="fab fa-cc-amex"></i>
                                            <img src="assets/images/upi-icon.png" alt="UPI" class="upi-icon">
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-back" onclick="backToStep2()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="submit" class="btn-place-order" form="checkoutForm">
                                <i class="fas fa-lock"></i>
                                Place Order - ₹<?php echo number_format($totalAmount, 0); ?>
                            </button>
                        </div>

                        <!-- Cashfree Integration Script - Updated to v3 -->
                        <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
                        <script>
                        function initCashfree(orderData) {
                            const cashfree = Cashfree({
                                mode: "<?php echo CASHFREE_API_ENV === 'TEST' ? 'sandbox' : 'production'; ?>"
                            });

                            const checkoutOptions = {
                                paymentSessionId: orderData.payment_session_id,
                                redirectTarget: "_self"
                            };

                            // For redirect checkout, no need to handle promise
                            cashfree.checkout(checkoutOptions);
                        }

                        // Note: With redirect checkout, payment verification happens on return URL
                        // No need for success/failure handlers as user is redirected to return_url
                        </script>
                    </div>
                </div>

                <!-- Right Side - Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <div class="order-items">
                        <?php foreach ($cartProducts as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <?php
                                    $imgUrl = $item['image_url'] ?? '';
                                    if (!empty($imgUrl)) {
                                        $imgFile = basename($imgUrl);
                                        $imgUrl = 'assets/' . $imgFile;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='assets/placeholder.jpg'">
                                    <span class="item-qty"><?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <?php if (!empty($item['variant_name'])): ?>
                                        <p class="item-variant"><?php echo htmlspecialchars($item['variant_name']); ?></p>
                                    <?php endif; ?>
                                    <p class="item-price">₹<?php echo number_format($item['total'], 0); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-totals">
                        <div class="total-line">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($totalAmount, 0); ?></span>
                        </div>
                        <div class="total-line">
                            <span>Shipping</span>
                            <span class="free-shipping">FREE</span>
                        </div>
                        <div class="total-line final-total">
                            <span>Total</span>
                            <span>₹<?php echo number_format($totalAmount, 0); ?></span>
                        </div>
                    </div>

                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-lock"></i>
                            <span>Secure Checkout</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>SSL Protected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
