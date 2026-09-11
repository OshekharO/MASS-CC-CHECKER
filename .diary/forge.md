## 2026-09-11 - Fixed Variable Shadowing in `isTestBIN` & Comprehensive PHP Test Suite

**Learning:**
In `isTestBIN()`, using `foreach (BIN_DATABASE as $bin => $info)` shadowed the `$bin` function parameter. As a result, when indexing static array `$testBins[substr($bin, 0, 6)]`, `$bin` evaluated to the last key in `BIN_DATABASE` (an integer) rather than the passed parameter, breaking BIN test lookups when called repeatedly in the same process or script.

Additionally, testing procedural PHP files that combine helper functions with immediate top-level request execution (`$_POST['data']`, `header()`, `exit`) requires either subprocess isolation or splitting evaluation before the request handling block (`// Request handling`).

**Action:**
1. Renamed loop variable in `isTestBIN` to `$b` to preserve parameter `$bin`.
2. Removed duplicated transition penalty calculation block in `scoreCard()`.
3. Added `tests/test_checker.php` test runner covering Luhn algorithm validation, card type detection across 10 card networks, CVV validation, expiry logic, anti-fraud heuristics, and end-to-end API response codes.
