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

// Getting posted data
$data = $_POST["data"];
if (!empty($data)) {
    // Splitting the data - support flexible card lengths
    $pattern = "/^([\\d]{" . MIN_CARD_LENGTH . "," . MAX_CARD_LENGTH . "})\\|([\\d]{2})\\|([\\d]{4})\\|([\\d]{3,4})$/";
    
    if (preg_match($pattern, $data, $matches)) {
        $num = $matches[1];
        $expm = $matches[2];
        $expy = $matches[3];
        $cvv = $matches[4];

        $format = $num . "|" . $expm . "|" . $expy . "|" . $cvv;

        // Validate with Luhn algorithm if enabled
        if (defined('ENABLE_LUHN_CHECK') && ENABLE_LUHN_CHECK && !validateLuhn($num)) {
            echo "{\"error\":2,\"msg\":\"<div><b style='color:#FF0000;'>Die</b> | " . $format . " | Failed Luhn check</div>\"}";
        } elseif ($expy >= MIN_VALID_YEAR && $expm <= 12) {
            $rand = rand(1, 3);
            if ($rand == 1) {
                echo "{\"error\":1,\"msg\":\"<div><b style='color:#008000;'>Live</b> | " . $format . " $0.5 Checked - OshekharO</div>\"}";
            } elseif ($rand == 2) {
                echo "{\"error\":2,\"msg\":\"<div><b style='color:#FF0000;'>Die</b> | " . $format . " [GATE:01] @/Checked - OshekharO</div>\"}";
            } elseif ($rand == 3) {
                echo "{\"error\":3,\"msg\":\"<div><b style='color:#800080;'>Unknown</b> | " . $format . " | [GATE:01] @/Checked - OshekharO</div>\"}";
            }
        } else {
            echo "{\"error\":4,\"msg\":\"<b>Check the validity of a credit card</b> | " . $format . " [GATE:01] @/Checked - OshekharO\"}";
        }
    } else {
        // Invalid format
        echo "{\"error\":4,\"msg\":\"<div><b style='color:#FF0000;'>Invalid Format</b> | Please use format: card_number|MM|YYYY|CVV</div>\"}";
    }
}
?>
