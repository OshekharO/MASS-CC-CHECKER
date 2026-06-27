# CC Checker

A modern, minimalist web-based tool for validating credit card numbers. Built with a focus on user experience, responsiveness, and robust bank-algorithm validation. Engineered with high-performance PHP backend logic and an optimized Luhn implementation.

### 🎨 Design Features :

• **Minimalist & Intuitive Interface** - Clean, modern dark theme with smooth animations

• **Fully Responsive** - Works seamlessly on desktop, tablet, and mobile devices

• **Smooth Animations** - CSS transitions and keyframe animations for enhanced UX

• **Progress Tracking** - Real-time progress bar and statistics during validation

### 🍥 Similar :

[KE1-CC-CHECKER](https://github.com/OshekharO/KE1-CC-CHECKER)

### ✨ Features :

• **Optimized Luhn Algorithm** - ISO/IEC 7812-1 compliant, branch-free checksum verification for maximum performance

• **Advanced Card Type Detection** - Automatic recognition of Visa, Mastercard, Amex, Discover, Diners, JCB, UnionPay, Maestro, Mir, and Troy

• **Comprehensive Validation** - Strict card length, CVV, and dynamic expiry date validation based on specific card networks

• **Anti-Fraud Heuristics** - Rejects known sandbox/test cards (O(1) lookup), identical digits, sequential patterns, and suspicious CVVs (e.g., CVV is a substring of the card number)

• **Real-time Results** - Shows live, die, and unknown card numbers instantly with a 32-bit safe deterministic scoring engine

• **Configurable Settings** - Customize validation rules via `config.php`

• **Bulk Processing** - Check multiple cards at once with stop functionality

### 🏦 Supported Card Types :

| Card Type | IIN/BIN Range | Lengths | CVV |
|-----------|---------------|---------|-----|
| Visa | 4xxx | 13, 16, 19 | 3 |
| Mastercard | 51-55xx, 2221-2720 | 16 | 3 |
| American Express | 34xx, 37xx | 15 | 4 |
| Discover | 6011, 644-649, 65, 622126-622925 | 16, 19 | 3 |
| Diners Club | 300-305, 36, 38 | 14, 16, 19 | 3 |
| JCB | 2131, 1800, 35 | 16-19 | 3 |
| UnionPay | 62xx | 16-19 | 3 |
| Maestro | 5018, 5020, 5038, etc. | 12-19 | 3 |
| Mir | 2200-2204 | 16 | 3 |
| Troy | 9792 | 16 | 3 |

### 😶‍🌫️ Usage :

1. Enter credit card numbers in the input field in the format of `card_number|expiry_month|expiry_year|cvv` (e.g., `4242424242424242|12|2026|123`)
2. Click the "START" button to begin the check
3. The tool will display the number of live, die, and unknown card numbers in real-time
4. Use the "STOP" button to halt processing at any time

### ⚙️ Configuration :

Edit `config.php` to customize validation settings:

- `ENABLE_LUHN_CHECK` - Enable/disable Luhn algorithm validation
- `MIN_CARD_LENGTH` - Minimum card number length (default: 13)
- `MAX_CARD_LENGTH` - Maximum card number length (default: 19)
- `MIN_VALID_YEAR` - Minimum valid expiry year (default: Dynamically set to the current year to prevent silent expiry bugs)

## 💽 Where To Host :

1. https://www.freehostia.com

2. https://infinityfree.net

Note: You can use any hosting that supports PHP.

## 🚸 Warnings :

- This is Just For Educational Purpose.

- DO NOT Sell this Script, This is 100% Free

## 🤗 Contact Me :


• For any Support About Script contact [issues](https://github.com/OshekharO/MASS-CC-CHECKER/issues/new)

---

<h4 align='center'>© 2026 ツ ѕнєкнєя</h4>

<!-- DO NOT REMOVE THIS CREDIT 🤬 🤬 -->
