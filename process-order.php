<?php
session_start();
require_once 'includes/db_connection.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit();
}

// Check if cart exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php?error=empty_cart');
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get form data
    $user_id = $_POST['user_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $save_address = isset($_POST['save_address']) ? 1 : 0;

    // Validate required fields
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'address' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'pincode' => 'Pincode'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing_fields[] = $label;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception("Please fill in the following required fields: " . implode(', ', $missing_fields));
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }
    
    // Validate phone (allow 10-12 digits, remove any non-numeric characters for validation)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 12) {
        throw new Exception("Please enter a valid phone number (10-12 digits).");
    }

    // Validate pincode (allow 6 digits, remove any non-numeric characters for validation)
    $cleanPincode = preg_replace('/[^0-9]/', '', $pincode);
    if (strlen($cleanPincode) !== 6) {
        throw new Exception("Please enter a valid 6-digit pincode (numbers only).");
    }
    
    // Get cart items and calculate total
    $cartItems = $_SESSION['cart'];

    if (empty($cartItems)) {
        throw new Exception("Your cart is empty.");
    }

    // Extract actual product IDs from cart keys (format: productId_variantId)
    $productIds = [];
    foreach ($cartItems as $cartKey => $item) {
        $productId = $item['product_id'];
        if (!in_array($productId, $productIds)) {
            $productIds[] = $productId;
        }
    }
    
    // Get product details from database with primary image
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $sql = "
        SELECT p.product_id, p.name, p.price,
               COALESCE(pi.image_url, '') as image_url
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.product_id IN ($placeholders)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        throw new Exception("No valid products found in cart.");
    }
    
    // Calculate order total
    $totalAmount = 0;
    $orderItems = [];

    // Create a lookup array for products
    $productLookup = [];
    foreach ($products as $product) {
        $productLookup[$product['product_id']] = $product;
    }

    // Process each cart item
    foreach ($cartItems as $cartKey => $cartItem) {
        $product_id = $cartItem['product_id'];
        $variant_id = $cartItem['variant_id'];
        $quantity = $cartItem['quantity'];

        if (isset($productLookup[$product_id])) {
            $product = $productLookup[$product_id];
            $price = floatval($product['price']); // Ensure price is a number

            // Handle variant pricing if needed
            if ($variant_id && $variant_id !== 'default') {
                // Get variant price if exists
                try {
                    $stmt = $pdo->prepare("SELECT price FROM product_variants WHERE variant_id = ?");
                    $stmt->execute([$variant_id]);
                    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($variant && isset($variant['price'])) {
                        $price = floatval($variant['price']);
                    }
                } catch (Exception $e) {
                    // Use base price if variant not found
                    error_log("Variant price lookup failed: " . $e->getMessage());
                }
            }

            // Ensure we have valid values
            $quantity = intval($quantity);
            $price = floatval($price);

            if ($price <= 0) {
                error_log("Invalid price for product $product_id: $price");
                throw new Exception("Invalid product price for " . $product['name']);
            }

            if ($quantity <= 0) {
                error_log("Invalid quantity for product $product_id: $quantity");
                throw new Exception("Invalid quantity for " . $product['name']);
            }

            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;

            $orderItems[] = [
                'product_id' => $product_id,
                'product_name' => $product['name'],
                'variant_id' => $variant_id,
                'price' => $price,
                'quantity' => $quantity,
                'total' => $itemTotal
            ];
        } else {
            error_log("Product not found in database: $product_id");
        }
    }

    // Check if we have any valid order items
    if (empty($orderItems)) {
        throw new Exception("No valid products found in cart. Please add products and try again.");
    }

    if ($totalAmount <= 0) {
        // Debug information for troubleshooting
        $debug_info = "Order total calculation failed. ";
        $debug_info .= "Cart items: " . count($cartItems) . ", ";
        $debug_info .= "Products found: " . count($products) . ", ";
        $debug_info .= "Order items: " . count($orderItems) . ", ";
        $debug_info .= "Total amount: " . $totalAmount;

        error_log("Order total debug: " . $debug_info);
        error_log("Cart items: " . print_r($cartItems, true));
        error_log("Products found: " . print_r($products, true));
        error_log("Order items: " . print_r($orderItems, true));

        throw new Exception("Invalid order total. Please check your cart and try again. (Debug: $debug_info)");
    }
    
    // Generate order ID and order number
    $order_id = bin2hex(random_bytes(16));
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($order_id, 0, 6));
    
    // Insert order into checkout_orders table
    $stmt = $pdo->prepare("
        INSERT INTO checkout_orders (
            order_id, order_number, user_id, first_name, last_name, email, phone, 
            address, city, state, pincode, total_amount, payment_method, 
            order_status, payment_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    
    $stmt->execute([
        $order_id, $order_number, $user_id, $firstName, $lastName, $email, $phone,
        $address, $city, $state, $pincode, $totalAmount, $paymentMethod
    ]);
    
    // Insert order items
    foreach ($orderItems as $item) {
        $item_id = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_item_id, order_id, product_id, product_name, variant_id, price, quantity, total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $item_id, $order_id, $item['product_id'], $item['product_name'],
            $item['variant_id'], $item['price'], $item['quantity'], $item['total']
        ]);
    }
    
    // Save address to user account if requested and user is logged in
    if ($save_address && $user_id) {
        try {
            $address_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO user_addresses (
                    address_id, user_id, first_name, last_name, phone, address, 
                    city, state, pincode, is_default, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
                ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                phone = VALUES(phone),
                address = VALUES(address),
                city = VALUES(city),
                state = VALUES(state),
                pincode = VALUES(pincode),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $address_id, $user_id, $firstName, $lastName, $phone,
                $address, $city, $state, $pincode
            ]);
        } catch (Exception $e) {
            // Address saving failed, but continue with order
            error_log("Failed to save address: " . $e->getMessage());
        }
    }
    
    // Handle payment processing
    if ($paymentMethod === 'cod') {
        // Cash on Delivery - Mark as confirmed
        $stmt = $pdo->prepare("UPDATE checkout_orders SET order_status = 'confirmed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Create a transaction record for COD
        $transaction_id = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                transaction_id, order_id, payment_method, amount, currency, 
                transaction_status, created_at
            ) VALUES (?, ?, 'cod', ?, 'INR', 'pending', NOW())
        ");
        $stmt->execute([$transaction_id, $order_id, $totalAmount]);
        
    } else if ($paymentMethod === 'cashfree') {
        // Create Cashfree order
        require_once 'includes/cashfree-handler.php';
        $cashfreeHandler = new CashfreeHandler($pdo);

        try {
            // Create transaction record
            $transaction_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (
                    transaction_id, order_id, payment_gateway, payment_method,
                    amount, currency, transaction_status, created_at
                ) VALUES (?, ?, 'cashfree', 'upi', ?, 'INR', 'pending', NOW())
            ");
            $stmt->execute([$transaction_id, $order_id, $totalAmount]);

            // Prepare order data for Cashfree
            $orderData = [
                'order_number' => $orderNumber,
                'amount' => $totalAmount,
                'email' => $email,
                'phone' => $phone,
                'customer_name' => $firstName . ' ' . $lastName,
                'user_id' => $user_id,
                'return_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-return.php',
                'notify_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/payment-webhook.php'
            ];

            $cashfreeOrder = $cashfreeHandler->createOrder($orderData);

            // Send order details to client (updated for API v3)
            echo json_encode([
                'success' => true,
                'payment_required' => true,
                'orderData' => [
                    'payment_session_id' => $cashfreeOrder['payment_session_id'],
                    'order_id' => $cashfreeOrder['order_id'],
                    'amount' => $totalAmount,
                    'transaction_id' => $transaction_id,
                    'order_id_internal' => $order_id
                ]
            ]);
            exit;
        } catch (Exception $e) {
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    } else if ($paymentMethod === 'razorpay') {
        // Create Razorpay order
        require_once 'includes/razorpay-handler.php';
        $razorpayHandler = new RazorpayHandler($pdo);
        
        try {
            // Create transaction record
            $transaction_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (
                    transaction_id, order_id, payment_gateway, payment_method, 
                    amount, currency, transaction_status, created_at
                ) VALUES (?, ?, 'razorpay', 'online', ?, 'INR', 'pending', NOW())
            ");
            $stmt->execute([$transaction_id, $order_id, $totalAmount]);
            
            // Create Razorpay order
            $razorpayOrder = $razorpayHandler->createOrder(
                $totalAmount,
                $order_number,
                [
                    'order_id' => $order_id,
                    'customer_email' => $email,
                    'customer_phone' => $phone
                ]
            );
            
            // Send order details to client
            echo json_encode([
                'success' => true,
                'payment_required' => true,
                'order' => [
                    'id' => $razorpayOrder->id,
                    'amount' => $totalAmount,
                    'order_number' => $order_number,
                    'transaction_id' => $transaction_id,
                    'order_id' => $order_id,
                    'email' => $email,
                    'phone' => $phone
                ]
            ]);
            exit;
        } catch (Exception $e) {
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Clear cart
    unset($_SESSION['cart']);
    
    // Store order details in session for confirmation page
    $_SESSION['order_success'] = [
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total_amount' => $totalAmount,
        'payment_method' => $paymentMethod,
        'customer_name' => $firstName . ' ' . $lastName,
        'email' => $email
    ];
    
    // Redirect to success page
    header('Location: order-success.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log error
    error_log("Order processing error: " . $e->getMessage());
    
    // Redirect back to checkout with error
    header('Location: checkout.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>
