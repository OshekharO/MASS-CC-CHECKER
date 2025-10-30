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

?>
