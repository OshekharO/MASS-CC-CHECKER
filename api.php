<?php
/**
 * API endpoint for credit card validation
 * 
 * This script validates credit card data including Luhn algorithm check,
 * format validation, and expiry date validation.
 * 
 * @author OshekharO
 * @license MIT
 */

// Include configuration file
require_once 'config.php';

/**
 * Validates a credit card number using the Luhn algorithm
 * 
 * @param string $number The card number to validate
 * @return bool True if valid, false otherwise
 */
function validateLuhn($number) {
    // Remove any non-digit characters
    $number = preg_replace('/\D/', '', $number);
    
    // Check if number is empty or contains non-digits
    if (empty($number) || !ctype_digit($number)) {
        return false;
    }
    
    $sum = 0;
    $numDigits = strlen($number);
    $parity = $numDigits % 2;
    
    for ($i = 0; $i < $numDigits; $i++) {
        $digit = (int)$number[$i];
        
        // Double every second digit
        if ($i % 2 == $parity) {
            $digit *= 2;
            // If result is greater than 9, subtract 9
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    return ($sum % 10) === 0;
}

/**
 * Validates the format and content of card data
 * 
 * @param array $cardData Array containing card details [num, expm, expy, cvv]
 * @return array ['valid' => bool, 'error' => string]
 */
function validateCardData($cardData) {
    if (count($cardData) !== 4) {
        return ['valid' => false, 'error' => MSG_INVALID_FORMAT];
    }
    
    list($num, $expm, $expy, $cvv) = $cardData;
    
    // Validate card number length (13-19 digits)
    $numLength = strlen($num);
    if ($numLength < CARD_MIN_LENGTH || $numLength > CARD_MAX_LENGTH || !ctype_digit($num)) {
        return ['valid' => false, 'error' => MSG_INVALID_FORMAT . ' (card number must be ' . CARD_MIN_LENGTH . '-' . CARD_MAX_LENGTH . ' digits)'];
    }
    
    // Validate Luhn algorithm if enabled
    if (ENABLE_LUHN_CHECK && !validateLuhn($num)) {
        return ['valid' => false, 'error' => MSG_INVALID_LUHN];
    }
    
    // Validate expiry month (1-12)
    $expmInt = (int)$expm;
    if (!ctype_digit($expm) || $expmInt < 1 || $expmInt > MAX_EXPIRY_MONTH) {
        return ['valid' => false, 'error' => MSG_INVALID_EXPIRY . ' (month must be 01-12)'];
    }
    
    // Validate expiry year (4 digits and >= minimum year)
    $expyInt = (int)$expy;
    if (!ctype_digit($expy) || strlen($expy) !== 4 || $expyInt < MIN_EXPIRY_YEAR) {
        return ['valid' => false, 'error' => MSG_INVALID_EXPIRY . ' (year must be >= ' . MIN_EXPIRY_YEAR . ')'];
    }
    
    // Check if card has expired
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    if ($expyInt < $currentYear || ($expyInt == $currentYear && $expmInt < $currentMonth)) {
        return ['valid' => false, 'error' => MSG_EXPIRED_CARD];
    }
    
    // Validate CVV length (3-4 digits)
    $cvvLength = strlen($cvv);
    if ($cvvLength < CVV_MIN_LENGTH || $cvvLength > CVV_MAX_LENGTH || !ctype_digit($cvv)) {
        return ['valid' => false, 'error' => MSG_INVALID_CVV . ' (must be ' . CVV_MIN_LENGTH . '-' . CVV_MAX_LENGTH . ' digits)'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Sends JSON response
 * 
 * @param int $errorCode Error code (1=live, 2=die, 3=unknown, 4=invalid)
 * @param string $message Message to display
 */
function sendResponse($errorCode, $message) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $errorCode, 'msg' => $message]);
}

// Main processing logic
$data = isset($_POST["data"]) ? trim($_POST["data"]) : '';

if (empty($data)) {
    sendResponse(4, '<b>' . MSG_INVALID_FORMAT . '</b> | No data provided');
    exit;
}

// Build regex pattern to support 13-19 digit card numbers and 3-4 digit CVV
$pattern = sprintf(
    "/^(\\d{%d,%d})\\|(\\d{2})\\|(\\d{4})\\|(\\d{%d,%d})$/",
    CARD_MIN_LENGTH,
    CARD_MAX_LENGTH,
    CVV_MIN_LENGTH,
    CVV_MAX_LENGTH
);

if (!preg_match($pattern, $data, $matches)) {
    sendResponse(4, '<b>' . MSG_INVALID_FORMAT . '</b> | Expected format: card_number|MM|YYYY|CVV');
    exit;
}

// Extract card data
$datas = explode("|", $matches[0]);
$num = $datas[0];
$expm = $datas[1];
$expy = $datas[2];
$cvv = $datas[3];

// Sanitize format string for output
$format = htmlspecialchars($num . "|" . $expm . "|" . $expy . "|" . $cvv, ENT_QUOTES, 'UTF-8');

// Validate card data
$validationResult = validateCardData($datas);

if (!$validationResult['valid']) {
    sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | " . $format . " | " . htmlspecialchars($validationResult['error']) . " - OshekharO</div>");
    exit;
}

// If all validations pass, proceed with random response (simulating check)
$rand = rand(1, 3);
if ($rand == 1) {
    sendResponse(1, "<div><b style='color:#008000;'>Live</b> | " . $format . " | \$0.5 Checked - OshekharO</div>");
} elseif ($rand == 2) {
    sendResponse(2, "<div><b style='color:#FF0000;'>Die</b> | " . $format . " | [GATE:01] @/Checked - OshekharO</div>");
} else {
    sendResponse(3, "<div><b style='color:#800080;'>Unknown</b> | " . $format . " | [GATE:01] @/Checked - OshekharO</div>");
}
?>
