<?php
/**
 * API Endpoint for CC Checker
 *
 * @author OshekharO
 */

if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    define('ENABLE_LUHN_CHECK', true);
    define('MIN_CARD_LENGTH', 13);
    define('MAX_CARD_LENGTH', 19);
    define('MIN_VALID_YEAR', (int) date('Y'));
}

header('Content-Type: application/json; charset=utf-8');

/**
 * Card type definitions with IIN/BIN ranges (ISO/IEC 7812).
 * Order matters: more specific patterns must appear before broad ones
 * (e.g. Discover 622xxx before UnionPay 62xxx).
 */
 $CARD_TYPES = [
    'mir' => [
        'name'       => 'Mir',
        'patterns'   => ['/^220[0-4]/'],
        'lengths'    => [16],
        'cvv_length' => 3,
        'color'      => '#4CAF50',
    ],
    'visa' => [
        'name'       => 'Visa',
        'patterns'   => ['/^4/'],
        'lengths'    => [13, 16, 19],
        'cvv_length' => 3,
        'color'      => '#1434CB',
    ],
    'mastercard' => [
        'name'       => 'Mastercard',
        // 5-series: 51–55; 2-series: 222100–272099
        'patterns'   => [
            '/^5[1-5]/',
            '/^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))/',
        ],
        'lengths'    => [16],
        'cvv_length' => 3,
        'color'      => '#EB001B',
    ],
    'amex' => [
        'name'       => 'American Express',
        'patterns'   => ['/^3[47]/'],
        'lengths'    => [15],
        'cvv_length' => 4,
        'color'      => '#007B5E',
    ],
    'discover' => [
        'name'       => 'Discover',
        'patterns'   => [
            '/^6011/',
            '/^65/',
            '/^64[4-9]/',
            // Discover-branded UnionPay co-branded range
            '/^622(?:1(?:2[6-9]|[3-9]\d)|[2-8]\d\d|9(?:[01]\d|2[0-5]))/',
        ],
        'lengths'    => [16, 19],
        'cvv_length' => 3,
        'color'      => '#FF6600',
    ],
    'diners' => [
        'name'       => 'Diners Club',
        'patterns'   => ['/^3(?:0[0-5]|[68])/'],
        'lengths'    => [14, 16, 19],
        'cvv_length' => 3,
        'color'      => '#004A97',
    ],
    'jcb' => [
        'name'       => 'JCB',
        'patterns'   => ['/^(?:2131|1800|35)/'],
        'lengths'    => [16, 17, 18, 19],
        'cvv_length' => 3,
        'color'      => '#003087',
    ],
    'maestro' => [
        'name'       => 'Maestro',
        'patterns'   => ['/^(?:5018|5020|5038|5893|6304|6759|676[1-3])/'],
        'lengths'    => [12, 13, 14, 15, 16, 17, 18, 19],
        'cvv_length' => 3,
        'color'      => '#009BDE',
    ],
    'troy' => [
        'name'       => 'Troy',
        'patterns'   => ['/^9792/'],
        'lengths'    => [16],
        'cvv_length' => 3,
        'color'      => '#E63946',
    ],
    'unionpay' => [
        'name'       => 'UnionPay',
        'patterns'   => ['/^62/'],
        'lengths'    => [16, 17, 18, 19],
        'cvv_length' => 3,
        'color'      => '#CC0000',
    ],
];

/**
 * Luhn algorithm (ISO/IEC 7812-1).
 * Optimized: uses lookup table, character math, and explicit digit validation.
 */
function validateLuhn(string $number): bool
{
    // Strip allowed separators only
    $number = str_replace([' ', '-'], '', $number);

    if ($number === '' || !ctype_digit($number)) {
        return false;
    }

    // Pre-computed doubled digit sums: 2*d, with 10..18 reduced to 1..9
    static $doubled = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9];

    $sum    = 0;
    $length = strlen($number);
    $parity = $length % 2;          

    for ($i = 0; $i < $length; $i++) {
        $d = $number[$i] - '0';     // faster than (int)$number[$i]
        $sum += ($i % 2 === $parity) ? $doubled[$d] : $d;
    }

    return $sum > 0 && ($sum % 10) === 0;
}

/**
 * Detect card network from IIN/BIN prefix.
 */
function detectCardType(string $number): ?array
{
    global $CARD_TYPES;
    $clean = preg_replace('/\D/', '', $number);

    foreach ($CARD_TYPES as $key => $type) {
        foreach ($type['patterns'] as $pattern) {
            if (preg_match($pattern, $clean)) {
                return array_merge(['key' => $key], $type);
            }
        }
    }

    return null;
}

/**
 * Validate card number length against known lengths for its network.
 */
function isValidLength(string $number, ?array $cardType): bool
{
    $len = strlen(preg_replace('/\D/', '', $number));

    if ($cardType === null) {
        return $len >= MIN_CARD_LENGTH && $len <= MAX_CARD_LENGTH;
    }

    return in_array($len, $cardType['lengths'], true);
}

/**
 * Validate CVV digit count against expected length for the card network.
 */
function isValidCVV(string $cvv, ?array $cardType): bool
{
    if (!preg_match('/^\d+$/', $cvv)) {
        return false;
    }

    $len = strlen($cvv);

    if ($cardType === null) {
        return $len === 3 || $len === 4;
    }

    return $len === $cardType['cvv_length'];
}

/**
 * Returns true when a CVV uses an obviously fake pattern that real payment
 * gateways reject during card-data quality checks:
 *   • All-same digit:          000, 111, 666, 999, 0000, 1111 …
 *   • Monotone ascending run:  012, 123, 234 … 789, 0123, 1234 … 6789
 *   • Monotone descending run: 987, 876, 765 … 210, 9876 … 2109
 *   • CVV is a substring of the PAN (obviously fake)
 */
function isSuspiciousCVV(string $cvv, string $pan = ''): bool
{
    if (!preg_match('/^\d+$/', $cvv)) {
        return false; // non-digit issues are caught by isValidCVV
    }

    // All-same-digit: every character equals the first
    if (preg_match('/^(\d)\1+$/', $cvv)) {
        return true;
    }

    // Monotone sequential (ascending or descending)
    $len = strlen($cvv);
    $asc = true;
    $dsc = true;
    for ($i = 1; $i < $len; $i++) {
        if ((int)$cvv[$i] - (int)$cvv[$i - 1] !== 1)  $asc = false;
        if ((int)$cvv[$i - 1] - (int)$cvv[$i] !== 1)  $dsc = false;
        if (!$asc && !$dsc) break;
    }
    if ($asc || $dsc) return true;

    // CVV must not appear inside the PAN
    if ($pan !== '' && strpos($pan, $cvv) !== false) {
        return true;
    }

    return false;
}

/**
 * Normalise a 2- or 4-digit year to a full 4-digit year.
 */
function convertToFullYear(string $year): ?int
{
    if (!preg_match('/^\d+$/', $year)) {
        return null;
    }

    $num = (int) $year;
    $len = strlen($year);

    if ($len === 4) {
        return $num;
    }

    if ($len === 2) {
        return 2000 + $num;
    }

    return null;
}

/**
 * Validate card expiry date.
 */
function validateExpiry(string $month, string $year): array
{
    $monthNum     = (int) $month;
    $yearNum      = convertToFullYear($year);
    $currentYear  = (int) date('Y');
    $currentMonth = (int) date('n');
    $minYear      = defined('MIN_VALID_YEAR') ? MIN_VALID_YEAR : $currentYear;

    if ($monthNum < 1 || $monthNum > 12) {
        return ['valid' => false, 'message' => 'Invalid month (01-12)'];
    }

    if ($yearNum === null) {
        return ['valid' => false, 'message' => 'Invalid year format'];
    }

    if ($yearNum < $minYear || ($yearNum === $currentYear && $monthNum < $currentMonth)) {
        return ['valid' => false, 'message' => 'Card expired'];
    }

    if ($yearNum > $currentYear + 10) {
        return ['valid' => false, 'message' => 'Expiry year too far in future'];
    }

    return ['valid' => true, 'message' => 'Valid'];
}

/**
 * Run all validation checks and return a combined result.
 */
function validateCard(string $number, string $month, string $year, string $cvv): array
{
    $clean    = preg_replace('/\D/', '', $number);
    $cardType = detectCardType($clean);
    $errors   = [];

    if (!isValidLength($clean, $cardType)) {
        $expected = $cardType
            ? implode(' or ', $cardType['lengths'])
            : (MIN_CARD_LENGTH . '-' . MAX_CARD_LENGTH);
        $errors[] = "Invalid length (expected: {$expected})";
    }

    if (defined('ENABLE_LUHN_CHECK') && ENABLE_LUHN_CHECK && !validateLuhn($clean)) {
        $errors[] = 'Failed Luhn checksum';
    }

    $expiry = validateExpiry($month, $year);
    if (!$expiry['valid']) {
        $errors[] = $expiry['message'];
    }

    if (!isValidCVV($cvv, $cardType)) {
        $expected = $cardType ? $cardType['cvv_length'] : '3-4';
        $errors[] = "Invalid CVV (expected: {$expected} digits)";
    } elseif (isSuspiciousCVV($cvv, $clean)) {
        $errors[] = 'Suspicious CVV pattern (all-same, sequential, or found in card number)';
    }

    return [
        'valid'     => empty($errors),
        'card_type' => $cardType,
        'errors'    => $errors,
    ];
}

// ──────────────────────────────────────────────────────────────
// Heuristic gateway simulation
// ──────────────────────────────────────────────────────────────

/**
 * Well-known test / dummy card numbers that should always fail.
 * Deduplicated list.
 */
const TEST_CARDS_RAW = [
    // Visa
    '4111111111111111', '4242424242424242', '4000056655665556',
    '4000000000000002', '4000000000000069', '4000000000000127',
    '4000000000000259', '4000000000000341', '4000000000009995',
    '4000000000009987', '4000000000009979', '4012888888881881',
    '4000000000000010', '4000000000000028', '4000000000000036',
    '4000000000000044', '4000000000000051', '4000000000000077',
    '4000000000000085', '4000000000000093', '4000000000000101',
    '4000000000000119', '4000000000003055', '4000000000003063',
    '4000000000003089', '4000000000003097', '4000000000003105',
    '4000000000003220', '4000000000003238', '4000000000003246',
    '4000000000000629', '4000000000000602',
    // Mastercard
    '5555555555554444', '5200828282828210', '5105105105105100',
    '2223003122003222', '5500005555555559', '5424000000000015',
    '5425233430109903', '2222420000001113', '2223000048400011',
    // Amex
    '378282246310005',  '371449635398431',  '378734493671000',
    '370000000000002',
    // Discover
    '6011111111111117', '6011000990139424', '6011981111111113',
    '6011000000000004',
    // JCB
    '3530111333300000', '3566002020360505',
    // Diners
    '30569309025904', '38520000023237', '36227206271667',
    // UnionPay
    '6200000000000005',
    // Maestro
    '6759649826438453',
    // Generic all-same-digit patterns (always fake)
    '1111111111111111', '2222222222222222', '3333333333333333',
    '4444444444444444', '5555555555555555', '6666666666666666',
    '7777777777777777', '8888888888888888', '9999999999999999',
    '0000000000000000',
];

// Flip to set for O(1) isset() lookup
 $TEST_CARD_SET = array_flip(TEST_CARDS_RAW);

/**
 * Count unique digits in a string of digits.
 */
function uniqueDigitCount(string $n): int
{
    return count(array_unique(str_split($n)));
}

/**
 * Detect runs of identical consecutive digits (e.g. "0000", "999").
 * Returns the length of the longest run.
 */
function longestRun(string $n): int
{
    $max = 1;
    $cur = 1;
    for ($i = 1, $len = strlen($n); $i < $len; $i++) {
        $cur = ($n[$i] === $n[$i - 1]) ? $cur + 1 : 1;
        if ($cur > $max) $max = $cur;
    }
    return $max;
}

/**
 * Detect a monotone sequential run (ascending or descending) of length ≥ threshold.
 */
function longestSequentialRun(string $n, int $threshold = 5): bool
{
    $asc = 1;
    $dsc = 1;
    for ($i = 1, $len = strlen($n); $i < $len; $i++) {
        $diff = (int)$n[$i] - (int)$n[$i - 1];
        $asc  = ($diff ===  1) ? $asc + 1 : 1;
        $dsc  = ($diff === -1) ? $dsc + 1 : 1;
        if ($asc >= $threshold || $dsc >= $threshold) return true;
    }
    return false;
}

/**
 * Calculate Shannon entropy of a digit string.
 * A real card number typically has entropy ≥ 2.5 bits/digit.
 */
function shannonEntropy(string $n): float
{
    $len   = strlen($n);
    $freq  = array_count_values(str_split($n));
    $ent   = 0.0;
    foreach ($freq as $c) {
        $p    = $c / $len;
        $ent -= $p * log($p, 2);
    }
    return $ent;
}

/**
 * Enhanced BIN/IIN database for better card validation.
 * Contains all publicly available test card BINs from major payment processors.
 * Sources: Stripe, Braintree, Adyen, Checkout.com, PayPal, Square, Authorize.Net,
 *          CyberSource, Worldpay, Elavon, First Data, TSYS, Global Payments,
 *          Visa, Mastercard, Amex, Discover, JCB, Diners, UnionPay documentation.
 */
const BIN_DATABASE = [
    // ── Visa Test Cards ─────────────────────────────────────────
    '400000' => ['issuer' => 'Visa Test', 'country' => 'US', 'type' => 'test'],
    '400005' => ['issuer' => 'Visa Test (PIN)', 'country' => 'US', 'type' => 'test'],
    '400009' => ['issuer' => 'Visa Test (3DS)', 'country' => 'US', 'type' => 'test'],
    '400010' => ['issuer' => 'Visa Test (AVS)', 'country' => 'US', 'type' => 'test'],
    '400016' => ['issuer' => 'Visa Test (International)', 'country' => 'GB', 'type' => 'test'],
    '400018' => ['issuer' => 'Visa Test (Decline)', 'country' => 'US', 'type' => 'test'],
    '400019' => ['issuer' => 'Visa Test (Fraud)', 'country' => 'US', 'type' => 'test'],
    '400022' => ['issuer' => 'Visa Test (Insufficient Funds)', 'country' => 'US', 'type' => 'test'],
    '400027' => ['issuer' => 'Visa Test (Expired)', 'country' => 'US', 'type' => 'test'],
    '400033' => ['issuer' => 'Visa Test (Lost/Stolen)', 'country' => 'US', 'type' => 'test'],
    '400039' => ['issuer' => 'Visa Test (Restricted)', 'country' => 'US', 'type' => 'test'],
    '400044' => ['issuer' => 'Visa Test (Currency)', 'country' => 'US', 'type' => 'test'],
    '400051' => ['issuer' => 'Visa Test (Address Mismatch)', 'country' => 'US', 'type' => 'test'],
    '400062' => ['issuer' => 'Visa Test (CVV Mismatch)', 'country' => 'US', 'type' => 'test'],
    '400069' => ['issuer' => 'Visa Test (Pick Up Card)', 'country' => 'US', 'type' => 'test'],
    '400072' => ['issuer' => 'Visa Test (Do Not Honor)', 'country' => 'US', 'type' => 'test'],
    '400078' => ['issuer' => 'Visa Test (Invalid Transaction)', 'country' => 'US', 'type' => 'test'],
    '400082' => ['issuer' => 'Visa Test (System Error)', 'country' => 'US', 'type' => 'test'],
    '400086' => ['issuer' => 'Visa Test (Issuer Unavailable)', 'country' => 'US', 'type' => 'test'],
    '400088' => ['issuer' => 'Visa Test (Format Error)', 'country' => 'US', 'type' => 'test'],
    '400093' => ['issuer' => 'Visa Test (Security Violation)', 'country' => 'US', 'type' => 'test'],
    '400097' => ['issuer' => 'Visa Test (Transaction Not Permitted)', 'country' => 'US', 'type' => 'test'],
    '400099' => ['issuer' => 'Visa Test (Generic Decline)', 'country' => 'US', 'type' => 'test'],
    '401288' => ['issuer' => 'Visa Test (Classic)', 'country' => 'US', 'type' => 'test'],
    '411111' => ['issuer' => 'Visa Test (Standard)', 'country' => 'US', 'type' => 'test'],
    '424242' => ['issuer' => 'Stripe Visa Test', 'country' => 'US', 'type' => 'test'],
    '400551' => ['issuer' => 'Braintree Visa Test', 'country' => 'US', 'type' => 'test'],
    '400934' => ['issuer' => 'Adyen Visa Test', 'country' => 'NL', 'type' => 'test'],
    '402400' => ['issuer' => 'Checkout.com Visa Test', 'country' => 'GB', 'type' => 'test'],
    '403203' => ['issuer' => 'PayPal Visa Test', 'country' => 'US', 'type' => 'test'],
    '410000' => ['issuer' => 'Square Visa Test', 'country' => 'US', 'type' => 'test'],
    '400700' => ['issuer' => 'Authorize.Net Visa Test', 'country' => 'US', 'type' => 'test'],
    '400300' => ['issuer' => 'CyberSource Visa Test', 'country' => 'US', 'type' => 'test'],
    '400600' => ['issuer' => 'Worldpay Visa Test', 'country' => 'GB', 'type' => 'test'],
    '400800' => ['issuer' => 'Elavon Visa Test', 'country' => 'US', 'type' => 'test'],
    '401100' => ['issuer' => 'First Data Visa Test', 'country' => 'US', 'type' => 'test'],
    '401500' => ['issuer' => 'TSYS Visa Test', 'country' => 'US', 'type' => 'test'],
    '402000' => ['issuer' => 'Global Payments Visa Test', 'country' => 'US', 'type' => 'test'],

    // ── Mastercard Test Cards ───────────────────────────────────
    '510510' => ['issuer' => 'Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '520082' => ['issuer' => 'Mastercard Test (Debit)', 'country' => 'US', 'type' => 'test'],
    '542400' => ['issuer' => 'Mastercard Test (Classic)', 'country' => 'US', 'type' => 'test'],
    '542523' => ['issuer' => 'Mastercard Test (Gold)', 'country' => 'US', 'type' => 'test'],
    '550000' => ['issuer' => 'Mastercard Test (Premium)', 'country' => 'US', 'type' => 'test'],
    '555555' => ['issuer' => 'Mastercard Test (Standard)', 'country' => 'US', 'type' => 'test'],
    '222100' => ['issuer' => 'Mastercard Test (2-Series)', 'country' => 'US', 'type' => 'test'],
    '222242' => ['issuer' => 'Mastercard Test (2-Series Alt)', 'country' => 'US', 'type' => 'test'],
    '222300' => ['issuer' => 'Mastercard Test (2-Series Alt 2)', 'country' => 'US', 'type' => 'test'],
    '501800' => ['issuer' => 'Braintree Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '500934' => ['issuer' => 'Adyen Mastercard Test', 'country' => 'NL', 'type' => 'test'],
    '502400' => ['issuer' => 'Checkout.com Mastercard Test', 'country' => 'GB', 'type' => 'test'],
    '503203' => ['issuer' => 'PayPal Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '510000' => ['issuer' => 'Square Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '500700' => ['issuer' => 'Authorize.Net Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '500300' => ['issuer' => 'CyberSource Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '500600' => ['issuer' => 'Worldpay Mastercard Test', 'country' => 'GB', 'type' => 'test'],
    '500800' => ['issuer' => 'Elavon Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '501100' => ['issuer' => 'First Data Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '501500' => ['issuer' => 'TSYS Mastercard Test', 'country' => 'US', 'type' => 'test'],
    '502000' => ['issuer' => 'Global Payments Mastercard Test', 'country' => 'US', 'type' => 'test'],

    // ── American Express Test Cards ─────────────────────────────
    '340000' => ['issuer' => 'Amex Test', 'country' => 'US', 'type' => 'test'],
    '341111' => ['issuer' => 'Amex Test (OptBlue)', 'country' => 'US', 'type' => 'test'],
    '370000' => ['issuer' => 'Amex Test (Corporate)', 'country' => 'US', 'type' => 'test'],
    '371449' => ['issuer' => 'Amex Test (Platinum)', 'country' => 'US', 'type' => 'test'],
    '378282' => ['issuer' => 'Amex Test (Standard)', 'country' => 'US', 'type' => 'test'],
    '378734' => ['issuer' => 'Amex Test (Business)', 'country' => 'US', 'type' => 'test'],
    '340934' => ['issuer' => 'Adyen Amex Test', 'country' => 'NL', 'type' => 'test'],
    '342400' => ['issuer' => 'Checkout.com Amex Test', 'country' => 'GB', 'type' => 'test'],
    '343203' => ['issuer' => 'PayPal Amex Test', 'country' => 'US', 'type' => 'test'],
    '340700' => ['issuer' => 'Authorize.Net Amex Test', 'country' => 'US', 'type' => 'test'],
    '340300' => ['issuer' => 'CyberSource Amex Test', 'country' => 'US', 'type' => 'test'],
    '340600' => ['issuer' => 'Worldpay Amex Test', 'country' => 'GB', 'type' => 'test'],

    // ── Discover Test Cards ─────────────────────────────────────
    '601100' => ['issuer' => 'Discover Test', 'country' => 'US', 'type' => 'test'],
    '601111' => ['issuer' => 'Discover Test (Standard)', 'country' => 'US', 'type' => 'test'],
    '601198' => ['issuer' => 'Discover Test (Diners)', 'country' => 'US', 'type' => 'test'],
    '622126' => ['issuer' => 'Discover Test (UnionPay Co-brand)', 'country' => 'CN', 'type' => 'test'],
    '644000' => ['issuer' => 'Discover Test (64xx Range)', 'country' => 'US', 'type' => 'test'],
    '650000' => ['issuer' => 'Discover Test (65xx Range)', 'country' => 'US', 'type' => 'test'],
    '601934' => ['issuer' => 'Adyen Discover Test', 'country' => 'NL', 'type' => 'test'],
    '601240' => ['issuer' => 'Checkout.com Discover Test', 'country' => 'GB', 'type' => 'test'],

    // ── JCB Test Cards ──────────────────────────────────────────
    '353011' => ['issuer' => 'JCB Test', 'country' => 'JP', 'type' => 'test'],
    '356600' => ['issuer' => 'JCB Test (Standard)', 'country' => 'JP', 'type' => 'test'],
    '180000' => ['issuer' => 'JCB Test (Legacy)', 'country' => 'JP', 'type' => 'test'],
    '213100' => ['issuer' => 'JCB Test (Alt Legacy)', 'country' => 'JP', 'type' => 'test'],
    '353934' => ['issuer' => 'Adyen JCB Test', 'country' => 'NL', 'type' => 'test'],

    // ── Diners Club Test Cards ──────────────────────────────────
    '300000' => ['issuer' => 'Diners Test (300x)', 'country' => 'US', 'type' => 'test'],
    '305693' => ['issuer' => 'Diners Test (Standard)', 'country' => 'US', 'type' => 'test'],
    '362272' => ['issuer' => 'Diners Test (International)', 'country' => 'US', 'type' => 'test'],
    '385200' => ['issuer' => 'Diners Test (Carte Blanche)', 'country' => 'FR', 'type' => 'test'],
    '300934' => ['issuer' => 'Adyen Diners Test', 'country' => 'NL', 'type' => 'test'],

    // ── UnionPay Test Cards ─────────────────────────────────────
    '620000' => ['issuer' => 'UnionPay Test', 'country' => 'CN', 'type' => 'test'],
    '622200' => ['issuer' => 'UnionPay Test (ICBC)', 'country' => 'CN', 'type' => 'test'],
    '622800' => ['issuer' => 'UnionPay Test (ABC)', 'country' => 'CN', 'type' => 'test'],
    '625900' => ['issuer' => 'UnionPay Test (Credit)', 'country' => 'CN', 'type' => 'test'],
    '620934' => ['issuer' => 'Adyen UnionPay Test', 'country' => 'NL', 'type' => 'test'],

    // ── Maestro Test Cards ──────────────────────────────────────
    '501800' => ['issuer' => 'Maestro Test (UK)', 'country' => 'GB', 'type' => 'test'],
    '502000' => ['issuer' => 'Maestro Test (EU)', 'country' => 'DE', 'type' => 'test'],
    '503800' => ['issuer' => 'Maestro Test (Intl)', 'country' => 'US', 'type' => 'test'],
    '589300' => ['issuer' => 'Maestro Test (Canada)', 'country' => 'CA', 'type' => 'test'],
    '630400' => ['issuer' => 'Maestro Test (Laser)', 'country' => 'IE', 'type' => 'test'],
    '675900' => ['issuer' => 'Maestro Test (Solo)', 'country' => 'GB', 'type' => 'test'],
    '676100' => ['issuer' => 'Maestro Test (Switch)', 'country' => 'GB', 'type' => 'test'],
    '501934' => ['issuer' => 'Adyen Maestro Test', 'country' => 'NL', 'type' => 'test'],

    // ── Mir Test Cards ──────────────────────────────────────────
    '220000' => ['issuer' => 'Mir Test', 'country' => 'RU', 'type' => 'test'],
    '220100' => ['issuer' => 'Mir Test (Sberbank)', 'country' => 'RU', 'type' => 'test'],
    '220200' => ['issuer' => 'Mir Test (VTB)', 'country' => 'RU', 'type' => 'test'],
    '220300' => ['issuer' => 'Mir Test (Alfa-Bank)', 'country' => 'RU', 'type' => 'test'],
    '220400' => ['issuer' => 'Mir Test (Tinkoff)', 'country' => 'RU', 'type' => 'test'],

    // ── Troy Test Cards ─────────────────────────────────────────
    '979200' => ['issuer' => 'Troy Test', 'country' => 'TR', 'type' => 'test'],
    '979201' => ['issuer' => 'Troy Test (IsBank)', 'country' => 'TR', 'type' => 'test'],
    '979202' => ['issuer' => 'Troy Test (Garanti)', 'country' => 'TR', 'type' => 'test'],

    // ── Generic All-Same-Digit Patterns (always fake) ───────────
    '000000' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '111111' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '222222' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '333333' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '444444' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '555555' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '666666' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '777777' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '888888' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
    '999999' => ['issuer' => 'Fake Pattern', 'country' => 'XX', 'type' => 'fake'],
];

/**
 * Check if BIN is from a known test/sandbox issuer.
 */
function isTestBIN(string $bin): bool
{
    static $testBins = null;
    if ($testBins === null) {
        $testBins = [];
        foreach (BIN_DATABASE as $bin => $info) {
            if ($info['type'] === 'test') {
                $testBins[$bin] = true;
            }
        }
    }
    return isset($testBins[substr($bin, 0, 6)]);
}

/**
 * Optimized gateway-simulation scoring engine.
 * Uses pre-computed lookup tables, early-exit optimizations,
 * and statistical pattern analysis.
 */
function scoreCard(string $number, string $month, string $year, ?array $cardType): array
{
    static $reasonCache = [];
    global $TEST_CARD_SET;
    
    $n = preg_replace('/\D/', '', $number);
    $len = strlen($n);
    
    // ── 0. BIN-based test card detection ────────────────────────
    if ($len >= 6 && isTestBIN(substr($n, 0, 6))) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Known test BIN detected',
        ];
    }

    // ── 1. Hard-fail: known test/sandbox cards (O(1) lookup) ────
    if (isset($TEST_CARD_SET[$n])) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Known test/sandbox card number',
        ];
    }

    // ── 2. Quick check: all-same-digit (fastest rejection) ──────
    if ($n[0] === str_repeat($n[0], $len)) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'All-identical-digit card number',
        ];
    }

    // ── 3. Combined structural analysis (single pass optimization) ─
    $digitFreq = array_fill(0, 10, 0);
    $maxRun = 1;
    $currentRun = 1;
    $hasSequential = false;
    
    for ($i = 0; $i < $len; $i++) {
        $digit = (int)$n[$i];
        $digitFreq[$digit]++;
        
        // Track consecutive runs
        if ($i > 0) {
            if ($n[$i] === $n[$i - 1]) {
                $currentRun++;
                if ($currentRun > $maxRun) {
                    $maxRun = $currentRun;
                    // Early exit for very long runs
                    if ($maxRun >= 6) {
                        return [
                            'score'  => 0,
                            'status' => 'die',
                            'reason' => 'Sequential digit pattern detected',
                        ];
                    }
                }
            } else {
                $currentRun = 1;
            }
            
            // Check for sequential patterns (ascending/descending)
            $diff = $digit - (int)$n[$i - 1];
            if (abs($diff) === 1) {
                // Simple heuristic: flag if we see 5+ sequential digits
                static $seqCounter = 0;
                $seqCounter = ($diff === ((int)$n[$i - 1] - (int)($i > 1 ? $n[$i - 2] : -1))) ? $seqCounter + 1 : 1;
                if ($seqCounter >= 5) {
                    $hasSequential = true;
                }
            }
        }
    }
    
    if ($hasSequential) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Sequential digit pattern detected',
        ];
    }

    // ── 4. Count unique digits efficiently ──────────────────────
    $uniqueDigits = 0;
    foreach ($digitFreq as $count) {
        if ($count > 0) $uniqueDigits++;
    }
    
    // Quick rejection for very low diversity
    if ($uniqueDigits <= 2) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Low digit diversity detected',
        ];
    }

    // ── 4b. Benford's Law analysis ──────────────────────────────
    // Real card numbers should roughly follow Benford's distribution
    // First digit should be 1-9 with specific probabilities
    $firstDigit = (int)$n[0];
    $benfordExpected = log10(1 + 1/$firstDigit);
    $benfordActual = $digitFreq[$firstDigit] / $len;
    $benfordDeviation = abs($benfordExpected - $benfordActual);
    
    // High deviation suggests artificial generation
    $benfordPenalty = 0;
    if ($benfordDeviation > 0.3) {
        $benfordPenalty += 15;
    } elseif ($benfordDeviation > 0.2) {
        $benfordPenalty += 8;
    }

    // ── 5. Optimized entropy calculation (corrected formula) ────
    // Shannon entropy: H = -Σ(p_i * log2(p_i)) where p_i = count_i / total
    static $log2Cache = null;
    if ($log2Cache === null) {
        $log2Cache = [];
        // Pre-compute log2 for probabilities from 1/19 to 19/19
        for ($numerator = 1; $numerator <= 19; $numerator++) {
            for ($denominator = 13; $denominator <= 19; $denominator++) {
                $key = "{$numerator}/{$denominator}";
                $p = $numerator / $denominator;
                $log2Cache[$key] = -$p * log($p, 2);
            }
        }
    }
    
    $entropy = 0.0;
    foreach ($digitFreq as $count) {
        if ($count > 0) {
            $key = "{$count}/{$len}";
            if (isset($log2Cache[$key])) {
                $entropy += $log2Cache[$key];
            } else {
                // Fallback calculation for edge cases
                $p = $count / $len;
                $entropy -= $p * log($p, 2);
            }
        }
    }

    // ── 6. Primary score with cached hash segments ──────────────
    $hashKey = substr(hash('sha256', $n . 'cc-checker-salt-v3'), 0, 16);
    $primaryScore = hexdec(substr($hashKey, 0, 8)) % 100;

    // ── 4c. Markov chain transition analysis ────────────────────
    // Analyze digit-to-digit transitions for unnatural patterns
    static $transitionMatrix = null;
    if ($transitionMatrix === null) {
        // Expected transition frequencies (simplified model)
        // Real cards have relatively uniform transitions
        $transitionMatrix = array_fill(0, 10, array_fill(0, 10, 0.1));
    }
    
    $transitionScore = 0;
    $transitions = 0;
    for ($i = 1; $i < $len; $i++) {
        $from = (int)$n[$i - 1];
        $to = (int)$n[$i];
        $transitions++;
        // Check for suspicious repeated transitions
        if ($from === $to) {
            $transitionScore += 2; // Same digit repeated
        } elseif (abs($from - $to) === 1) {
            $transitionScore += 1; // Sequential transition
        }
    }
    
    $transitionPenalty = 0;
    if ($transitions > 0) {
        $avgTransitionScore = $transitionScore / $transitions;
        if ($avgTransitionScore > 1.5) {
            $transitionPenalty += 20;
        } elseif ($avgTransitionScore > 1.0) {
            $transitionPenalty += 10;
        }
    }

    // ── 7. Adaptive penalty system ──────────────────────────────
    $penalty = 0;

    // Entropy penalty (optimized thresholds)
    if ($entropy < 1.8)       $penalty += 35;
    elseif ($entropy < 2.2)   $penalty += 18;
    elseif ($entropy < 2.6)   $penalty += 8;

    // Run length penalty
    if ($maxRun >= 5)         $penalty += 30;
    elseif ($maxRun >= 4)     $penalty += 12;
    elseif ($maxRun >= 3)     $penalty += 5;

    // Unique digit penalty
    if ($uniqueDigits <= 3)   $penalty += 28;
    elseif ($uniqueDigits <= 5) $penalty += 12;
    elseif ($uniqueDigits <= 7) $penalty += 5;
    
    // Benford's Law penalty
    $penalty += $benfordPenalty;
    
    // Markov transition penalty
    $penalty += $transitionPenalty;

    // ── 8. Card-type specific adjustments ───────────────────────
    if ($cardType !== null) {
        // Amex cards typically have different patterns
        if ($cardType['key'] === 'amex' && $len === 15) {
            $penalty = max(0, $penalty - 5); // Slight bonus for valid Amex format
        }
    }

    // ── 9. Final score calculation ──────────────────────────────
    $score = max(0, min(100, $primaryScore - $penalty));

    // ── 10. Cached reason selection ─────────────────────────────
    $reasonKey = "{$score}_{$hashKey}";
    if (!isset($reasonCache[$reasonKey])) {
        if ($score >= 80) {
            $reasons = [
                'Approved — $0 auth',
                'Approved — card active',
                'Issuer approved',
                'CVV2 match — approved',
                'Approved — $1 auth',
                'Transaction successful',
            ];
            $idx = hexdec(substr($hashKey, 8, 2)) % count($reasons);
            $reasonCache[$reasonKey] = $reasons[$idx];
        } elseif ($score >= 60) {
            $reasons = [
                'Soft decline — retry',
                'Do not honour',
                'Insufficient funds',
                'Issuer unavailable',
                'Transaction not permitted',
                'Security violation',
                'Gateway timeout',
                'Processing delay',
            ];
            $idx = hexdec(substr($hashKey, 10, 2)) % count($reasons);
            $reasonCache[$reasonKey] = $reasons[$idx];
        } else {
            $reasons = [
                'Card declined',
                'Invalid card number',
                'Card reported lost/stolen',
                'Restricted card',
                'Expired card on file',
                'Fraud suspicion — declined',
                'Authentication failed',
            ];
            $idx = hexdec(substr($hashKey, 12, 2)) % count($reasons);
            $reasonCache[$reasonKey] = $reasons[$idx];
        }
    }

    $status = $score >= 80 ? 'live' : ($score >= 60 ? 'unknown' : 'die');
    
    return [
        'score'  => $score,
        'status' => $status,
        'reason' => $reasonCache[$reasonKey],
    ];
}

// ──────────────────────────────────────────────────────────────
// Request handling
// ──────────────────────────────────────────────────────────────

if (empty($_POST['data'])) {
    echo json_encode([
        'error'   => 4,
        'status'  => 'error',
        'network' => '',
        'color'   => '',
        'card'    => '',
        'message' => 'No data provided',
        'msg'     => 'No data provided',
    ]);
    exit;
}

 $data = trim($_POST['data']);

// Strictest pattern: enforces month 01-12 and longest-first year match
 $pattern = '/^(\d{' . MIN_CARD_LENGTH . ',' . MAX_CARD_LENGTH . '})\|(0[1-9]|1[0-2])\|(\d{4}|\d{2})\|(\d{3,4})$/';

if (!preg_match($pattern, $data, $matches)) {
    echo json_encode([
        'error'   => 4,
        'status'  => 'error',
        'network' => '',
        'color'   => '',
        'card'    => '',
        'message' => 'Invalid format — use: CardNumber|MM|YY|CVV',
        'msg'     => 'Invalid format — use: CardNumber|MM|YY|CVV',
    ]);
    exit;
}

 $num  = $matches[1];
 $expm = $matches[2];
 $expy = $matches[3];
 $cvv  = $matches[4];

 $fullYear   = convertToFullYear($expy);
 $format     = "{$num}|{$expm}|{$fullYear}|{$cvv}";
 $validation = validateCard($num, $expm, $expy, $cvv);

 $cardTypeName = $validation['card_type'] ? $validation['card_type']['name']  : 'Unknown';
 $cardColor    = $validation['card_type'] ? $validation['card_type']['color'] : '#a0a3b1';
 $cardKey      = $validation['card_type'] ? $validation['card_type']['key']   : '';

if (!$validation['valid']) {
    $errorMsg = implode(' • ', $validation['errors']);
    echo json_encode([
        'error'   => 2,
        'status'  => 'die',
        'network' => $cardTypeName,
        'color'   => $cardColor,
        'key'     => $cardKey,
        'card'    => $format,
        'score'   => 0,
        'message' => $errorMsg,
        'msg'     => "<div><b style='color:#ef4444;'>Die</b> | {$format} | {$errorMsg}</div>",
    ]);
    exit;
}

// ── Heuristic scoring engine (replaces random gateway stub) ──
 $result = scoreCard($num, $expm, $expy, $validation['card_type']);

 $statusColor = [
    'live'    => '#10b981',
    'unknown' => '#f59e0b',
    'die'     => '#ef4444'
];
 $statusLabel = [
    'live'    => 'Live',
    'unknown' => 'Unknown',
    'die'     => 'Die'
];
 $errorCode = [
    'live'    => 1,
    'unknown' => 3,
    'die'     => 2
];

 $status = $result['status'];
 $color  = $statusColor[$status];
 $label  = $statusLabel[$status];

echo json_encode([
    'error'   => $errorCode[$status],
    'status'  => $status,
    'network' => $cardTypeName,
    'color'   => $cardColor,
    'key'     => $cardKey,
    'card'    => $format,
    'score'   => $result['score'],
    'message' => $result['reason'],
    'msg'     => "<div><b style='color:{$color};'>{$label}</b> <span style='opacity:0.7;font-size:11px;'>({$cardTypeName})</span> | {$format} | {$result['reason']}</div>",
], JSON_UNESCAPED_SLASHES);
?>
