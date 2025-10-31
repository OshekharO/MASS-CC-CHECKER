<?php
/**
 * Configuration file for CC Checker
 * 
 * @author OshekharO
 */

// Card validation settings
define('ENABLE_LUHN_CHECK', true);          // Enable Luhn algorithm validation
define('MIN_CARD_LENGTH', 13);              // Minimum card number length
define('MAX_CARD_LENGTH', 19);              // Maximum card number length

// Expiry validation
define('MIN_VALID_YEAR', 2024);             // Cards must be valid from this year onwards

// Security settings
define('ENABLE_RATE_LIMITING', true);       // Enable rate limiting
define('MAX_REQUESTS_PER_MINUTE', 60);      // Maximum requests per minute per IP
define('RATE_LIMIT_DURATION', 60);          // Rate limit duration in seconds

// Processing settings
define('PROCESSING_DELAY_MS', 100);         // Delay between requests in milliseconds (frontend)

// Feature toggles
define('ENABLE_BIN_DETECTION', true);       // Enable BIN (Bank Identification Number) detection
define('ENABLE_CARD_BRAND_DETECTION', true); // Enable card brand detection

// Logging settings
define('ENABLE_ERROR_LOGGING', true);       // Enable error logging
define('LOG_FILE_PATH', 'logs/error.log');  // Path to log file

?>
