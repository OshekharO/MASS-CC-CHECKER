<?php
/**
 * API Endpoint for CC Checker
 * Reworked: comprehensive BIN detection, structured JSON, additional networks
 *
 * @author OshekharO
 */

if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    define('ENABLE_LUHN_CHECK', true);
    define('MIN_CARD_LENGTH', 13);
    define('MAX_CARD_LENGTH', 19);
    define('MIN_VALID_YEAR', 2024);
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
 */
function validateLuhn(string $number): bool
{
    $number = preg_replace('/\D/', '', $number);
    if ($number === '') {
        return false;
    }

    $sum    = 0;
    $length = strlen($number);

    for ($i = $length - 1; $i >= 0; $i--) {
        $digit = (int) $number[$i];
        if (($length - $i) % 2 === 0) {
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

    if ($monthNum < 1 || $monthNum > 12) {
        return ['valid' => false, 'message' => 'Invalid month (01-12)'];
    }

    if ($yearNum === null) {
        return ['valid' => false, 'message' => 'Invalid year format'];
    }

    if ($yearNum < $currentYear || ($yearNum === $currentYear && $monthNum < $currentMonth)) {
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
    }

    return [
        'valid'     => empty($errors),
        'card_type' => $cardType,
        'errors'    => $errors,
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

$pattern = '/^([\d]{' . MIN_CARD_LENGTH . ',' . MAX_CARD_LENGTH . '})\|([\d]{2})\|([\d]{2}|[\d]{4})\|([\d]{3,4})$/';

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
        'message' => $errorMsg,
        'msg'     => "<div><b style='color:#ef4444;'>Die</b> | {$format} | {$errorMsg}</div>",
    ]);
    exit;
}

// Simulate payment-gateway response.
// Replace this block with real gateway integration.
$rand = rand(1, 10);

if ($rand <= 3) {
    echo json_encode([
        'error'   => 1,
        'status'  => 'live',
        'network' => $cardTypeName,
        'color'   => $cardColor,
        'key'     => $cardKey,
        'card'    => $format,
        'message' => '$0.5 Auth',
        'msg'     => "<div><b style='color:#10b981;'>Live</b> | {$format} | \$0.5 Auth</div>",
    ]);
} elseif ($rand <= 8) {
    echo json_encode([
        'error'   => 2,
        'status'  => 'die',
        'network' => $cardTypeName,
        'color'   => $cardColor,
        'key'     => $cardKey,
        'card'    => $format,
        'message' => 'Declined',
        'msg'     => "<div><b style='color:#ef4444;'>Die</b> | {$format} | Declined</div>",
    ]);
} else {
    echo json_encode([
        'error'   => 3,
        'status'  => 'unknown',
        'network' => $cardTypeName,
        'color'   => $cardColor,
        'key'     => $cardKey,
        'card'    => $format,
        'message' => 'Gateway timeout',
        'msg'     => "<div><b style='color:#f59e0b;'>Unknown</b> | {$format} | Gateway timeout</div>",
    ]);
}
?>
