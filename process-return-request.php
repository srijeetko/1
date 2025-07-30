<?php
include 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: return-request.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get form data
    $order_id = $_POST['order_id'] ?? '';
    $return_items = $_POST['return_items'] ?? [];
    $return_quantities = $_POST['return_quantity'] ?? [];
    $return_reason_id = $_POST['return_reason_id'] ?? '';
    $custom_reason = $_POST['custom_reason'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    
    // Validate required fields
    if (empty($order_id) || empty($return_items) || empty($return_reason_id) || empty($customer_email)) {
        throw new Exception('Please fill in all required fields and select at least one item to return.');
    }
    
    // Verify order exists and is paid
    $stmt = $pdo->prepare("SELECT * FROM checkout_orders WHERE order_id = ? AND payment_status = 'paid'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found or not eligible for return.');
    }
    
    // Check if return window is still valid (30 days)
    $order_date = new DateTime($order['created_at']);
    $current_date = new DateTime();
    $days_since_order = $current_date->diff($order_date)->days;
    
    if ($days_since_order > 30) {
        throw new Exception('Return window has expired. Returns must be requested within 30 days of order.');
    }
    
    // Generate return ID and return number
    $return_id = str_replace('-', '', bin2hex(random_bytes(18)));
    $return_number = 'RET' . date('Ymd') . strtoupper(substr($return_id, 0, 6));
    
    // Calculate total return amount
    $total_return_amount = 0;
    $return_item_details = [];
    
    foreach ($return_items as $order_item_id) {
        // Get order item details
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name,
                   CASE
                       WHEN pv.size IS NOT NULL AND pv.color IS NOT NULL THEN CONCAT(pv.size, ' - ', pv.color)
                       WHEN pv.size IS NOT NULL THEN pv.size
                       WHEN pv.color IS NOT NULL THEN pv.color
                       ELSE NULL
                   END as variant_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
            WHERE oi.order_item_id = ? AND oi.order_id = ?
        ");
        $stmt->execute([$order_item_id, $order_id]);
        $order_item = $stmt->fetch();
        
        if (!$order_item) {
            throw new Exception('Invalid order item selected.');
        }
        
        $return_quantity = intval($return_quantities[$order_item_id] ?? 0);
        
        if ($return_quantity <= 0 || $return_quantity > $order_item['quantity']) {
            throw new Exception('Invalid return quantity for ' . $order_item['product_name']);
        }
        
        $item_return_amount = ($order_item['price'] * $return_quantity);
        $total_return_amount += $item_return_amount;
        
        $return_item_details[] = [
            'order_item_id' => $order_item_id,
            'product_id' => $order_item['product_id'],
            'product_name' => $order_item['product_name'],
            'variant_id' => $order_item['variant_id'],
            'variant_name' => $order_item['variant_name'],
            'quantity_ordered' => $order_item['quantity'],
            'quantity_returned' => $return_quantity,
            'unit_price' => $order_item['price'],
            'total_amount' => $item_return_amount
        ];
    }
    
    // Get return reason details
    $stmt = $pdo->prepare("SELECT * FROM return_reasons WHERE reason_id = ?");
    $stmt->execute([$return_reason_id]);
    $return_reason = $stmt->fetch();
    
    if (!$return_reason) {
        throw new Exception('Invalid return reason selected.');
    }
    
    // Calculate refund amount based on return reason
    $refund_percentage = $return_reason['refund_percentage'];
    $refund_amount = ($total_return_amount * $refund_percentage) / 100;
    
    // Determine initial status based on return reason
    $initial_status = $return_reason['auto_approve'] ? 'approved' : 'pending';
    
    // Insert return request
    $stmt = $pdo->prepare("
        INSERT INTO return_requests (
            return_id, return_number, order_id, customer_email, customer_phone,
            return_reason_id, custom_reason, return_status, total_return_amount,
            refund_amount, pickup_address, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $pickup_address = $order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode'];
    
    $stmt->execute([
        $return_id,
        $return_number,
        $order_id,
        $customer_email,
        $customer_phone,
        $return_reason_id,
        $custom_reason,
        $initial_status,
        $total_return_amount,
        $refund_amount,
        $pickup_address
    ]);
    
    // Insert return items
    foreach ($return_item_details as $item) {
        $return_item_id = str_replace('-', '', bin2hex(random_bytes(18)));
        
        $stmt = $pdo->prepare("
            INSERT INTO return_items (
                return_item_id, return_id, order_item_id, product_id, product_name,
                variant_id, variant_name, quantity_ordered, quantity_returned,
                unit_price, total_amount, refund_amount, is_approved
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $item_refund_amount = ($item['total_amount'] * $refund_percentage) / 100;
        $is_approved = $return_reason['auto_approve'] ? 1 : 0;
        
        $stmt->execute([
            $return_item_id,
            $return_id,
            $item['order_item_id'],
            $item['product_id'],
            $item['product_name'],
            $item['variant_id'],
            $item['variant_name'],
            $item['quantity_ordered'],
            $item['quantity_returned'],
            $item['unit_price'],
            $item['total_amount'],
            $item_refund_amount,
            $is_approved
        ]);
    }
    
    // Insert status history
    $history_id = str_replace('-', '', bin2hex(random_bytes(18)));
    $stmt = $pdo->prepare("
        INSERT INTO return_status_history (
            history_id, return_id, previous_status, new_status, change_reason, created_at
        ) VALUES (?, ?, NULL, ?, 'Return request submitted by customer', NOW())
    ");
    $stmt->execute([$history_id, $return_id, $initial_status]);
    
    $pdo->commit();
    
    // Redirect to success page
    header("Location: return-success.php?return_number=" . urlencode($return_number));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Redirect back with error
    $error_message = urlencode($e->getMessage());
    $order_id = urlencode($_POST['order_id'] ?? '');
    header("Location: return-request.php?order_id=$order_id&error=$error_message");
    exit;
}
?>
