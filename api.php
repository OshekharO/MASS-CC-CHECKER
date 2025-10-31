<?php
/**
 * API Endpoint for CC Checker
 * Integrated with config.php and Luhn validation
 * 
 * @author OshekharO
 */

// Load configuration if available
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    // Default settings if config.php doesn't exist
    define('ENABLE_LUHN_CHECK', true);
    define('MIN_CARD_LENGTH', 13);
    define('MAX_CARD_LENGTH', 19);
    define('MIN_VALID_YEAR', 2024);
    define('ENABLE_RATE_LIMITING', true);
    define('MAX_REQUESTS_PER_MINUTE', 60);
    define('RATE_LIMIT_DURATION', 60);
    define('ENABLE_BIN_DETECTION', true);
    define('ENABLE_CARD_BRAND_DETECTION', true);
    define('ENABLE_ERROR_LOGGING', true);
    define('LOG_FILE_PATH', 'logs/error.log');
}

/**
 * Rate limiting implementation
 */
function checkRateLimit() {
    if (!defined('ENABLE_RATE_LIMITING') || !ENABLE_RATE_LIMITING) {
        return true;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateFile = sys_get_temp_dir() . '/cc_rate_' . md5($ip) . '.txt';
    
    $currentTime = time();
    $requests = [];
    
    // Read existing requests
    if (file_exists($rateFile)) {
        $data = file_get_contents($rateFile);
        $requests = $data ? json_decode($data, true) : [];
    }
    
    // Filter requests within the time window
    $requests = array_filter($requests, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < RATE_LIMIT_DURATION;
    });
    
    // Check if limit exceeded
    if (count($requests) >= MAX_REQUESTS_PER_MINUTE) {
        return false;
    }
    
    // Add current request
    $requests[] = $currentTime;
    file_put_contents($rateFile, json_encode($requests));
    
    return true;
}

/**
 * Error logging function
 */
function logError($message) {
    if (!defined('ENABLE_ERROR_LOGGING') || !ENABLE_ERROR_LOGGING) {
        return;
    }
    
    $logDir = dirname(LOG_FILE_PATH);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logMessage = "[$timestamp] [$ip] $message\n";
    
    @error_log($logMessage, 3, LOG_FILE_PATH);
}

/**
 * Detect card brand based on BIN
 */
function detectCardBrand($number) {
    if (!defined('ENABLE_CARD_BRAND_DETECTION') || !ENABLE_CARD_BRAND_DETECTION) {
        return 'Unknown';
    }
    
    $number = preg_replace('/\D/', '', $number);
    
    // Visa
    if (preg_match('/^4/', $number)) {
        return 'Visa';
    }
    // Mastercard
    if (preg_match('/^(5[1-5]|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/', $number)) {
        return 'Mastercard';
    }
    // American Express
    if (preg_match('/^3[47]/', $number)) {
        return 'American Express';
    }
    // Discover
    if (preg_match('/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5])|64[4-9]|65)/', $number)) {
        return 'Discover';
    }
    // Diners Club
    if (preg_match('/^3(?:0[0-5]|[68])/', $number)) {
        return 'Diners Club';
    }
    // JCB
    if (preg_match('/^35/', $number)) {
        return 'JCB';
    }
    
    return 'Unknown';
}

/**
 * Get BIN information
 */
function getBINInfo($number) {
    if (!defined('ENABLE_BIN_DETECTION') || !ENABLE_BIN_DETECTION) {
        return '';
    }
    
    $brand = detectCardBrand($number);
    $bin = substr($number, 0, 6);
    
    return " | BIN: $bin | Brand: $brand";
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
 * Validate CVV length based on card brand
 */
function validateCVV($cvv, $cardBrand) {
    $cvvLength = strlen($cvv);
    
    // American Express uses 4-digit CVV
    if ($cardBrand === 'American Express') {
        return $cvvLength === 4;
    }
    
    // Most other cards use 3-digit CVV
    return $cvvLength === 3;
}

// Check rate limit
if (!checkRateLimit()) {
    http_response_code(429);
    echo json_encode([
        'error' => 4,
        'msg' => '<div><b style="color:#FF0000;">Rate Limit Exceeded</b> | Please wait before making more requests</div>'
    ]);
    logError('Rate limit exceeded');
    exit;
}

// Getting posted data
$data = $_POST["data"] ?? '';
if (!empty($data)) {
    // Sanitize input
    $data = trim($data);
    
    // Splitting the data - support flexible card lengths
    $pattern = "/^([\\d]{" . MIN_CARD_LENGTH . "," . MAX_CARD_LENGTH . "})\\|([\\d]{2})\\|([\\d]{4})\\|([\\d]{3,4})$/";
    
    if (preg_match($pattern, $data, $matches)) {
        $num = $matches[1];
        $expm = $matches[2];
        $expy = $matches[3];
        $cvv = $matches[4];

        $format = $num . "|" . $expm . "|" . $expy . "|" . $cvv;
        $binInfo = getBINInfo($num);
        $cardBrand = detectCardBrand($num);

        // Validate CVV length
        if (!validateCVV($cvv, $cardBrand)) {
            $expectedLength = ($cardBrand === 'American Express') ? 4 : 3;
            echo json_encode([
                'error' => 2,
                'msg' => "<div><b style='color:#FF0000;'>Die</b> | " . $format . " | Invalid CVV length (expected $expectedLength digits for $cardBrand)</div>"
            ]);
            exit;
        }

        // Validate with Luhn algorithm if enabled
        if (defined('ENABLE_LUHN_CHECK') && ENABLE_LUHN_CHECK && !validateLuhn($num)) {
            echo json_encode([
                'error' => 2,
                'msg' => "<div><b style='color:#FF0000;'>Die</b> | " . $format . "$binInfo | Failed Luhn check</div>"
            ]);
            exit;
        } elseif ($expy >= MIN_VALID_YEAR && $expm >= 1 && $expm <= 12) {
            $rand = rand(1, 3);
            if ($rand == 1) {
                echo json_encode([
                    'error' => 1,
                    'msg' => "<div><b style='color:#008000;'>Live</b> | " . $format . "$binInfo | \$0.5 Checked - OshekharO</div>"
                ]);
            } elseif ($rand == 2) {
                echo json_encode([
                    'error' => 2,
                    'msg' => "<div><b style='color:#FF0000;'>Die</b> | " . $format . "$binInfo | [GATE:01] @/Checked - OshekharO</div>"
                ]);
            } elseif ($rand == 3) {
                echo json_encode([
                    'error' => 3,
                    'msg' => "<div><b style='color:#800080;'>Unknown</b> | " . $format . "$binInfo | [GATE:01] @/Checked - OshekharO</div>"
                ]);
            }
        } else {
            $errorMsg = ($expm < 1 || $expm > 12) ? 'Invalid expiry month' : 'Expired card';
            echo json_encode([
                'error' => 4,
                'msg' => "<div><b style='color:#FF0000;'>Invalid</b> | " . $format . "$binInfo | $errorMsg</div>"
            ]);
        }
    } else {
        // Invalid format
        echo json_encode([
            'error' => 4,
            'msg' => "<div><b style='color:#FF0000;'>Invalid Format</b> | Please use format: card_number|MM|YYYY|CVV</div>"
        ]);
        logError("Invalid format received: $data");
    }
} else {
    echo json_encode([
        'error' => 4,
        'msg' => "<div><b style='color:#FF0000;'>Error</b> | No data received</div>"
    ]);
    logError('Empty data received');
}
?>
