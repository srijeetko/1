<?php
/**
 * OTP API Endpoint
 * Handles OTP generation, verification, and resend operations
 * Alpha Nutrition - User Authentication System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

// Validate action
$allowedActions = ['generate', 'verify', 'resend', 'register', 'login', 'reset_password'];
if (!in_array($action, $allowedActions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    switch ($action) {
        case 'generate':
            handleGenerateOTP($input);
            break;
            
        case 'verify':
            handleVerifyOTP($input);
            break;
            
        case 'resend':
            handleResendOTP($input);
            break;
            
        case 'register':
            handleRegisterWithOTP($input);
            break;
            
        case 'login':
            handleLoginWithOTP($input);
            break;
            
        case 'reset_password':
            handlePasswordResetOTP($input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("OTP API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Generate OTP
 */
function handleGenerateOTP($input) {
    global $auth;
    
    $identifier = trim($input['identifier'] ?? '');
    $otpType = $input['otp_type'] ?? 'login';
    $userId = $input['user_id'] ?? null;
    $method = $input['method'] ?? 'whatsapp';
    
    if (empty($identifier)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifier is required']);
        return;
    }
    
    $result = $auth->otpHandler->generateOTP($identifier, $otpType, $userId, $method);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Verify OTP
 */
function handleVerifyOTP($input) {
    global $auth;
    
    $identifier = trim($input['identifier'] ?? '');
    $otpCode = trim($input['otp_code'] ?? '');
    $otpType = $input['otp_type'] ?? 'login';
    
    if (empty($identifier) || empty($otpCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifier and OTP code are required']);
        return;
    }
    
    $result = $auth->otpHandler->verifyOTP($identifier, $otpCode, $otpType);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Resend OTP
 */
function handleResendOTP($input) {
    global $auth;
    
    $identifier = trim($input['identifier'] ?? '');
    $otpType = $input['otp_type'] ?? 'login';
    
    if (empty($identifier)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifier is required']);
        return;
    }
    
    $result = $auth->resendOTP($identifier, $otpType);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Register with OTP
 */
function handleRegisterWithOTP($input) {
    global $auth;
    
    $userData = [
        'first_name' => trim($input['first_name'] ?? ''),
        'last_name' => trim($input['last_name'] ?? ''),
        'phone' => trim($input['phone'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'password' => $input['password'] ?? ''
    ];
    
    // Validation
    $requiredFields = ['first_name', 'last_name', 'phone', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($userData[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    if (strlen($userData['password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        return;
    }
    
    $result = $auth->registerWithOTP($userData);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Login with OTP
 */
function handleLoginWithOTP($input) {
    global $auth;
    
    $step = $input['step'] ?? 'request';
    
    if ($step === 'request') {
        $identifier = trim($input['identifier'] ?? '');
        
        if (empty($identifier)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Identifier is required']);
            return;
        }
        
        $result = $auth->loginWithOTP($identifier);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } elseif ($step === 'verify') {
        $identifier = trim($input['identifier'] ?? '');
        $otpCode = trim($input['otp_code'] ?? '');
        
        if (empty($identifier) || empty($otpCode)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Identifier and OTP code are required']);
            return;
        }
        
        $result = $auth->completeOTPLogin($identifier, $otpCode);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid step']);
    }
}

/**
 * Password reset with OTP
 */
function handlePasswordResetOTP($input) {
    global $auth;
    
    $step = $input['step'] ?? 'request';
    
    if ($step === 'request') {
        $identifier = trim($input['identifier'] ?? '');
        
        if (empty($identifier)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Identifier is required']);
            return;
        }
        
        $result = $auth->requestPasswordResetOTP($identifier);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } elseif ($step === 'reset') {
        $identifier = trim($input['identifier'] ?? '');
        $otpCode = trim($input['otp_code'] ?? '');
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($identifier) || empty($otpCode) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            return;
        }
        
        $result = $auth->resetPasswordWithOTP($identifier, $otpCode, $newPassword);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid step']);
    }
}

// Rate limiting helper
function checkRateLimit($identifier, $action) {
    $key = "rate_limit_{$action}_{$identifier}";
    $attempts = $_SESSION[$key] ?? 0;
    $lastAttempt = $_SESSION["{$key}_time"] ?? 0;
    
    // Reset counter if more than 1 minute has passed
    if (time() - $lastAttempt > 60) {
        $attempts = 0;
    }
    
    // Check if rate limit exceeded
    if ($attempts >= 5) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key] = $attempts + 1;
    $_SESSION["{$key}_time"] = time();
    
    return true;
}
?>
