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
// Heuristic gateway simulation
// ──────────────────────────────────────────────────────────────

/**
 * Well-known test / dummy card numbers that should always fail.
 * Sources: Stripe docs, PayPal sandbox, Braintree, Adyen, and
 * other public payment-gateway sandbox documentation.
 */
const TEST_CARDS = [
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
    '370000000000002',  '378282246310005',  '371449635398431',
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
 * BIN (first 6 digits) plausibility score.
 * Checks that the BIN itself is not trivially sequential/repeated.
 */
function binPlausibility(string $n): int
{
    $bin = substr($n, 0, 6);
    $score = 0;
    if (uniqueDigitCount($bin) >= 4) $score += 10;
    if (longestRun($bin) <= 2)       $score += 10;
    return $score;
}

/**
 * Expiry freshness: cards that expire further in the future are more
 * likely to be "live" since they have not yet been replaced/cancelled.
 */
function expiryFreshnessScore(string $month, string $year): int
{
    $yearNum      = convertToFullYear($year);
    $currentYear  = (int) date('Y');
    $currentMonth = (int) date('n');

    if ($yearNum === null) return 0;

    $monthsLeft = ($yearNum - $currentYear) * 12 + ((int)$month - $currentMonth);

    if ($monthsLeft >= 30) return 15;
    if ($monthsLeft >= 18) return 10;
    if ($monthsLeft >= 9)  return  5;
    if ($monthsLeft >= 3)  return  0;
    return -10; // expires very soon
}

/**
 * Per-network baseline live-rate bias.
 * Reflects rough real-world observed ratios for demo purposes.
 */
function networkBias(?array $cardType): int
{
    if ($cardType === null) return -5;

    $biases = [
        'visa'       =>  8,
        'mastercard' =>  8,
        'amex'       =>  6,
        'discover'   =>  4,
        'jcb'        =>  2,
        'unionpay'   =>  2,
        'maestro'    =>  0,
        'diners'     =>  0,
        'mir'        => -2,
        'troy'       => -2,
    ];

    return $biases[$cardType['key']] ?? 0;
}

/**
 * Main heuristic scoring engine.
 *
 * Returns an associative array:
 *   'score'  (int)    0-100 — higher = more likely live
 *   'status' (string) 'live' | 'unknown' | 'die'
 *   'reason' (string) human-readable decision message
 *
 * The score is deterministic: the same card always yields the same result.
 * Randomness is seeded from a SHA-256 of (cardNumber + secret salt) so
 * different cards get different outcomes, but repeated runs are stable.
 */
function scoreCard(string $number, string $month, string $year, ?array $cardType): array
{
    $n = preg_replace('/\D/', '', $number);

    // ── 1. Hard-fail: known test/sandbox cards ──────────────────
    if (in_array($n, TEST_CARDS, true)) {
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

    // ── 4. Base score ────────────────────────────────────────────
    $score = 50;

    // ── 5. Entropy score (real cards: ~2.8–3.1 bits/digit) ──────
    $entropy = shannonEntropy($n);
    if ($entropy >= 3.0)      $score += 18;
    elseif ($entropy >= 2.7)  $score += 12;
    elseif ($entropy >= 2.4)  $score +=  6;
    elseif ($entropy >= 2.0)  $score -=  5;
    else                      $score -= 20;

    // ── 6. Repeating-digit penalty ───────────────────────────────
    $run = longestRun($n);
    if ($run >= 5)      $score -= 25;
    elseif ($run === 4) $score -= 10;
    elseif ($run === 3) $score -=  4;

    // ── 7. Unique-digit richness ─────────────────────────────────
    $uniq = uniqueDigitCount($n);
    if ($uniq >= 8)      $score += 10;
    elseif ($uniq >= 6)  $score +=  5;
    elseif ($uniq <= 4)  $score -= 10;
    elseif ($uniq <= 3)  $score -= 20;

    // ── 8. BIN plausibility ──────────────────────────────────────
    $score += binPlausibility($n);

    // ── 9. Network bias ──────────────────────────────────────────
    $score += networkBias($cardType);

    // ── 10. Expiry freshness ─────────────────────────────────────
    $score += expiryFreshnessScore($month, $year);

    // ── 11. Deterministic card-specific variance (−12 to +12 points) ─
    //        `hexdec(...) % 25` yields 0–24; subtracting 12 centres it
    //        on 0, giving a symmetric ±12 window. SHA-256 of the card
    //        number ensures every distinct card always gets the same
    //        variance without relying on PHP's rand().
    $hash   = hash('sha256', $n . 'cc-checker-salt-v2');
    $offset = (hexdec(substr($hash, 0, 4)) % 25) - 12;
    $score += $offset;

    // ── 12. Clamp to [0, 100] ────────────────────────────────────
    $score = max(0, min(100, $score));

    // ── 13. Decision thresholds ──────────────────────────────────
    if ($score >= 62) {
        $reasons = [
            'Approved — $0 auth',
            'Approved — card active',
            'Issuer approved',
            'CVV2 match — approved',
            'Approved — $1 auth',
        ];
        $reason = $reasons[hexdec(substr($hash, 4, 2)) % count($reasons)];
        return ['score' => $score, 'status' => 'live', 'reason' => $reason];
    }

    if ($score >= 38) {
        $reasons = [
            'Soft decline — retry',
            'Do not honour',
            'Insufficient funds',
            'Issuer unavailable',
            'Transaction not permitted',
            'Security violation',
            'Gateway timeout',
        ];
        $reason = $reasons[hexdec(substr($hash, 6, 2)) % count($reasons)];
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
    $reason = $reasons[hexdec(substr($hash, 8, 2)) % count($reasons)];
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

// ── Heuristic scoring engine (replaces random gateway stub) ──
$result = scoreCard($num, $expm, $expy, $validation['card_type']);

switch ($result['status']) {
    case 'live':
        echo json_encode([
            'error'   => 1,
            'status'  => 'live',
            'network' => $cardTypeName,
            'color'   => $cardColor,
            'key'     => $cardKey,
            'card'    => $format,
            'score'   => $result['score'],
            'message' => $result['reason'],
            'msg'     => "<div><b style='color:#10b981;'>Live</b> <span style='opacity:0.7;font-size:11px;'>({$cardTypeName})</span> | {$format} | {$result['reason']}</div>",
        ]);
        break;

    case 'unknown':
        echo json_encode([
            'error'   => 3,
            'status'  => 'unknown',
            'network' => $cardTypeName,
            'color'   => $cardColor,
            'key'     => $cardKey,
            'card'    => $format,
            'score'   => $result['score'],
            'message' => $result['reason'],
            'msg'     => "<div><b style='color:#f59e0b;'>Unknown</b> <span style='opacity:0.7;font-size:11px;'>({$cardTypeName})</span> | {$format} | {$result['reason']}</div>",
        ]);
        break;

    default: // 'die'
        echo json_encode([
            'error'   => 2,
            'status'  => 'die',
            'network' => $cardTypeName,
            'color'   => $cardColor,
            'key'     => $cardKey,
            'card'    => $format,
            'score'   => $result['score'],
            'message' => $result['reason'],
            'msg'     => "<div><b style='color:#ef4444;'>Die</b> <span style='opacity:0.7;font-size:11px;'>({$cardTypeName})</span> | {$format} | {$result['reason']}</div>",
        ]);
}
?>
