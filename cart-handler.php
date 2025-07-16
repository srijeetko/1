<?php
session_start();
include 'includes/db_connection.php';

header('Content-Type: application/json');

if (!isset($pdo) || !$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add_to_cart':
            $productId = $input['product_id'] ?? '';
            $quantity = intval($input['quantity'] ?? 1);
            $variantId = $input['variant_id'] ?? null;
            
            if (empty($productId)) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit();
            }
            
            // For now, use session-based cart since user authentication might not be implemented
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Create a unique key for the cart item
            $cartKey = $productId . '_' . ($variantId ?? 'default');
            
            // Check if item already exists in cart
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Get cart count
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'cart_count' => $cartCount,
                'debug_cart' => $_SESSION['cart'] // Debug: show cart contents
            ]);
            break;
            
        case 'remove_from_cart':
            $productId = $input['product_id'] ?? '';
            $variantId = $input['variant_id'] ?? null;
            
            if (empty($productId)) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit();
            }
            
            $cartKey = $productId . '_' . ($variantId ?? 'default');
            
            if (isset($_SESSION['cart'][$cartKey])) {
                unset($_SESSION['cart'][$cartKey]);
            }
            
            // Get updated cart count
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product removed from cart',
                'cart_count' => $cartCount
            ]);
            break;
            
        case 'update_quantity':
            $productId = $input['product_id'] ?? '';
            $variantId = $input['variant_id'] ?? null;
            $quantity = intval($input['quantity'] ?? 1);
            
            if (empty($productId) || $quantity < 1) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
                exit();
            }
            
            $cartKey = $productId . '_' . ($variantId ?? 'default');
            
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            }
            
            // Get updated cart count
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cart updated successfully',
                'cart_count' => $cartCount
            ]);
            break;
            
        case 'get_cart_count':
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'cart_count' => $cartCount
            ]);
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cart cleared successfully',
                'cart_count' => 0
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
