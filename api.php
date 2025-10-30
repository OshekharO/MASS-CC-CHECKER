<?php
/**
 * API Endpoint for CC Checker
 * 
 * @author OshekharO
 * @version 2.0
 */

require_once 'config.php';

// Start session if enabled
if (SESSION_ENABLED) {
    session_start();
}

// Set security headers
if (ENABLE_SECURITY_HEADERS) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Content-Type: application/json');
}

// Enable CORS if needed
if (in_array('*', ALLOWED_ORIGINS) || isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], ALLOWED_ORIGINS)) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

/**
 * Validate card number using Luhn algorithm
 */
function validateLuhn($number) {
    $number = preg_replace('/\D/', '', $number);
    $sum = 0;
    $length = strlen($number);
    
    for ($i = $length - 1; $i >= 0; $i--) {
        $digit = intval($number[$i]);
        
        if (($length - $i) % 2 == 0) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    return ($sum % 10) === 0;
}

/**
 * Detect card brand
 */
function detectCardBrand($number) {
    global $CARD_BRANDS;
    $number = preg_replace('/\D/', '', $number);
    
    foreach ($CARD_BRANDS as $brand => $pattern) {
        if (preg_match($pattern, $number)) {
            return ucfirst($brand);
        }
    }
    
    return 'Unknown';
}

/**
 * Check rate limit
 */
function checkRateLimit() {
    if (!RATE_LIMIT_ENABLED || !SESSION_ENABLED) {
        return true;
    }
    
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 0;
        $_SESSION['request_start_time'] = time();
    }
    
    $current_time = time();
    $elapsed_time = $current_time - $_SESSION['request_start_time'];
    
    // Reset counter if time window has passed
    if ($elapsed_time > RATE_LIMIT_TIME_WINDOW) {
        $_SESSION['request_count'] = 0;
        $_SESSION['request_start_time'] = $current_time;
    }
    
    $_SESSION['request_count']++;
    
    return $_SESSION['request_count'] <= RATE_LIMIT_MAX_REQUESTS;
}

/**
 * Send JSON response
 */
function sendResponse($error, $msg, $extra = []) {
    $response = array_merge([
        'error' => $error,
        'msg' => $msg
    ], $extra);
    
    echo json_encode($response);
    exit;
}

// Main logic
try {
    // Check rate limit
    if (!checkRateLimit()) {
        sendResponse(5, '<div><b style="color:#FF0000;">Rate Limit Exceeded</b> | Please wait before making more requests</div>');
    }
    
    // Get posted data
    $data = isset($_POST["data"]) ? trim($_POST["data"]) : '';
    
    if (empty($data)) {
        sendResponse(4, '<div><b style="color:#FF0000;">Error</b> | No data provided</div>');
    }
    
    // Parse card data - support flexible format
    $pattern = "/^([\\d]{" . MIN_CARD_LENGTH . "," . MAX_CARD_LENGTH . "})\\|([\\d]{2})\\|([\\d]{4})\\|([\\d]{3,4})$/";
    
    if (!preg_match($pattern, $data, $matches)) {
        sendResponse(4, '<div><b style="color:#FF0000;">Invalid Format</b> | Expected format: card_number|MM|YYYY|CVV</div>');
    }
    
    $num = $matches[1];
    $expm = intval($matches[2]);
    $expy = intval($matches[3]);
    $cvv = $matches[4];
    
    $format = $num . "|" . $expm . "|" . $expy . "|" . $cvv;
    
    // Validate card number length
    $cardLength = strlen($num);
    if ($cardLength < MIN_CARD_LENGTH || $cardLength > MAX_CARD_LENGTH) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Invalid card length</div>");
    }
    
    // Validate Luhn algorithm
    if (ENABLE_LUHN_CHECK && !validateLuhn($num)) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Failed Luhn check</div>");
    }
    
    // Detect card brand
    $brand = detectCardBrand($num);
    
    // Validate expiry month
    if ($expm < 1 || $expm > 12) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Invalid expiry month</div>");
    }
    
    // Validate expiry year
    if ($expy < MIN_VALID_YEAR) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Card expired</div>");
    }
    
    // Check if card is expired (compare with current date)
    $currentYear = intval(date('Y'));
    $currentMonth = intval(date('m'));
    
    if ($expy < $currentYear || ($expy == $currentYear && $expm < $currentMonth)) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Card expired ($brand)</div>");
    }
    
    // Validate CVV length based on card type
    $cvvLength = strlen($cvv);
    if (($brand === 'Amex' && $cvvLength != 4) || ($brand !== 'Amex' && $cvvLength != 3)) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | Invalid CVV length for $brand</div>");
    }
    
    // All validations passed - simulate check result
    // Note: In real implementation, this would call actual payment gateway
    $rand = rand(1, 3);
    
    if ($rand == 1) {
        sendResponse(1, "<div><b style='color:#008000;'>Live</b> | $format | $brand - Valid Card ✓</div>", ['brand' => $brand]);
    } elseif ($rand == 2) {
        sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | $format | $brand - Declined</div>", ['brand' => $brand]);
    } else {
        sendResponse(3, "<div><b style='color:#800080;'>Unknown</b> | $format | $brand - Status Unknown</div>", ['brand' => $brand]);
    }
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        sendResponse(4, '<div><b style="color:#FF0000;">Error</b> | ' . htmlspecialchars($e->getMessage()) . '</div>');
    } else {
        sendResponse(4, '<div><b style="color:#FF0000;">Error</b> | An error occurred while processing your request</div>');
    }
}
?>
