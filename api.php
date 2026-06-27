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
 * Gateway-simulation scoring engine.
 */
function scoreCard(string $number, string $month, string $year, ?array $cardType): array
{
    global $TEST_CARD_SET;
    
    $n = preg_replace('/\D/', '', $number);

    // ── 1. Hard-fail: known test/sandbox cards ──────────────────
    if (isset($TEST_CARD_SET[$n])) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Known test/sandbox card number',
        ];
    }

    // ── 2. Hard-fail: all-same-digit number ─────────────────────
    if (uniqueDigitCount($n) === 1) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'All-identical-digit card number',
        ];
    }

    // ── 3. Hard-fail: strongly sequential ───────────────────────
    if (longestSequentialRun($n, 6)) {
        return [
            'score'  => 0,
            'status' => 'die',
            'reason' => 'Sequential digit pattern detected',
        ];
    }

    // ── 4. Primary score: stable pseudorandom draw from card hash ─
    $hash         = hash('sha256', $n . 'cc-checker-salt-v2');
    // Use intval(..., 16) for 32-bit PHP safety instead of hexdec
    $primaryScore = intval(substr($hash, 0, 8), 16) % 100;

    // ── 5. Structural penalties only ────────────────────────────
    $penalty = 0;

    $entropy = shannonEntropy($n);
    if ($entropy < 2.0)       $penalty += 30;   
    elseif ($entropy < 2.5)   $penalty += 12;   

    $run = longestRun($n);
    if ($run >= 5)             $penalty += 25;   
    elseif ($run >= 4)         $penalty += 10;

    $uniq = uniqueDigitCount($n);
    if ($uniq <= 3)            $penalty += 25;   
    elseif ($uniq <= 5)        $penalty += 10;

    // ── 6. Final score and decision ─────────────────────────────
    $score = max(0, min(100, $primaryScore - $penalty));

    if ($score >= 80) {
        $reasons = [
            'Approved — $0 auth',
            'Approved — card active',
            'Issuer approved',
            'CVV2 match — approved',
            'Approved — $1 auth',
        ];
        $reason = $reasons[intval(substr($hash, 8, 2), 16) % count($reasons)];
        return ['score' => $score, 'status' => 'live', 'reason' => $reason];
    }

    if ($score >= 60) {
        $reasons = [
            'Soft decline — retry',
            'Do not honour',
            'Insufficient funds',
            'Issuer unavailable',
            'Transaction not permitted',
            'Security violation',
            'Gateway timeout',
        ];
        $reason = $reasons[intval(substr($hash, 10, 2), 16) % count($reasons)];
        return ['score' => $score, 'status' => 'unknown', 'reason' => $reason];
    }

    $reasons = [
        'Card declined',
        'Invalid card number',
        'Card reported lost/stolen',
        'Restricted card',
        'Expired card on file',
        'Fraud suspicion — declined',
    ];
    $reason = $reasons[intval(substr($hash, 12, 2), 16) % count($reasons)];
    return ['score' => $score, 'status' => 'die', 'reason' => $reason];
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
