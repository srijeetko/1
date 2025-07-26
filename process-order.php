<?php
// Prevent any output before JSON response
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent JSON corruption

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
                // Get variant price modifier and add to base price
                try {
                    $stmt = $pdo->prepare("SELECT price_modifier FROM product_variants WHERE variant_id = ?");
                    $stmt->execute([$variant_id]);
                    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($variant && isset($variant['price_modifier'])) {
                        $price = $price + floatval($variant['price_modifier']);
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

            // Debug logging for price calculation
            error_log("Price calculation - Product: {$product['name']}, Base Price: {$product['price']}, Final Price: $price, Quantity: $quantity, Item Total: $itemTotal");

            $orderItems[] = [
                'product_id' => $product_id,
                'product_name' => $product['name'],
                'variant_id' => $variant_id,
                'variant_name' => null, // Will be populated if variants are implemented
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

    $result = $stmt->execute([
        $order_id, $order_number, $user_id, $firstName, $lastName, $email, $phone,
        $address, $city, $state, $pincode, $totalAmount, $paymentMethod
    ]);

    // Log order creation for debugging
    error_log("Order created - ID: $order_id, Number: $order_number, Amount: $totalAmount, Result: " . ($result ? 'Success' : 'Failed'));
    
    // Insert order items with error checking
    foreach ($orderItems as $item) {
        $item_id = bin2hex(random_bytes(16));

        try {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_item_id, order_id, product_id, product_name, variant_id,
                    variant_name, quantity, unit_price, total_price, price, total, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $item_id, $order_id, $item['product_id'], $item['product_name'],
                $item['variant_id'], $item['variant_name'] ?? null, $item['quantity'],
                $item['price'], $item['total'], $item['price'], $item['total']
            ]);

            if (!$result) {
                throw new Exception("Failed to insert order item: " . $item['product_name']);
            }

            // Log successful item insertion
            error_log("Order item inserted - Product: {$item['product_name']}, Qty: {$item['quantity']}, Price: {$item['price']}, Total: {$item['total']}");

        } catch (Exception $e) {
            // Log the error but continue with other items
            error_log("Order item insertion failed - Product: {$item['product_name']}, Error: " . $e->getMessage());

            // Log to specific file for better tracking
            $logEntry = date('Y-m-d H:i:s') . " - ORDER_ITEM_ERROR - Order: $order_id, Product: {$item['product_name']}, Error: " . $e->getMessage() . "\n";
            file_put_contents('logs/order_errors.log', $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    // Verify order items were inserted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $itemCount = $stmt->fetchColumn();

    if ($itemCount == 0) {
        error_log("WARNING: No order items were inserted for order $order_id");
        $logEntry = date('Y-m-d H:i:s') . " - ORDER_ITEMS_MISSING - Order: $order_id, Expected: " . count($orderItems) . ", Actual: 0\n";
        file_put_contents('logs/order_errors.log', $logEntry, FILE_APPEND | LOCK_EX);
    } else {
        error_log("Order items verification - Order: $order_id, Items inserted: $itemCount");
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
        // Create Cashfree order with enhanced error handling
        require_once 'includes/cashfree-handler.php';
        $cashfreeHandler = new CashfreeHandler($pdo);

        try {
            // Create transaction record FIRST
            $transaction_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions (
                    transaction_id, order_id, payment_gateway, payment_method,
                    amount, currency, transaction_status, created_at
                ) VALUES (?, ?, 'cashfree', 'upi', ?, 'INR', 'pending', NOW())
            ");
            $stmt->execute([$transaction_id, $order_id, $totalAmount]);

            // Enhanced logging for debugging
            error_log("Cashfree: Created transaction record - ID: $transaction_id, Order: $order_id, Amount: $totalAmount");

            // Log to specific file for better tracking
            $logEntry = date('Y-m-d H:i:s') . " - ORDER_CREATION - Transaction created - Order: $order_id, Transaction: $transaction_id, Amount: $totalAmount\n";
            file_put_contents('logs/order_errors.log', $logEntry, FILE_APPEND | LOCK_EX);

            // Prepare order data for Cashfree
            $orderData = [
                'order_number' => $order_number,
                'amount' => $totalAmount,
                'email' => $email,
                'phone' => $phone,
                'customer_name' => $firstName . ' ' . $lastName,
                'user_id' => $user_id,
                'return_url' => CASHFREE_BASE_URL . '/payment-return.php',
                'notify_url' => CASHFREE_BASE_URL . '/payment-webhook.php'
            ];

            // Log order data for debugging
            error_log("Cashfree: Creating order with data - " . json_encode($orderData));

            $cashfreeOrder = $cashfreeHandler->createOrder($orderData);

            // Validate Cashfree response
            if (!$cashfreeOrder || !isset($cashfreeOrder['payment_session_id'])) {
                throw new Exception('Cashfree order creation failed - no payment session ID received');
            }

            // Update transaction with Cashfree order details
            $stmt = $pdo->prepare("
                UPDATE payment_transactions
                SET gateway_transaction_id = ?, gateway_response = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([
                $cashfreeOrder['order_id'] ?? $order_number,
                json_encode($cashfreeOrder),
                $transaction_id
            ]);

            // Enhanced logging for successful order creation
            error_log("Cashfree: Order created successfully - " . json_encode($cashfreeOrder));
            $logEntry = date('Y-m-d H:i:s') . " - ORDER_CREATION - Cashfree order created successfully - Order: $order_id, Cashfree ID: " . ($cashfreeOrder['order_id'] ?? 'unknown') . "\n";
            file_put_contents('logs/order_errors.log', $logEntry, FILE_APPEND | LOCK_EX);

            // Clear any output buffer to prevent JSON corruption
            if (ob_get_level()) {
                ob_clean();
            }

            // Send order details to client (updated for API v3)
            header('Content-Type: application/json');
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
            // Enhanced error logging and cleanup
            error_log("Cashfree order creation failed: " . $e->getMessage());
            $logEntry = date('Y-m-d H:i:s') . " - ORDER_CREATION_FAILED - Order: $order_id, Error: " . $e->getMessage() . "\n";
            file_put_contents('logs/order_errors.log', $logEntry, FILE_APPEND | LOCK_EX);

            // Clean up - delete the order and transaction records since payment failed
            try {
                $stmt = $pdo->prepare("DELETE FROM payment_transactions WHERE order_id = ?");
                $stmt->execute([$order_id]);

                $stmt = $pdo->prepare("DELETE FROM checkout_orders WHERE order_id = ?");
                $stmt->execute([$order_id]);

                error_log("Cashfree: Cleaned up failed order records for order_id: $order_id");
            } catch (Exception $cleanupError) {
                error_log("Cashfree: Failed to cleanup order records: " . $cleanupError->getMessage());
            }

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
            
            // Clear any output buffer to prevent JSON corruption
            ob_clean();

            // Send order details to client
            header('Content-Type: application/json');
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

    // Check if this is an AJAX request (for payment processing)
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($isAjax || (isset($_POST['payment_method']) && $_POST['payment_method'] !== 'cod')) {
        // Clear any output buffer to prevent JSON corruption
        ob_clean();

        // Return JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    } else {
        // Redirect back to checkout with error for regular form submissions
        header('Location: checkout.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}
?>
