# API Documentation

## MASS CC Checker API v2.0

This document describes how to interact with the CC Checker API.

---

## Endpoint

**URL**: `/api.php`  
**Method**: `POST`  
**Content-Type**: `application/x-www-form-urlencoded`

---

## Request Format

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| data | string | Yes | Card data in format: `CARD|MM|YYYY|CVV` |

### Example Request

```bash
curl -X POST http://localhost:8000/api.php \
  -d "data=4532015112830366|12|2025|123"
```

### PHP Example

```php
<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=4532015112830366|12|2025|123');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
?>
```

### JavaScript Example

```javascript
fetch('http://localhost:8000/api.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded',
  },
  body: 'data=4532015112830366|12|2025|123'
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### Python Example

```python
import requests

url = 'http://localhost:8000/api.php'
data = {'data': '4532015112830366|12|2025|123'}

response = requests.post(url, data=data)
result = response.json()
print(result)
```

---

## Response Format

### Response Structure

```json
{
  "error": 1,
  "msg": "<div><b style='color:#008000;'>Live</b> | 4532015112830366|12|2025|123 | Visa - Valid Card ✓</div>",
  "brand": "Visa"
}
```

### Error Codes

| Code | Status | Description |
|------|--------|-------------|
| 1 | Live | Card passed all validations (simulated as valid) |
| 2 | Die | Card failed validation (invalid/declined) |
| 3 | Unknown | Card validation status unknown |
| 4 | Error | Invalid request or format error |
| 5 | Rate Limit | Rate limit exceeded |

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| error | integer | Status code (1-5) |
| msg | string | HTML-formatted message with result details |
| brand | string | Card brand (Visa, Mastercard, Amex, etc.) - Only present for valid formats |

---

## Card Format Rules

### Input Format

`CARD_NUMBER|EXPIRY_MONTH|EXPIRY_YEAR|CVV`

### Format Requirements

- **Card Number**: 13-19 digits (no spaces or dashes)
- **Expiry Month**: 2 digits (01-12)
- **Expiry Year**: 4 digits (must not be expired)
- **CVV**: 3 digits (4 for American Express)

### Valid Examples

```
4532015112830366|12|2025|123          # Visa
5425233430109903|08|2026|456          # Mastercard
371449635398431|01|2027|1234          # American Express
6011111111111117|03|2025|789          # Discover
3530111333300000|06|2026|321          # JCB
```

### Invalid Examples

```
4532015112830366|12|2025              # Missing CVV
453201511283036|12|2025|123           # Card number too short (15 digits, needs 16)
4532015112830366|13|2025|123          # Invalid month (13)
4532015112830366|12|2020|123          # Expired card
4532015112830366|12|2025|12           # CVV too short
```

---

## Validation Process

The API performs the following validations in order:

1. **Rate Limit Check** - Ensures user hasn't exceeded request limit
2. **Format Validation** - Validates input format matches pattern
3. **Card Length** - Checks card number is between 13-19 digits
4. **Luhn Algorithm** - Validates card number checksum
5. **Brand Detection** - Identifies card issuer
6. **Expiry Month** - Validates month is 01-12
7. **Expiry Year** - Checks card is not expired
8. **CVV Length** - Validates CVV matches card type (3 or 4 digits)

---

## Rate Limiting

### Default Limits

- **Max Requests**: 100 per session
- **Time Window**: 1 hour (3600 seconds)

### Rate Limit Response

When rate limit is exceeded:

```json
{
  "error": 5,
  "msg": "<div><b style=\"color:#FF0000;\">Rate Limit Exceeded</b> | Please wait before making more requests</div>"
}
```

### Configuring Rate Limits

Edit `config.php`:

```php
define('RATE_LIMIT_MAX_REQUESTS', 100);    // Requests allowed
define('RATE_LIMIT_TIME_WINDOW', 3600);    // Time window in seconds
```

---

## Supported Card Brands

The API automatically detects the following card brands:

| Brand | Detection Pattern | CVV Length |
|-------|-------------------|------------|
| Visa | Starts with 4 | 3 digits |
| Mastercard | Starts with 51-55 | 3 digits |
| American Express | Starts with 34 or 37 | 4 digits |
| Discover | Starts with 6011 or 65 | 3 digits |
| Diners Club | Starts with 300-305, 36, 38 | 3 digits |
| JCB | Starts with 2131, 1800, or 35 | 3 digits |
| UnionPay | Starts with 62 or 88 | 3 digits |
| Maestro | Various patterns | 3 digits |

---

## Error Handling

### Common Errors

#### Invalid Format Error
```json
{
  "error": 4,
  "msg": "<div><b style=\"color:#FF0000;\">Invalid Format</b> | Expected format: card_number|MM|YYYY|CVV</div>"
}
```

#### Failed Luhn Check
```json
{
  "error": 2,
  "msg": "<div><b style='color:#FF0000;'>Die</b> | 4532015112830367|12|2025|123 | Failed Luhn check</div>"
}
```

#### Expired Card
```json
{
  "error": 2,
  "msg": "<div><b style='color:#FF0000;'>Die</b> | 4532015112830366|12|2020|123 | Card expired</div>"
}
```

#### Invalid Month
```json
{
  "error": 2,
  "msg": "<div><b style='color:#FF0000;'>Die</b> | 4532015112830366|13|2025|123 | Invalid expiry month</div>"
}
```

---

## Security Features

### Implemented Security

- ✅ **Rate Limiting** - Prevents abuse
- ✅ **Input Validation** - All inputs are validated and sanitized
- ✅ **Security Headers** - XSS, clickjacking protection
- ✅ **Session Management** - Secure session handling
- ✅ **CORS Configuration** - Configurable cross-origin requests

### Security Headers

The API automatically sets:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Type: application/json
```

---

## Testing

### Using cURL

```bash
# Test valid card
curl -X POST http://localhost:8000/api.php \
  -d "data=4532015112830366|12|2025|123"

# Test invalid format
curl -X POST http://localhost:8000/api.php \
  -d "data=invalid-format"

# Test expired card
curl -X POST http://localhost:8000/api.php \
  -d "data=4532015112830366|12|2020|123"
```

### Using Postman

1. Create new POST request
2. URL: `http://localhost:8000/api.php`
3. Body type: `x-www-form-urlencoded`
4. Key: `data`, Value: `4532015112830366|12|2025|123`
5. Send request

---

## Configuration

### Available Settings (config.php)

```php
// Debug mode
define('DEBUG_MODE', false);

// Rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 100);
define('RATE_LIMIT_TIME_WINDOW', 3600);

// Validation
define('ENABLE_LUHN_CHECK', true);
define('MIN_CARD_LENGTH', 13);
define('MAX_CARD_LENGTH', 19);
define('MIN_VALID_YEAR', 2024);

// Sessions
define('SESSION_ENABLED', true);
define('SESSION_LIFETIME', 3600);

// Security
define('ENABLE_SECURITY_HEADERS', true);
define('ALLOWED_ORIGINS', ['*']);
```

---

## Integration Examples

### Batch Processing Script

```php
<?php
$cards = [
    '4532015112830366|12|2025|123',
    '5425233430109903|08|2026|456',
    '371449635398431|01|2027|1234'
];

$results = [];

foreach ($cards as $card) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "data=$card");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    $results[] = [
        'card' => $card,
        'status' => $result['error'],
        'brand' => $result['brand'] ?? 'Unknown'
    ];
    
    // Small delay to respect rate limits
    usleep(100000); // 100ms
}

print_r($results);
?>
```

---

## Troubleshooting

### Common Issues

**Issue**: Headers already sent error  
**Solution**: Ensure no output before `session_start()` and `header()` calls

**Issue**: Rate limit hit immediately  
**Solution**: Clear browser cookies or increase rate limit in config

**Issue**: All cards return "Die"  
**Solution**: This is expected - the API simulates random results for demonstration

**Issue**: Card brand not detected  
**Solution**: Ensure card number matches a known pattern (see Supported Card Brands)

---

## Best Practices

1. **Rate Limiting**: Implement delays between batch requests
2. **Error Handling**: Always check error codes in responses
3. **Input Validation**: Validate on client-side before sending to API
4. **Security**: Never send real card data without proper security
5. **Testing**: Use test card numbers only

---

## Legal & Compliance

⚠️ **Important**: This API is for **educational purposes only**.

- ✅ Use for learning and testing
- ✅ Use with test/dummy card numbers
- ❌ Do NOT use with real credit card data
- ❌ Do NOT use for fraud or illegal activities
- ❌ Do NOT validate stolen cards

---

## Support

For issues or questions:
- Open an issue on GitHub
- Check the FEATURES.md documentation
- Review CONTRIBUTING.md for development guidelines

---

**API Version**: 2.0  
**Last Updated**: October 2024  
**Documentation Author**: OshekharO
