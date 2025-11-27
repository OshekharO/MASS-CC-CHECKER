<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Free Credit Card Checker - Validate & Verify Card Numbers Instantly</title>
  <meta name="description" content="Our free credit card checker instantly validates card numbers using the Luhn algorithm. Check Visa, Mastercard, Amex & more. No registration required.">
  <meta name="keywords" content="credit card validator, CC number checker, Luhn algorithm checker, payment card verification, free card checker, test credit cards, card number validator, BIN checker, card security check, bulk checker, mrchecker, namso, cc checker live">
  
  <!-- Canonical & Social Meta -->
  <link rel="canonical" href="https://uncoder.eu.org/cc-checker">
  <meta property="og:title" content="Free Credit Card Checker - Validate & Verify Card Numbers Instantly">
  <meta property="og:description" content="Instantly check credit card validity with our free online checker. Supports all major card types with Luhn algorithm verification.">
  <meta property="og:image" content="https://uncoder.eu.org/assets/icons/apple-touch-icon.png">
  
  <!-- Robots -->
  <meta name="robots" content="index, follow">
  <meta name="googlebot" content="index, follow">
  
  <!-- Structured Data for Featured Snippets -->
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "Credit Card Checker",
      "url": "https://uncoder.eu.org/cc-checker",
      "description": "Free online tool to validate credit card numbers using the Luhn algorithm",
      "applicationCategory": "FinanceApplication",
      "operatingSystem": "Web Browser",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      },
      "creator": {
        "@type": "Person",
        "name": "OshekharO"
      }
    }
  </script>
  
  <!-- Standard Favicon -->
  <link rel="icon" href="../assets/icons/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/icons/favicon-16x16.png">
  <link rel="icon" type="image/svg+xml" href="../assets/icons/safari-pinned-tab.svg">

  <!-- Apple Touch Icon -->
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/icons/apple-touch-icon.png">
  <link rel="apple-touch-icon" sizes="152x152" href="../assets/icons/apple-touch-icon-152x152.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>
  <div class="container-main">
    <h1 class="page-title">Credit Card Checker</h1>
    
    <!-- Info Messages -->
    <div class="info" id="info-message"></div>
    
    <form method="post" action="api.php" role="form" id="form">
      <div class="box-body">
        <div class="box-content">
          <label for="cc" class="form-label badge bg-danger">Card Numbers</label>
          <textarea 
            class="form-control" 
            rows="8" 
            id="cc" 
            name="cc" 
            title="Format: card_number|MM|YY|CVV or card_number|MM|YYYY|CVV" 
            placeholder="5301272453912345|05|25|653&#10;4111111111111111|12|2026|123&#10;378282246310005|08|27|1234"
            required
            aria-describedby="format-help"
          ></textarea>
          <small id="format-help" class="format-help">
            Format: card_number|MM|YY|CVV or card_number|MM|YYYY|CVV (one per line)
          </small>
          
          <!-- Progress Bar -->
          <div class="progress-bar-container" id="progress-container" style="display: none;">
            <div class="progress-bar" id="progress-bar"></div>
          </div>
          
          <div class="button text-center mb-3 mt-3">
            <button type="submit" name="valid" class="btn btn-outline-success" id="start-btn">
              START
            </button>
            <button type="button" id="stop" class="btn btn-outline-danger" disabled>
              STOP
            </button>
          </div>
          
          <!-- Stats Summary -->
          <div class="stats-summary" id="stats-summary" style="display: none;">
            <div class="stat-item">
              <div class="stat-value" id="stat-total">0</div>
              <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="stat-processed">0</div>
              <div class="stat-label">Processed</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Results -->
      <div class="box-title">
        <h3 class="panel-title alert alert-primary">
          <span>Live</span>
          <span class="badge bg-success live" id="live-count">0</span>
        </h3>
      </div>
      <div class="box-body">
        <div class="box-content alert alert-success">
          <div class="panel-body success" id="success-panel"></div>
        </div>
      </div>

      <!-- Dead Results -->
      <div class="box-title">
        <h3 class="panel-title alert alert-primary">
          <span>Die</span>
          <span class="badge bg-danger die" id="die-count">0</span>
        </h3>
      </div>
      <div class="box-body">
        <div class="box-content alert alert-danger">
          <div class="panel-body danger" id="danger-panel"></div>
        </div>
      </div>

      <!-- Unknown Results -->
      <div class="box-title">
        <h3 class="panel-title alert alert-primary">
          <span>Unknown</span>
          <span class="badge bg-warning unknown" id="unknown-count">0</span>
        </h3>
      </div>
      <div class="box-body">
        <div class="box-content alert alert-warning">
          <div class="panel-body warning" id="warning-panel"></div>
        </div>
      </div>
    </form>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
  <script>
    /**
     * Credit Card Checker - Enhanced JavaScript
     * Features: Robust validation, card type detection, and improved UX
     */
    $(document).ready(function() {
      'use strict';

      // ============================================
      // DOM Element References (cached for performance)
      // ============================================
      const elements = {
        form: $('#form'),
        startBtn: $('#start-btn'),
        stopBtn: $('#stop'),
        ccInput: $('#cc'),
        liveCount: $('#live-count'),
        dieCount: $('#die-count'),
        unknownCount: $('#unknown-count'),
        successPanel: $('#success-panel'),
        dangerPanel: $('#danger-panel'),
        warningPanel: $('#warning-panel'),
        infoMessage: $('#info-message'),
        progressContainer: $('#progress-container'),
        progressBar: $('#progress-bar'),
        statsSummary: $('#stats-summary'),
        statTotal: $('#stat-total'),
        statProcessed: $('#stat-processed')
      };

      // ============================================
      // Application State
      // ============================================
      const state = {
        isProcessing: false,
        shouldStop: false,
        totalCards: 0,
        processedCards: 0
      };

      // ============================================
      // Card Type Definitions (Bank Algorithm Patterns)
      // Based on ISO/IEC 7812 standards
      // ============================================
      const CARD_TYPES = {
        visa: {
          name: 'Visa',
          patterns: [/^4/],
          lengths: [13, 16, 19],
          cvvLength: 3
        },
        mastercard: {
          name: 'Mastercard',
          patterns: [/^5[1-5]/, /^2(?:2[2-9][1-9]|2[3-9]|[3-6]|7[0-1]|720)/],
          lengths: [16],
          cvvLength: 3
        },
        amex: {
          name: 'Amex',
          patterns: [/^3[47]/],
          lengths: [15],
          cvvLength: 4
        },
        discover: {
          name: 'Discover',
          patterns: [/^6(?:011|5|4[4-9]|22(?:1(?:2[6-9]|[3-9])|[2-8]|9(?:[01]|2[0-5])))/],
          lengths: [16, 19],
          cvvLength: 3
        },
        diners: {
          name: 'Diners',
          patterns: [/^3(?:0[0-5]|[68])/],
          lengths: [14, 16, 19],
          cvvLength: 3
        },
        jcb: {
          name: 'JCB',
          patterns: [/^(?:2131|1800|35)/],
          lengths: [16, 17, 18, 19],
          cvvLength: 3
        },
        unionpay: {
          name: 'UnionPay',
          patterns: [/^62/],
          lengths: [16, 17, 18, 19],
          cvvLength: 3
        },
        maestro: {
          name: 'Maestro',
          patterns: [/^(?:5018|5020|5038|5893|6304|6759|676[1-3])/],
          lengths: [12, 13, 14, 15, 16, 17, 18, 19],
          cvvLength: 3
        }
      };

      // ============================================
      // Validation Functions
      // ============================================

      /**
       * Validates card number using Luhn algorithm (ISO/IEC 7812-1)
       * @param {string} number - Card number (digits only)
       * @returns {boolean} - True if valid
       */
      function isValidLuhn(number) {
        if (!number || !/^\d+$/.test(number)) {
          return false;
        }

        const digits = number.split('').map(Number).reverse();
        let sum = 0;

        for (let i = 0; i < digits.length; i++) {
          let digit = digits[i];
          
          if (i % 2 === 1) {
            digit *= 2;
            if (digit > 9) {
              digit -= 9;
            }
          }
          
          sum += digit;
        }

        return sum % 10 === 0;
      }

      /**
       * Detects card type based on IIN/BIN ranges
       * @param {string} number - Card number
       * @returns {object|null} - Card type info or null
       */
      function detectCardType(number) {
        const cleanNumber = number.replace(/\D/g, '');
        
        for (const [key, cardType] of Object.entries(CARD_TYPES)) {
          for (const pattern of cardType.patterns) {
            if (pattern.test(cleanNumber)) {
              return { key, ...cardType };
            }
          }
        }
        
        return null;
      }

      /**
       * Validates card length based on card type
       * @param {string} number - Card number
       * @param {object} cardType - Card type info
       * @returns {boolean} - True if length is valid
       */
      function isValidLength(number, cardType) {
        const cleanNumber = number.replace(/\D/g, '');
        
        if (!cardType) {
          // Generic validation: 13-19 digits
          return cleanNumber.length >= 13 && cleanNumber.length <= 19;
        }
        
        return cardType.lengths.includes(cleanNumber.length);
      }

      /**
       * Validates CVV based on card type
       * @param {string} cvv - CVV code
       * @param {object} cardType - Card type info
       * @returns {boolean} - True if CVV is valid
       */
      function isValidCVV(cvv, cardType) {
        if (!/^\d+$/.test(cvv)) {
          return false;
        }
        
        const expectedLength = cardType ? cardType.cvvLength : 3;
        // Allow 3 or 4 digits for unknown card types
        if (!cardType) {
          return cvv.length === 3 || cvv.length === 4;
        }
        
        return cvv.length === expectedLength;
      }

      /**
       * Converts 2-digit year (YY) to 4-digit year (YYYY)
       * @param {string} year - Year string (2 or 4 digits)
       * @returns {number} - Full 4-digit year
       */
      function convertToFullYear(year) {
        const yearStr = String(year).trim();
        const yearNum = parseInt(yearStr, 10);
        
        // If already 4 digits, return as-is
        if (yearStr.length === 4) {
          return yearNum;
        }
        
        // For 2-digit year: assume current century (2000s)
        // Credit cards typically have expiry within 10 years
        return 2000 + yearNum;
      }

      /**
       * Validates expiry date
       * @param {string} month - Expiry month (MM)
       * @param {string} year - Expiry year (YY or YYYY)
       * @returns {object} - Validation result with status and message
       */
      function validateExpiry(month, year) {
        const monthNum = parseInt(month, 10);
        const yearNum = convertToFullYear(year);
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        if (isNaN(monthNum) || monthNum < 1 || monthNum > 12) {
          return { valid: false, message: 'Invalid month (01-12)' };
        }

        if (isNaN(yearNum) || yearNum < currentYear) {
          return { valid: false, message: 'Card expired (year)' };
        }

        if (yearNum === currentYear && monthNum < currentMonth) {
          return { valid: false, message: 'Card expired (month)' };
        }

        // Cards typically valid for 3-5 years, flag suspicious if too far
        if (yearNum > currentYear + 10) {
          return { valid: false, message: 'Expiry year too far in future' };
        }

        return { valid: true, message: 'Valid' };
      }

      /**
       * Comprehensive card validation using bank algorithm standards
       * @param {string} cardData - Card data in format: number|MM|YY|CVV or number|MM|YYYY|CVV
       * @returns {object} - Validation result
       */
      function validateCard(cardData) {
        const result = {
          valid: false,
          cardNumber: '',
          cardType: null,
          errors: [],
          formatted: cardData
        };

        // Parse card data
        const parts = cardData.split('|');
        if (parts.length !== 4) {
          result.errors.push('Invalid format. Use: number|MM|YY|CVV or number|MM|YYYY|CVV');
          return result;
        }

        const [number, month, year, cvv] = parts.map(p => p.trim());
        const cleanNumber = number.replace(/\D/g, '');
        result.cardNumber = cleanNumber;

        // Validate year format (2 or 4 digits)
        if (!/^\d{2}$/.test(year) && !/^\d{4}$/.test(year)) {
          result.errors.push('Invalid year format (use YY or YYYY)');
        }

        // Detect card type
        result.cardType = detectCardType(cleanNumber);

        // Validate card number length
        if (!isValidLength(cleanNumber, result.cardType)) {
          const expectedLengths = result.cardType 
            ? result.cardType.lengths.join(', ')
            : '13-19';
          result.errors.push(`Invalid length (expected: ${expectedLengths} digits)`);
        }

        // Validate Luhn algorithm
        if (!isValidLuhn(cleanNumber)) {
          result.errors.push('Failed Luhn checksum');
        }

        // Validate expiry
        const expiryResult = validateExpiry(month, year);
        if (!expiryResult.valid) {
          result.errors.push(expiryResult.message);
        }

        // Validate CVV
        if (!isValidCVV(cvv, result.cardType)) {
          const expectedCvv = result.cardType ? result.cardType.cvvLength : '3-4';
          result.errors.push(`Invalid CVV (expected: ${expectedCvv} digits)`);
        }

        result.valid = result.errors.length === 0;
        return result;
      }

      // ============================================
      // UI Helper Functions
      // ============================================

      /**
       * Updates counter with animation
       * @param {jQuery} $element - Counter element
       * @param {number} value - Value to add
       */
      function updateCounter($element, value) {
        const currentVal = parseInt($element.text(), 10) || 0;
        const newVal = currentVal + value;
        $element.text(newVal);
        
        // Add pulse animation
        $element.css('transform', 'scale(1.2)');
        setTimeout(() => $element.css('transform', 'scale(1)'), 200);
      }

      /**
       * Displays a message in the specified panel
       * @param {string} panelId - Panel element ID
       * @param {string} message - HTML message to display
       */
      function displayMessage(panelId, message) {
        const $panel = $(panelId);
        $panel.prepend(message);
        
        // Scroll to top of panel
        $panel.scrollTop(0);
      }

      /**
       * Shows info message
       * @param {string} message - Message text
       * @param {string} type - Message type (info, error, success)
       */
      function showInfo(message, type = 'info') {
        const colors = {
          info: 'var(--accent-info)',
          error: 'var(--accent-danger)',
          success: 'var(--accent-success)'
        };
        
        elements.infoMessage
          .html(message)
          .css('border-color', colors[type])
          .show();
        
        setTimeout(() => elements.infoMessage.fadeOut(300), 5000);
      }

      /**
       * Updates progress bar
       * @param {number} current - Current progress
       * @param {number} total - Total items
       */
      function updateProgress(current, total) {
        const percentage = Math.round((current / total) * 100);
        elements.progressBar.css('width', `${percentage}%`);
        elements.statProcessed.text(current);
      }

      /**
       * Creates result HTML with card type badge
       * @param {string} status - Status (Live, Die, Invalid)
       * @param {string} data - Card data
       * @param {object} cardType - Card type info
       * @param {string} message - Additional message
       * @returns {string} - HTML string
       */
      function createResultHTML(status, data, cardType, message) {
        const colors = {
          'Live': '#10b981',
          'Die': '#ef4444',
          'Invalid': '#ef4444',
          'Unknown': '#f59e0b',
          'Error': '#ef4444'
        };
        
        const color = colors[status] || '#a0a3b1';
        const cardBadge = cardType 
          ? `<span class="card-type-badge">${cardType.name}</span>` 
          : '';
        
        return `<div>
          <b style="color:${color};">${status}</b>${cardBadge} | ${data}${message ? ` | ${message}` : ''}
        </div>`;
      }

      // ============================================
      // Processing Functions
      // ============================================

      /**
       * Processes a single credit card
       * @param {string} cc - Card data string
       */
      async function processCC(cc) {
        try {
          // Client-side validation first
          const validation = validateCard(cc);
          
          if (!validation.valid) {
            const errorMsg = validation.errors.join(', ');
            const cardType = validation.cardType;
            displayMessage('#danger-panel', createResultHTML('Invalid', cc, cardType, errorMsg));
            updateCounter(elements.dieCount, 1);
            return;
          }

          // Send to server for additional validation
          const response = await $.post(elements.form.attr('action'), { data: cc });
          const jsonResponse = JSON.parse(response);
          
          switch (jsonResponse.error) {
            case 1:
              displayMessage('#success-panel', jsonResponse.msg);
              updateCounter(elements.liveCount, 1);
              break;
            case 2:
              displayMessage('#danger-panel', jsonResponse.msg);
              updateCounter(elements.dieCount, 1);
              break;
            case 3:
              displayMessage('#warning-panel', jsonResponse.msg);
              updateCounter(elements.unknownCount, 1);
              break;
            case 4:
              showInfo(jsonResponse.msg, 'info');
              break;
            default:
              displayMessage('#warning-panel', createResultHTML('Unknown', cc, validation.cardType, 'Unexpected response'));
              updateCounter(elements.unknownCount, 1);
          }
        } catch (error) {
          console.error('Error processing CC:', error);
          displayMessage('#warning-panel', createResultHTML('Error', cc, null, 'Processing failed'));
          updateCounter(elements.unknownCount, 1);
        }
      }

      /**
       * Processes all credit cards in the list
       * @param {Array} ccList - Array of card data strings
       */
      async function processAllCC(ccList) {
        state.totalCards = ccList.length;
        state.processedCards = 0;
        
        elements.statTotal.text(state.totalCards);
        elements.statsSummary.show();
        elements.progressContainer.show();
        
        for (const cc of ccList) {
          if (state.shouldStop) {
            showInfo('Processing stopped by user', 'info');
            break;
          }
          
          await processCC(cc.trim());
          state.processedCards++;
          updateProgress(state.processedCards, state.totalCards);
          
          // Small delay between requests to prevent rate limiting
          await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        finishProcessing();
      }

      /**
       * Starts the processing state
       */
      function startProcessing() {
        state.isProcessing = true;
        state.shouldStop = false;
        
        elements.startBtn.prop('disabled', true).html('Processing...');
        elements.stopBtn.prop('disabled', false);
        elements.ccInput.prop('disabled', true);
        elements.progressBar.css('width', '0%');
      }

      /**
       * Finishes the processing state
       */
      function finishProcessing() {
        state.isProcessing = false;
        
        elements.startBtn.prop('disabled', false).html('START');
        elements.stopBtn.prop('disabled', true);
        elements.ccInput.prop('disabled', false).val('');
        
        const liveCount = parseInt(elements.liveCount.text(), 10) || 0;
        const dieCount = parseInt(elements.dieCount.text(), 10) || 0;
        const unknownCount = parseInt(elements.unknownCount.text(), 10) || 0;
        
        showInfo(`Completed! Live: ${liveCount} | Die: ${dieCount} | Unknown: ${unknownCount}`, 'success');
      }

      /**
       * Resets all counters and panels
       */
      function resetCounters() {
        elements.liveCount.text('0');
        elements.dieCount.text('0');
        elements.unknownCount.text('0');
        elements.successPanel.empty();
        elements.dangerPanel.empty();
        elements.warningPanel.empty();
        elements.statTotal.text('0');
        elements.statProcessed.text('0');
      }

      // ============================================
      // Event Handlers
      // ============================================

      elements.form.on('submit', function(e) {
        e.preventDefault();
        
        if (state.isProcessing) return;

        const ccList = elements.ccInput.val()
          .split('\n')
          .map(cc => cc.trim())
          .filter(cc => cc !== '');

        if (ccList.length === 0) {
          showInfo('Please enter at least one card number', 'error');
          return;
        }

        resetCounters();
        startProcessing();
        processAllCC(ccList);
      });

      elements.stopBtn.on('click', function() {
        state.shouldStop = true;
        $(this).prop('disabled', true).html('Stopping...');
      });

      // Reset stop button text when re-enabled
      elements.stopBtn.on('focus', function() {
        if (!$(this).prop('disabled')) {
          $(this).html('STOP');
        }
      });

    });
  </script>
</body>
</html>
