# CC Checker

A modern, minimalist web-based tool for validating credit card numbers. Built with a focus on user experience, responsiveness, and robust bank-algorithm validation.

### 🎨 Design Features :

• **Minimalist & Intuitive Interface** - Clean, modern dark theme with smooth animations

• **Fully Responsive** - Works seamlessly on desktop, tablet, and mobile devices

• **Smooth Animations** - CSS transitions and animations for enhanced UX

• **Progress Tracking** - Real-time progress bar and statistics during validation

### 🍥 Similar :

[KE1-CC-CHECKER](https://github.com/OshekharO/KE1-CC-CHECKER)

### ✨ Features :

• **Luhn Algorithm Validation** - ISO/IEC 7812-1 compliant checksum verification

• **Card Type Detection** - Automatic recognition of Visa, Mastercard, Amex, Discover, Diners, JCB, UnionPay, and Maestro

• **Comprehensive Validation** - Card length, CVV, and expiry date validation based on card type

• **Real-time Results** - Shows live, die, and unknown card numbers instantly

• **Configurable Settings** - Customize validation via config.php

• **Bulk Processing** - Check multiple cards at once with stop functionality

### 🏦 Supported Card Types :

| Card Type | IIN/BIN Range | Lengths | CVV |
|-----------|---------------|---------|-----|
| Visa | 4xxx | 13, 16, 19 | 3 |
| Mastercard | 51-55xx, 2221-2720 | 16 | 3 |
| American Express | 34xx, 37xx | 15 | 4 |
| Discover | 6011, 644-649, 65 | 16, 19 | 3 |
| Diners Club | 300-305, 36, 38 | 14, 16, 19 | 3 |
| JCB | 2131, 1800, 35 | 16-19 | 3 |
| UnionPay | 62xx | 16-19 | 3 |
| Maestro | Various | 12-19 | 3 |

### 😶‍🌫️ Usage :

1. Enter credit card numbers in the input field in the format of `card_number|expiry_month|expiry_year|cvv`

2. Click the "START" button to begin the check

3. The tool will display the number of live, die, and unknown card numbers in real-time

4. Use the "STOP" button to halt processing at any time

### ⚙️ Configuration :

Edit `config.php` to customize validation settings:

- `ENABLE_LUHN_CHECK` - Enable/disable Luhn algorithm validation
- `MIN_CARD_LENGTH` - Minimum card number length (default: 13)
- `MAX_CARD_LENGTH` - Maximum card number length (default: 19)
- `MIN_VALID_YEAR` - Minimum valid expiry year (default: 2024)

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
