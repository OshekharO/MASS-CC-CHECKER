# CC Checker v2.0

A modern, feature-rich web-based tool for validating credit card numbers. Built with HTML, CSS, Bootstrap, and PHP with enhanced security and usability features.

### 🍥 Similar Projects:

[KE1-CC-CHECKER](https://github.com/OshekharO/KE1-CC-CHECKER)

## ✨ Features

### Core Functionality
• **Advanced Card Validation** - Luhn algorithm validation with comprehensive error checking  
• **Multiple Card Format Support** - Supports 13-19 digit card numbers  
• **Card Brand Detection** - Automatically identifies Visa, Mastercard, Amex, Discover, JCB, Diners, UnionPay, and Maestro  
• **Real-time Results** - Live, die, and unknown card status display  
• **Batch Processing** - Check multiple cards simultaneously  

### Security Features
• **Rate Limiting** - Prevents abuse with configurable request limits (100 requests/hour default)  
• **Input Validation** - Server-side validation with strict format checking  
• **Security Headers** - XSS protection, frame options, and content-type protection  
• **Session Management** - Secure session handling with configurable timeouts  

### User Interface
• **Modern Dashboard** - Real-time statistics with color-coded counters  
• **Export Functionality** - Download results in JSON format  
• **Keyboard Shortcuts** - Ctrl+Enter to start, Escape to stop  
• **Progress Tracking** - See processing status in browser tab  
• **Responsive Design** - Works seamlessly on desktop and mobile  
• **Dark Theme** - Professional dark mode interface  

### Enhanced Validation
• **Expiry Date Checking** - Validates month (01-12) and year (not expired)  
• **CVV Validation** - Ensures correct CVV length for each card type  
• **Format Detection** - Flexible parsing with comprehensive error messages  

## 📋 Requirements

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Modern web browser with JavaScript enabled

## 🚀 Installation

1. Clone the repository:
```bash
git clone https://github.com/OshekharO/MASS-CC-CHECKER.git
cd MASS-CC-CHECKER
```

2. Configure settings (optional):
   - Edit `config.php` to customize rate limits, validation rules, etc.

3. Start the application:

**Using PHP built-in server:**
```bash
php -S localhost:8000
```

**Using Apache/Nginx:**
- Place files in your web server's document root
- Access via browser at your configured URL

## 😶‍🌫️ Usage

1. **Enter card data** in the format: `card_number|MM|YYYY|CVV`  
   Example: `4532015112830366|12|2025|123`

2. **Multiple cards**: Enter one card per line

3. **Click START** or press `Ctrl+Enter` to begin validation

4. **View results** in real-time:
   - 🟢 **Live**: Valid cards
   - 🔴 **Die**: Invalid/declined cards  
   - 🟡 **Unknown**: Status could not be determined

5. **Export results**: Click EXPORT to download JSON file

6. **Clear results**: Click CLEAR to reset all counters

## 📖 Card Format Examples

### Supported Card Brands
```
Visa:            4532015112830366|12|2025|123
Mastercard:      5425233430109903|08|2026|456
American Express: 371449635398431|01|2027|1234
Discover:        6011111111111117|03|2025|789
JCB:             3530111333300000|06|2026|321
```

### Format Rules
- **Card Number**: 13-19 digits
- **Month**: 01-12
- **Year**: 4 digits (must not be expired)
- **CVV**: 3 digits (4 for Amex)

## ⚙️ Configuration

Edit `config.php` to customize:

```php
// Rate Limiting
define('RATE_LIMIT_MAX_REQUESTS', 100);    // Max requests per hour
define('RATE_LIMIT_TIME_WINDOW', 3600);    // Time window in seconds

// Validation
define('ENABLE_LUHN_CHECK', true);         // Enable/disable Luhn validation
define('MIN_CARD_LENGTH', 13);             // Minimum card number length
define('MAX_CARD_LENGTH', 19);             // Maximum card number length
define('MIN_VALID_YEAR', 2024);            // Minimum valid expiry year

// Sessions
define('SESSION_ENABLED', true);           // Enable session management
define('SESSION_LIFETIME', 3600);          // Session timeout (seconds)
```

## 🏗️ Project Structure

```
MASS-CC-CHECKER/
├── index.php           # Main interface
├── api.php            # Backend API endpoint
├── config.php         # Configuration settings
├── style.css          # Styling
├── README.md          # This file
├── FEATURES.md        # Detailed features documentation
├── CONTRIBUTING.md    # Contribution guidelines
├── LICENSE            # MIT License
└── .gitignore         # Git ignore rules
```

## 💽 Where To Host

### Free Hosting Options:
1. [Freehostia](https://www.freehostia.com)
2. [InfinityFree](https://infinityfree.net)
3. [000webhost](https://www.000webhost.com)
4. Any PHP-compatible hosting

### Local Development:
```bash
php -S localhost:8000
```

## 🔒 Security Notes

- All user input is validated and sanitized
- Rate limiting prevents abuse
- Security headers protect against common attacks
- Session management with secure timeouts
- No sensitive data is stored

## 🚸 Important Warnings

- ⚠️ **Educational Purpose Only** - This tool is for learning and testing
- ❌ **DO NOT** use for illegal activities or fraud
- ❌ **DO NOT** test real credit cards without authorization
- ❌ **DO NOT** sell this script - it's 100% free
- ✅ **DO** use for understanding card validation algorithms
- ✅ **DO** use for educational demonstrations

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

### Ways to Contribute:
- 🐛 Report bugs
- 💡 Suggest new features
- 📝 Improve documentation
- 🔧 Submit pull requests
- ⭐ Star the repository

## 📚 Documentation

- [Features Documentation](FEATURES.md) - Detailed feature descriptions
- [Contributing Guide](CONTRIBUTING.md) - How to contribute

## 🎯 Roadmap

### Planned Features:
- [ ] Multi-language support
- [ ] CSV/TXT export formats
- [ ] Bulk file upload
- [ ] History tracking
- [ ] Advanced statistics
- [ ] API authentication
- [ ] Database integration option

## 🤗 Support & Contact

- **Issues**: [GitHub Issues](https://github.com/OshekharO/MASS-CC-CHECKER/issues/new)
- **Discussions**: [GitHub Discussions](https://github.com/OshekharO/MASS-CC-CHECKER/discussions)
- **Documentation**: See FEATURES.md for detailed information

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Bootstrap team for the UI framework
- jQuery team for the JavaScript library
- All contributors and users of this project

## 📊 Version History

### v2.0 (Current)
- ✨ Enhanced validation with comprehensive error checking
- 🎨 Modern UI with statistics dashboard
- 📤 Export functionality
- 🔒 Advanced security features
- 💳 Card brand detection
- ⌨️ Keyboard shortcuts
- 📱 Responsive design improvements

### v1.0
- Basic card validation
- Simple UI
- Live/Die/Unknown status

---

<h4 align='center'>© 2024 ツ ѕнєкнєя</h4>

<!-- DO NOT REMOVE THIS CREDIT 🤬 🤬 -->

**⭐ If you find this project useful, please consider giving it a star!**

