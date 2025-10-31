# CC Checker

This is a simple web-based tool for checking the validity of credit card numbers. The tool is built using HTML, CSS, and Bootstrap for the front-end, and PHP for the back-end.

### 🍥 Similar :

[KE1-CC-CHECKER](https://github.com/OshekharO/KE1-CC-CHECKER)

### ✨ Features :

• Check the validity of credit card number with Luhn algorithm validation

• Shows live, die, and unknown card number

• **BIN (Bank Identification Number) detection** - Automatically identifies card brand (Visa, Mastercard, Amex, etc.)

• **Card brand detection** - Recognizes major card types and validates CVV length accordingly

• **Rate limiting** - Prevents abuse with configurable request limits

• **Copy to clipboard** - Easily copy results for each category (Live, Die, Unknown)

• **Export results** - Download all results as a text file

• **Progress tracking** - Real-time progress bar and statistics (processing rate, ETA)

• **Clear results** - One-click button to clear all results

• **Enhanced validation** - Better error handling and input validation

• Configurable validation settings via config.php

• Support for cards with 13-19 digits

### 😶‍🌫️ Usage :

1. Enter credit card numbers in the input field in the format of `card_number|expiry_month|expiry_year|cvv`

2. Click the "START" button to begin the check

3. The tool will display the number of live, die, and unknown card numbers in real-time

### ⚙️ Configuration :

Edit `config.php` to customize validation settings:

**Card Validation:**
- `ENABLE_LUHN_CHECK` - Enable/disable Luhn algorithm validation
- `MIN_CARD_LENGTH` - Minimum card number length (default: 13)
- `MAX_CARD_LENGTH` - Maximum card number length (default: 19)
- `MIN_VALID_YEAR` - Minimum valid expiry year (default: 2024)

**Security Settings:**
- `ENABLE_RATE_LIMITING` - Enable/disable rate limiting (default: true)
- `MAX_REQUESTS_PER_MINUTE` - Maximum requests per minute per IP (default: 60)
- `RATE_LIMIT_DURATION` - Rate limit duration in seconds (default: 60)

**Feature Toggles:**
- `ENABLE_BIN_DETECTION` - Enable/disable BIN detection (default: true)
- `ENABLE_CARD_BRAND_DETECTION` - Enable/disable card brand detection (default: true)

**Processing Settings:**
- `PROCESSING_DELAY_MS` - Delay between requests in milliseconds (default: 100)

**Logging:**
- `ENABLE_ERROR_LOGGING` - Enable/disable error logging (default: true)
- `LOG_FILE_PATH` - Path to log file (default: logs/error.log)

## 💽 Where To Host :

1. https://www.freehostia.com

2. https://infinityfree.net

Note: You can use any hosting.

## 🚸 Warnings :

- This is Just For Educational Purpose.

- DO NOT Sell this Script, This is 100% Free

## 🤗 Contact Me :


• For any Support About Script contact [issues](https://github.com/OshekharO/MASS-CC-CHECKER/issues/new)

---

<h4 align='center'>© 2023 ツ ѕнєкнєя</h4>

<!-- DO NOT REMOVE THIS CREDIT 🤬 🤬 -->
