<?php
/**
 * Configuration file for CC Checker
 * Enhanced with robust bank algorithm validation settings
 * 
 * @author OshekharO
 */

// ============================================
// Card Validation Settings
// ============================================

// Enable Luhn algorithm validation (ISO/IEC 7812-1)
define('ENABLE_LUHN_CHECK', true);

// Card number length constraints
define('MIN_CARD_LENGTH', 13);  // Minimum: 13 digits (some older Visa cards)
define('MAX_CARD_LENGTH', 19);  // Maximum: 19 digits (some UnionPay cards)

// ============================================
// Expiry Validation
// ============================================

// Cards must be valid from this year onwards
define('MIN_VALID_YEAR', 2026);

// ============================================
// Supported Card Types (IIN/BIN Ranges)
// ============================================
// The following card types are automatically detected:
// - Visa (4xxx)
// - Mastercard (51xx-55xx, 222100-272099)
// - American Express (34xx, 37xx)
// - Discover (6011, 644-649, 65)
// - Diners Club (300-305, 36, 38)
// - JCB (2131, 1800, 35)
// - UnionPay (62)
// - Maestro (5018, 5020, 5038, 5893, 6304, 6759, 6761-6763)

?>
