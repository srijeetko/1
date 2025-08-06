<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
include '../../includes/db_connection.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
        case 'POST':
            handlePostRequest($pdo, $action);
            break;
        case 'PUT':
            handlePutRequest($pdo, $action);
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $action);
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'validate':
            validateCoupon($pdo);
            break;
        case 'list':
            listCoupons($pdo);
            break;
        case 'get':
            getCoupon($pdo);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case 'create':
            createCoupon($pdo);
            break;
        case 'apply':
            applyCoupon($pdo);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}

function handlePutRequest($pdo, $action) {
    switch ($action) {
        case 'update':
            updateCoupon($pdo);
            break;
        case 'toggle':
            toggleCouponStatus($pdo);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}

function handleDeleteRequest($pdo, $action) {
    switch ($action) {
        case 'delete':
            deleteCoupon($pdo);
            break;
        default:
            throw new Exception('Invalid action', 400);
    }
}

function validateCoupon($pdo) {
    $code = $_GET['code'] ?? '';
    $orderAmount = floatval($_GET['amount'] ?? 0);
    
    if (empty($code)) {
        throw new Exception('Coupon code is required', 400);
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 AND expires_at > NOW()
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired coupon code'
        ]);
        return;
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
        echo json_encode([
            'success' => false,
            'message' => 'Coupon usage limit exceeded'
        ]);
        return;
    }
    
    // Check minimum order amount
    if ($coupon['minimum_order_amount'] > $orderAmount) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimum order amount of ₹' . number_format($coupon['minimum_order_amount'], 2) . ' required'
        ]);
        return;
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($orderAmount * $coupon['discount_value']) / 100;
    } else {
        $discount = $coupon['discount_value'];
    }
    
    // Ensure discount doesn't exceed order amount
    $discount = min($discount, $orderAmount);
    
    echo json_encode([
        'success' => true,
        'coupon' => [
            'coupon_id' => $coupon['coupon_id'],
            'code' => $coupon['code'],
            'description' => $coupon['description'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
            'discount_amount' => $discount,
            'minimum_order_amount' => $coupon['minimum_order_amount']
        ],
        'discount_amount' => $discount,
        'final_amount' => $orderAmount - $discount
    ]);
}

function applyCoupon($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $couponId = $input['coupon_id'] ?? '';
    $orderId = $input['order_id'] ?? '';
    
    if (empty($couponId) || empty($orderId)) {
        throw new Exception('Coupon ID and Order ID are required', 400);
    }
    
    // Update coupon usage count
    $stmt = $pdo->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE coupon_id = ?");
    $stmt->execute([$couponId]);
    
    // Update order with coupon
    $stmt = $pdo->prepare("UPDATE checkout_orders SET coupon_id = ? WHERE order_id = ?");
    $stmt->execute([$couponId, $orderId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Coupon applied successfully'
    ]);
}

function listCoupons($pdo) {
    $stmt = $pdo->query("
        SELECT *, 
        CASE 
            WHEN expires_at < NOW() THEN 'Expired'
            WHEN is_active = 0 THEN 'Inactive'
            ELSE 'Active'
        END as status
        FROM coupons 
        ORDER BY created_at DESC
    ");
    $coupons = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'coupons' => $coupons
    ]);
}

function getCoupon($pdo) {
    $couponId = $_GET['id'] ?? '';
    
    if (empty($couponId)) {
        throw new Exception('Coupon ID is required', 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE coupon_id = ?");
    $stmt->execute([$couponId]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        throw new Exception('Coupon not found', 404);
    }
    
    echo json_encode([
        'success' => true,
        'coupon' => $coupon
    ]);
}

function createCoupon($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['code', 'discount_type', 'discount_value', 'expires_at'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required", 400);
        }
    }
    
    $couponId = bin2hex(random_bytes(16));
    $code = strtoupper(trim($input['code']));
    $description = trim($input['description'] ?? '');
    $discountType = $input['discount_type'];
    $discountValue = floatval($input['discount_value']);
    $minimumOrderAmount = floatval($input['minimum_order_amount'] ?? 0);
    $usageLimit = intval($input['usage_limit'] ?? 0);
    $expiresAt = $input['expires_at'];
    $isActive = $input['is_active'] ?? true;
    
    $stmt = $pdo->prepare("
        INSERT INTO coupons (coupon_id, code, description, discount_type, discount_value, minimum_order_amount, usage_limit, usage_count, expires_at, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
    ");
    
    $result = $stmt->execute([
        $couponId, $code, $description, $discountType, $discountValue, 
        $minimumOrderAmount, $usageLimit, $expiresAt, $isActive ? 1 : 0
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Coupon created successfully',
            'coupon_id' => $couponId
        ]);
    } else {
        throw new Exception('Failed to create coupon', 500);
    }
}

function updateCoupon($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $couponId = $input['coupon_id'] ?? '';
    
    if (empty($couponId)) {
        throw new Exception('Coupon ID is required', 400);
    }
    
    $code = strtoupper(trim($input['code']));
    $description = trim($input['description'] ?? '');
    $discountType = $input['discount_type'];
    $discountValue = floatval($input['discount_value']);
    $minimumOrderAmount = floatval($input['minimum_order_amount'] ?? 0);
    $usageLimit = intval($input['usage_limit'] ?? 0);
    $expiresAt = $input['expires_at'];
    $isActive = $input['is_active'] ?? true;
    
    $stmt = $pdo->prepare("
        UPDATE coupons 
        SET code = ?, description = ?, discount_type = ?, discount_value = ?, 
            minimum_order_amount = ?, usage_limit = ?, expires_at = ?, is_active = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE coupon_id = ?
    ");
    
    $result = $stmt->execute([
        $code, $description, $discountType, $discountValue, 
        $minimumOrderAmount, $usageLimit, $expiresAt, $isActive ? 1 : 0, $couponId
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Coupon updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update coupon', 500);
    }
}

function toggleCouponStatus($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $couponId = $input['coupon_id'] ?? '';
    $newStatus = $input['is_active'] ?? false;
    
    if (empty($couponId)) {
        throw new Exception('Coupon ID is required', 400);
    }
    
    $stmt = $pdo->prepare("UPDATE coupons SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE coupon_id = ?");
    $result = $stmt->execute([$newStatus ? 1 : 0, $couponId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Coupon status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update coupon status', 500);
    }
}

function deleteCoupon($pdo) {
    $couponId = $_GET['id'] ?? '';
    
    if (empty($couponId)) {
        throw new Exception('Coupon ID is required', 400);
    }
    
    // Check if coupon is used in any orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM checkout_orders WHERE coupon_id = ?");
    $stmt->execute([$couponId]);
    $usageCount = $stmt->fetchColumn();
    
    if ($usageCount > 0) {
        // Don't delete, just deactivate
        $stmt = $pdo->prepare("UPDATE coupons SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE coupon_id = ?");
        $result = $stmt->execute([$couponId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Coupon deactivated (cannot delete as it has been used in orders)'
        ]);
    } else {
        // Safe to delete
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE coupon_id = ?");
        $result = $stmt->execute([$couponId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Coupon deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete coupon', 500);
        }
    }
}
?>
