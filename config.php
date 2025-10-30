<?php
/**
 * Configuration file for CC Checker
 * 
 * @author OshekharO
 * @version 2.0
 */

// Error reporting (set to false in production)
define('DEBUG_MODE', false);

// Rate limiting settings
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 100); // Max requests per session
define('RATE_LIMIT_TIME_WINDOW', 3600); // Time window in seconds (1 hour)

// Card validation settings
define('ENABLE_LUHN_CHECK', true);
define('MIN_CARD_LENGTH', 13);
define('MAX_CARD_LENGTH', 19);

// Expiry validation
define('MIN_VALID_YEAR', 2024); // Cards must be valid from this year onwards

// Response settings
define('RESPONSE_FORMAT', 'json'); // json or xml

// Session settings
define('SESSION_ENABLED', true);
define('SESSION_LIFETIME', 3600); // 1 hour

// Export settings
define('EXPORT_ENABLED', true);
define('EXPORT_FORMATS', ['txt', 'csv', 'json']);

// Card brand patterns
$CARD_BRANDS = [
    'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
    'mastercard' => '/^5[1-5][0-9]{14}$/',
    'amex' => '/^3[47][0-9]{13}$/',
    'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
    'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
    'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
    'unionpay' => '/^(62|88)\d{14,17}$/',
    'maestro' => '/^(5018|5020|5038|6304|6759|6761|6763)[0-9]{8,15}$/'
];

// Security headers
define('ENABLE_SECURITY_HEADERS', true);
define('ALLOWED_ORIGINS', ['*']); // Configure for production

// Branding
define('APP_NAME', 'MASS CC Checker');
define('APP_VERSION', '2.0');
define('APP_AUTHOR', 'OshekharO');

?>
