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
  
  <?php
  // Load configuration
  if (file_exists('config.php')) {
      require_once 'config.php';
  }
  // Set default if not defined
  if (!defined('PROCESSING_DELAY_MS')) {
      define('PROCESSING_DELAY_MS', 100);
  }
  ?>
  
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
  <div class="fs-3 fw-bold mb-5 text-uppercase mx-auto text-center text-light">Credit Card Checker </div>
  <form method="post" action="api.php" role="form" id="form">
    <div class="box-body">
      <div class="box-content"> <label for="cc" class="form-label fs-6 font-monospace badge bg-danger text-light">Card Numbers</label>
        <div> <textarea class="form-control" rows="10" id="cc" name="cc" title="53012724539xxxxx|05|2022|653" placeholder="53012724539xxxxx|05|2022|653" required></textarea> </div>
        <div class="button text-center mb-3 mt-3"> 
          <button type="submit" name="valid" class="btn btn-outline-success text-light">START</button> 
          <button type="button" id="stop" class="btn btn-outline-danger text-light" disabled>STOP</button> 
          <button type="button" id="clear" class="btn btn-outline-warning text-light">CLEAR</button> 
          <button type="button" id="export" class="btn btn-outline-info text-light">EXPORT</button> 
        </div>
        <!-- Progress Bar -->
        <div class="progress mb-3" style="display:none;" id="progress-container">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" id="progress-bar" style="width: 0%"></div>
        </div>
        <!-- Statistics Summary -->
        <div class="alert alert-info mb-3" style="display:none;" id="stats-summary">
          <strong>Processing Statistics:</strong>
          <span id="stats-text"></span>
        </div>
      </div>
    </div> <!-- Info success -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">
        Live - <span class="badge bg-success live">0</span>
        <button type="button" class="btn btn-sm btn-outline-light float-end copy-btn" data-target="success" title="Copy Live Results">📋</button>
      </h3>
    </div>
    <div class="box-body">
      <div class="box-content alert alert-success">
        <div class="panel-body success"></div>
      </div>
    </div> <!-- Info error -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">
        Die - <span class="badge bg-danger die">0</span>
        <button type="button" class="btn btn-sm btn-outline-light float-end copy-btn" data-target="danger" title="Copy Die Results">📋</button>
      </h3>
    </div>
    <div class="box-body">
      <div class="box-content alert alert-danger">
        <div class="panel-body danger"></div>
      </div>
    </div> <!-- Info unknown -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">
        Unknown - <span class="badge bg-warning unknown">0</span>
        <button type="button" class="btn btn-sm btn-outline-light float-end copy-btn" data-target="warning" title="Copy Unknown Results">📋</button>
      </h3>
    </div>
    <div class="box-body">
      <div class="box-content alert alert-warning">
        <div class="panel-body warning"></div>
      </div>
    </div>
  </form>
  <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
  <script>
    $(document).ready(function() {
        const $validButton = $('button[name="valid"]');
        const $stopButton = $('button[id="stop"]');
        const $clearButton = $('button[id="clear"]');
        const $exportButton = $('button[id="export"]');
        const $ccInput = $('#cc');
        const $form = $('#form');
        const $live = $('.live');
        const $die = $('.die');
        const $unknown = $('.unknown');
        const $info = $('.info');
        const $progressContainer = $('#progress-container');
        const $progressBar = $('#progress-bar');
        const $statsContainer = $('#stats-summary');
        const $statsText = $('#stats-text');
        
        let isProcessing = false;
        let shouldStop = false;
        let totalCards = 0;
        let processedCards = 0;
        let startTime = 0;
        
        // Get processing delay from config
        const processingDelay = <?php echo PROCESSING_DELAY_MS; ?>;
    
        function updateCounter($element, value) {
            $element.text(parseInt($element.text()) + value);
        }
    
        function displayMessage(selector, message) {
            $(selector).prepend(message);
        }
        
        function updateProgress() {
            if (totalCards === 0) return;
            
            const percentage = Math.round((processedCards / totalCards) * 100);
            $progressBar.css('width', percentage + '%');
            $progressBar.text(percentage + '%');
            
            // Update statistics
            const elapsed = Math.round((Date.now() - startTime) / 1000);
            const rate = processedCards / elapsed || 0;
            const remaining = totalCards - processedCards;
            const eta = remaining > 0 ? Math.round(remaining / rate) : 0;
            
            $statsText.html(`
                Processed: ${processedCards}/${totalCards} | 
                Elapsed: ${elapsed}s | 
                Rate: ${rate.toFixed(1)}/s | 
                ETA: ${eta}s
            `);
        }
    
        // Improved Luhn algorithm check
        function isValidLuhn(number) {
            let sum = 0;
            let isEven = false;
            
            for (let i = number.length - 1; i >= 0; i--) {
                let digit = parseInt(number.charAt(i), 10);
                
                if (isEven) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
                isEven = !isEven;
            }
            
            return (sum % 10) === 0;
        }
    
        async function processCC(cc) {
            try {
                const response = await $.post($form.attr('action'), { data: cc });
                const jsonResponse = JSON.parse(response);
                
                switch(jsonResponse.error) {
                    case 1:
                        displayMessage('.success', jsonResponse.msg);
                        updateCounter($live, 1);
                        break;
                    case 2:
                        displayMessage('.danger', jsonResponse.msg);
                        updateCounter($die, 1);
                        break;
                    case 3:
                        displayMessage('.warning', jsonResponse.msg);
                        updateCounter($unknown, 1);
                        break;
                    case 4:
                        $info.show().prepend(jsonResponse.msg + '<br>');
                        break;
                }
            } catch (error) {
                console.error('Error processing CC:', error);
                displayMessage('.danger', `<div><b style='color:#FF0000;'>Error</b> | ${cc} (Processing failed)</div>`);
                updateCounter($unknown, 1);
            } finally {
                processedCards++;
                updateProgress();
            }
        }
    
        async function processAllCC(ccList) {
            for (let cc of ccList) {
                if (shouldStop) break;
                await processCC(cc.trim());
                await new Promise(resolve => setTimeout(resolve, processingDelay));
            }
            finishProcessing();
        }
    
        function startProcessing() {
            isProcessing = true;
            shouldStop = false;
            $validButton.attr('disabled', true);
            $stopButton.attr('disabled', false);
            $ccInput.attr('disabled', true);
            $clearButton.attr('disabled', true);
            $exportButton.attr('disabled', true);
            $progressContainer.show();
            $statsContainer.show();
            startTime = Date.now();
        }
    
        function finishProcessing() {
            isProcessing = false;
            $validButton.attr('disabled', false);
            $stopButton.attr('disabled', true);
            $ccInput.attr('disabled', false).val('');
            $clearButton.attr('disabled', false);
            $exportButton.attr('disabled', false);
            $progressBar.css('width', '100%').text('100%');
        }
        
        function clearResults() {
            $('.success, .danger, .warning').empty();
            $live.text('0');
            $die.text('0');
            $unknown.text('0');
            $progressBar.css('width', '0%').text('');
            $progressContainer.hide();
            $statsContainer.hide();
            processedCards = 0;
            totalCards = 0;
        }
        
        function exportResults() {
            let exportText = '=== CREDIT CARD CHECKER RESULTS ===\n\n';
            exportText += `Generated: ${new Date().toLocaleString()}\n\n`;
            
            // Live cards
            const liveCount = parseInt($live.text());
            if (liveCount > 0) {
                exportText += `LIVE CARDS (${liveCount}):\n`;
                exportText += '================================\n';
                $('.success div').each(function() {
                    exportText += $(this).text().replace(/\s+/g, ' ').trim() + '\n';
                });
                exportText += '\n';
            }
            
            // Die cards
            const dieCount = parseInt($die.text());
            if (dieCount > 0) {
                exportText += `DIE CARDS (${dieCount}):\n`;
                exportText += '================================\n';
                $('.danger div').each(function() {
                    exportText += $(this).text().replace(/\s+/g, ' ').trim() + '\n';
                });
                exportText += '\n';
            }
            
            // Unknown cards
            const unknownCount = parseInt($unknown.text());
            if (unknownCount > 0) {
                exportText += `UNKNOWN CARDS (${unknownCount}):\n`;
                exportText += '================================\n';
                $('.warning div').each(function() {
                    exportText += $(this).text().replace(/\s+/g, ' ').trim() + '\n';
                });
                exportText += '\n';
            }
            
            exportText += `\nTOTAL PROCESSED: ${liveCount + dieCount + unknownCount}\n`;
            
            // Create download link
            const blob = new Blob([exportText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `cc_checker_results_${Date.now()}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        function copyResults(target) {
            let text = '';
            $(`.${target} div`).each(function() {
                text += $(this).text().replace(/\s+/g, ' ').trim() + '\n';
            });
            
            if (text) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Results copied to clipboard!');
                }).catch(function(err) {
                    console.error('Failed to copy:', err);
                    // Fallback method
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    alert('Results copied to clipboard!');
                });
            } else {
                alert('No results to copy!');
            }
        }
    
        $form.submit(function(e) {
            e.preventDefault();
            if (isProcessing) return;
    
            const ccList = $ccInput.val().split('\n').filter(cc => cc.trim() !== '');
            if (ccList.length === 0) {
                alert('Error: No valid input');
                return;
            }
            
            totalCards = ccList.length;
            processedCards = 0;
    
            startProcessing();
            processAllCC(ccList);
        });
    
        $stopButton.click(function() {
            shouldStop = true;
            finishProcessing();
        });
        
        $clearButton.click(function() {
            if (confirm('Are you sure you want to clear all results?')) {
                clearResults();
            }
        });
        
        $exportButton.click(function() {
            exportResults();
        });
        
        // Copy button handlers
        $('.copy-btn').click(function() {
            const target = $(this).data('target');
            copyResults(target);
        });
    });
  </script>
</body>
</html>
