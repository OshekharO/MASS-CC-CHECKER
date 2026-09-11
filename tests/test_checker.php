<?php
/**
 * Test Suite for CC Checker
 * Tests all core validation functions, network detection, heuristics, and API responses.
 */

require_once 'config.php';

// Helper assertion functions
function assertTrue($condition, string $message): void {
    if (!$condition) {
        echo "❌ FAIL: {$message}\n";
        exit(1);
    }
    echo "✅ PASS: {$message}\n";
}

function assertEquals($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        $expStr = var_export($expected, true);
        $actStr = var_export($actual, true);
        echo "❌ FAIL: {$message} (Expected {$expStr}, got {$actStr})\n";
        exit(1);
    }
    echo "✅ PASS: {$message}\n";
}

// Subprocess runner for individual function execution to isolate static variable initialisation
function evalFunctionInSubprocess(string $code) {
    $script = '
        $_POST["data"] = "4242424242424242|12|2028|123";
        ob_start();
        $file = file_get_contents("api.php");
        $parts = explode("// Request handling", $file);
        eval("?>" . $parts[0]);
        ob_clean();
        ' . $code;
    $cmd = sprintf('php -r %s 2>&1', escapeshellarg($script));
    return shell_exec($cmd);
}

function callApi(string $data): array {
    $cmd = sprintf('php -r %s 2>&1', escapeshellarg('
        $_POST["data"] = ' . var_export($data, true) . ';
        ob_start();
        require "api.php";
        $out = ob_get_clean();
        echo $out;
    '));
    $output = shell_exec($cmd);
    $json = json_decode($output, true);
    if (!is_array($json)) {
        echo "❌ FAIL: Invalid JSON response from API: {$output}\n";
        exit(1);
    }
    return $json;
}

echo "===========================================\n";
echo " Running CC Checker Test Suite\n";
echo "===========================================\n\n";

// Section 1: Luhn Algorithm Validation
echo "--- Section 1: Luhn Algorithm Validation ---\n";
$luhnTests = [
    '4242424242424242' => true,   // Visa test card
    '4242424242424241' => false,  // Invalid checksum
    '5500000000000004' => true,   // Mastercard test card
    '378282246310005'  => true,   // Amex test card
    '6011000000000004' => true,   // Discover test card with valid checksum
    '1234567812345670' => true,   // Valid Luhn
    '1234567812345678' => false,  // Invalid Luhn
    '0000000000000'    => false,  // All zeros
    'abc123'           => false,  // Non-numeric
];

foreach ($luhnTests as $card => $expected) {
    $code = sprintf('echo validateLuhn(%s) ? "1" : "0";', var_export($card, true));
    $res = trim(evalFunctionInSubprocess($code)) === '1';
    assertEquals($expected, $res, "validateLuhn('{$card}') should be " . ($expected ? 'true' : 'false'));
}

// Section 2: Card Type Detection
echo "\n--- Section 2: Card Type Detection ---\n";
$cardTypeTests = [
    '4242424242424242' => 'visa',
    '5100000000000000' => 'mastercard',
    '2221000000000000' => 'mastercard',
    '340000000000000'  => 'amex',
    '370000000000000'  => 'amex',
    '6011000000000000' => 'discover',
    '6500000000000000' => 'discover',
    '30000000000000'   => 'diners',
    '36000000000000'   => 'diners',
    '3528000000000000' => 'jcb',
    '6200000000000000' => 'unionpay',
    '5018000000000000' => 'maestro',
    '2200000000000000' => 'mir',
    '9792000000000000' => 'troy',
    '9999000000000000' => null,
];

foreach ($cardTypeTests as $card => $expectedKey) {
    $code = sprintf('$t = detectCardType(%s); echo $t ? $t["key"] : "null";', var_export($card, true));
    $resKey = trim(evalFunctionInSubprocess($code));
    $resKey = ($resKey === 'null') ? null : $resKey;
    assertEquals($expectedKey, $resKey, "detectCardType('{$card}') should return " . ($expectedKey ?? 'null'));
}

// Section 3: CVV Validation
echo "\n--- Section 3: CVV Validation ---\n";
$cvvTests = [
    // [cvv, card_number, expected_valid_cvv, expected_suspicious]
    ['791', '4242424242424242', true, false],     // Non-sequential, non-substring CVV
    ['1234', '378282246310005', true, true],      // Sequential digits 1234
    ['111', '4242424242424242', true, true],     // All-same digits
    ['123', '4242424212324242', true, true],     // CVV substring of card number
    ['12', '4242424242424242', false, true],     // Too short for Visa, also sequential "12"
    ['12345', '4242424242424242', false, true], // Too long, also sequential "12345"
];

foreach ($cvvTests as [$cvv, $card, $expValid, $expSuspicious]) {
    $codeVal = sprintf('$type = detectCardType(%s); echo isValidCVV(%s, $type) ? "1" : "0";', var_export($card, true), var_export($cvv, true));
    $resVal = trim(evalFunctionInSubprocess($codeVal)) === '1';
    assertEquals($expValid, $resVal, "isValidCVV('{$cvv}') for card '{$card}'");

    $codeSusp = sprintf('echo isSuspiciousCVV(%s, %s) ? "1" : "0";', var_export($cvv, true), var_export($card, true));
    $resSusp = trim(evalFunctionInSubprocess($codeSusp)) === '1';
    assertEquals($expSuspicious, $resSusp, "isSuspiciousCVV('{$cvv}', '{$card}')");
}

// Section 4: Expiry Date Validation
echo "\n--- Section 4: Expiry Date Validation ---\n";
$currentYear = (int) date('Y');
$expiryTests = [
    ['12', (string)$currentYear, true],
    ['01', (string)($currentYear + 2), true],
    ['13', (string)($currentYear + 2), false], // Invalid month
    ['00', (string)($currentYear + 2), false], // Invalid month
    ['05', '2020', false],                     // Past year
];

foreach ($expiryTests as [$month, $year, $expectedValid]) {
    $codeExp = sprintf('$r = validateExpiry(%s, %s); echo $r["valid"] ? "1" : "0";', var_export($month, true), var_export($year, true));
    $resExp = trim(evalFunctionInSubprocess($codeExp)) === '1';
    assertEquals($expectedValid, $resExp, "validateExpiry('{$month}', '{$year}')");
}

// Section 5: Heuristic Analysis Functions
echo "\n--- Section 5: Heuristic Analysis Functions ---\n";

$resUnique = (int)trim(evalFunctionInSubprocess('echo uniqueDigitCount("4242424242424242");'));
assertEquals(2, $resUnique, "uniqueDigitCount('4242424242424242') should be 2");

$resRun = (int)trim(evalFunctionInSubprocess('echo longestRun("11112345");'));
assertEquals(4, $resRun, "longestRun('11112345') should be 4");

$resSeq = trim(evalFunctionInSubprocess('echo longestSequentialRun("12345678") ? "1" : "0";')) === '1';
assertEquals(true, $resSeq, "longestSequentialRun('12345678') should return true");

$resEntropy = trim(evalFunctionInSubprocess('echo shannonEntropy("1111111111111111") == 0 ? "1" : "0";')) === '1';
assertEquals(true, $resEntropy, "shannonEntropy for all identical digits should be 0");

$resTestBin = trim(evalFunctionInSubprocess('echo isTestBIN("400000") ? "1" : "0";')) === '1';
assertEquals(true, $resTestBin, "isTestBIN('400000') should return true");

// Section 6: API Endpoint End-to-End Tests
echo "\n--- Section 6: API Endpoint End-to-End Tests ---\n";

// 1. Empty data
$resApi1 = callApi('');
assertEquals(4, $resApi1['error'], "API empty data error code");
assertEquals("No data provided", $resApi1['message'], "API empty data message");

// 2. Invalid format
$resApi2 = callApi('4242424242424242|12|2028');
assertEquals(4, $resApi2['error'], "API invalid format error code");
assertTrue(str_contains($resApi2['message'], 'Invalid format'), "API invalid format message check");

// 3. Failed Luhn check (Dies)
$resApi3 = callApi('4242424242424241|12|2028|123');
assertEquals(2, $resApi3['error'], "API failed Luhn error code (Die)");
assertEquals("die", $resApi3['status'], "API status die");

// 4. Test card / sandbox card (Dies due to heuristics)
$resApi4 = callApi('4000001234567890|12|2028|123');
assertEquals(2, $resApi4['error'], "API test card BIN error code (Die)");
assertEquals("die", $resApi4['status'], "API test card status die");

// 5. Valid card with valid format
$resApi5 = callApi('4242424242424242|12|2028|123');
assertTrue(in_array($resApi5['error'], [1, 2, 3]), "API card check valid response error code");
assertEquals("Visa", $resApi5['network'], "API card network detection Visa");
assertTrue(isset($resApi5['score']), "API score field present");
assertTrue(isset($resApi5['message']), "API reason message present");

echo "\n===========================================\n";
echo " All tests passed successfully! 🎉\n";
echo "===========================================\n";
