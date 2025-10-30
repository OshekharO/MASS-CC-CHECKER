# Features Documentation

## MASS CC Checker v2.0

This document provides detailed information about all features available in MASS CC Checker.

---

## 🎯 Core Features

### 1. Credit Card Validation

#### Luhn Algorithm Validation
* **Backend Implementation**: Server-side validation using the Luhn algorithm
* **Purpose**: Ensures mathematically valid card numbers
* **Benefit**: Catches typos and invalid numbers before processing

#### Format Validation
* **Supported Formats**: `CARD_NUMBER|MM|YYYY|CVV`
* **Flexible Card Length**: Supports 13-19 digit card numbers
* **CVV Validation**: 
  - 3 digits for Visa, Mastercard, Discover, etc.
  - 4 digits for American Express

#### Expiry Validation
* **Month Validation**: Ensures month is between 01-12
* **Year Validation**: Checks cards are not expired
* **Current Date Comparison**: Validates against current month/year

---

## 💳 Card Brand Detection

The system automatically detects and displays card brands:

| Brand | Pattern | Example |
|-------|---------|---------|
| **Visa** | Starts with 4 | 4532015112830366 |
| **Mastercard** | Starts with 51-55 | 5425233430109903 |
| **American Express** | Starts with 34 or 37 | 371449635398431 |
| **Discover** | Starts with 6011 or 65 | 6011111111111117 |
| **Diners Club** | Starts with 300-305, 36, 38 | 30569309025904 |
| **JCB** | Starts with 2131, 1800, 35 | 3530111333300000 |
| **UnionPay** | Starts with 62 or 88 | 6212341234567890 |
| **Maestro** | Various patterns | 6304000000000000 |

---

## 📊 Real-Time Statistics Dashboard

### Statistics Cards
* **Live Counter**: Shows number of valid cards (green)
* **Die Counter**: Shows number of invalid/declined cards (red)
* **Unknown Counter**: Shows cards with unknown status (yellow)
* **Total Counter**: Shows total cards processed (blue)

### Visual Feedback
* Color-coded results for easy identification
* Animated counters that update in real-time
* Responsive card design that adapts to screen size

---

## 🔒 Security Features

### Rate Limiting
* **Default Limit**: 100 requests per hour per session
* **Purpose**: Prevents abuse and server overload
* **Customizable**: Can be configured in `config.php`

### Input Sanitization
* **XSS Protection**: All user input is sanitized
* **SQL Injection Prevention**: Uses proper data validation
* **Format Validation**: Strict regex patterns for card data

### Security Headers
* **X-Content-Type-Options**: nosniff
* **X-Frame-Options**: DENY
* **X-XSS-Protection**: Enabled
* **CORS Configuration**: Customizable allowed origins

### Session Management
* **Secure Sessions**: PHP session handling
* **Timeout**: 1-hour default session lifetime
* **Request Tracking**: Monitors request count per session

---

## 📤 Export Functionality

### Export Format
* **Format**: JSON
* **Filename**: `cc-checker-results-[timestamp].json`

### Exported Data Includes:
```json
{
  "timestamp": "2024-10-30T06:21:23.899Z",
  "summary": {
    "live": 5,
    "die": 3,
    "unknown": 2,
    "total": 10
  },
  "results": {
    "live": ["card1|mm|yyyy|cvv", "card2|mm|yyyy|cvv"],
    "die": ["card3|mm|yyyy|cvv"],
    "unknown": ["card4|mm|yyyy|cvv"]
  }
}
```

---

## 🎮 User Interface Features

### Bulk Processing
* **Batch Input**: Enter multiple cards (one per line)
* **Async Processing**: Non-blocking card validation
* **Progress Tracking**: Shows processed/total in browser tab

### Interactive Controls

#### START Button
* Initiates card validation process
* Disabled during processing
* Keyboard shortcut: `Ctrl + Enter`

#### STOP Button
* Stops ongoing validation
* Gracefully halts processing
* Keyboard shortcut: `Escape`

#### CLEAR Button
* Clears all results and counters
* Requires confirmation
* Resets input field

#### EXPORT Button
* Exports results to JSON file
* Downloads automatically
* Only enabled when results exist

### Keyboard Shortcuts
* `Ctrl + Enter`: Start processing
* `Escape`: Stop processing
* Standard form shortcuts available

---

## 🎨 Visual Design

### Modern UI Elements
* **Dark Theme**: Easy on the eyes
* **Gradient Background**: Professional appearance
* **Card Animations**: Hover effects on statistics cards
* **Smooth Transitions**: All interactions are animated

### Responsive Design
* **Mobile Friendly**: Works on all screen sizes
* **Touch Optimized**: Easy to use on tablets/phones
* **Adaptive Layout**: Adjusts to viewport size

### Result Display
* **Color Coding**:
  - Green: Live/Valid cards
  - Red: Die/Invalid cards
  - Yellow: Unknown status
* **Scrollable Results**: Individual scrolling for each category
* **Monospace Font**: Easy to read card numbers
* **Auto-scroll**: Latest results appear at top

---

## ⚙️ Configuration Options

All configurable options are in `config.php`:

### Validation Settings
```php
ENABLE_LUHN_CHECK = true
MIN_CARD_LENGTH = 13
MAX_CARD_LENGTH = 19
MIN_VALID_YEAR = 2024
```

### Rate Limiting
```php
RATE_LIMIT_ENABLED = true
RATE_LIMIT_MAX_REQUESTS = 100
RATE_LIMIT_TIME_WINDOW = 3600
```

### Session Settings
```php
SESSION_ENABLED = true
SESSION_LIFETIME = 3600
```

### Export Settings
```php
EXPORT_ENABLED = true
EXPORT_FORMATS = ['txt', 'csv', 'json']
```

---

## 🔄 Processing Flow

1. **User Input**: User enters card data
2. **Client Validation**: Basic format check
3. **Submit**: Data sent to API endpoint
4. **Rate Check**: Server validates rate limit
5. **Format Parse**: Extract card components
6. **Luhn Check**: Validate card algorithm
7. **Brand Detection**: Identify card issuer
8. **Expiry Check**: Validate expiration date
9. **CVV Check**: Validate CVV length
10. **Response**: Return result to client
11. **Display**: Show result in appropriate category

---

## 📱 Browser Compatibility

### Supported Browsers
* ✅ Chrome/Edge 90+
* ✅ Firefox 88+
* ✅ Safari 14+
* ✅ Opera 76+
* ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Required Features
* JavaScript ES6+
* jQuery 3.6+
* CSS3
* Fetch API / AJAX

---

## 🎓 Educational Purpose

**Important Notice**: This tool is designed for educational purposes only.

### Appropriate Uses:
* Learning about payment card validation
* Understanding the Luhn algorithm
* Testing card validation systems
* Educational demonstrations
* Training materials

### Prohibited Uses:
* Validating stolen credit cards
* Fraud or illegal activities
* Unauthorized testing of real cards
* Any activity that violates laws or regulations

---

## 🔧 Technical Details

### Backend
* **Language**: PHP 7.4+
* **Dependencies**: None (vanilla PHP)
* **Sessions**: Native PHP sessions
* **Security**: Input validation, rate limiting

### Frontend
* **HTML5**: Semantic markup
* **CSS3**: Modern styling with animations
* **JavaScript**: ES6+ with jQuery
* **Framework**: Bootstrap 5.3

### API
* **Method**: POST
* **Format**: JSON
* **Endpoint**: `/api.php`
* **Response Codes**:
  - 1: Live/Valid
  - 2: Die/Invalid
  - 3: Unknown
  - 4: Error
  - 5: Rate Limit

---

## 📈 Future Enhancements

Potential features for future versions:

* Multi-language support
* Batch file upload
* More export formats (CSV, TXT)
* History tracking
* Advanced statistics
* API key authentication
* Webhook integration
* Detailed logging
* Admin dashboard
* Database storage option

---

## 📞 Support

For issues, questions, or suggestions:
* Open an issue on GitHub
* Check existing issues first
* Provide detailed information
* Include steps to reproduce

---

**Version**: 2.0  
**Last Updated**: October 2024  
**Author**: OshekharO
