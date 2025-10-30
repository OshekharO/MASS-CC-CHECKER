<?php
/**
 * Configuration file for Credit Card Checker
 * 
 * This file contains customizable settings for the card validation system
 * 
 * @author OshekharO
 * @license MIT
 */

// Card number validation settings
define('CARD_MIN_LENGTH', 13);  // Minimum card number length (e.g., some Visa cards)
define('CARD_MAX_LENGTH', 19);  // Maximum card number length (e.g., some UnionPay cards)

// CVV validation settings
define('CVV_MIN_LENGTH', 3);    // Standard CVV length
define('CVV_MAX_LENGTH', 4);    // American Express CVV length

// Expiry validation settings
define('MIN_EXPIRY_YEAR', (int)date('Y'));  // Minimum valid expiry year (current year)
define('MAX_EXPIRY_MONTH', 12);             // Maximum month value

// Validation settings
define('ENABLE_LUHN_CHECK', true);      // Enable/disable Luhn algorithm validation
define('STRICT_FORMAT_CHECK', true);    // Enable/disable strict format checking

// Response messages
define('MSG_INVALID_FORMAT', 'Invalid card format');
define('MSG_INVALID_LUHN', 'Card number failed Luhn algorithm validation');
define('MSG_INVALID_EXPIRY', 'Invalid expiry date');
define('MSG_INVALID_CVV', 'Invalid CVV code');
define('MSG_EXPIRED_CARD', 'Card has expired');

// Processing settings
define('RATE_LIMIT_DELAY', 100);        // Delay between requests in milliseconds (frontend)

?>
