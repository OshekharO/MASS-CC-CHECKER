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
  <div class="fs-3 fw-bold mb-5 text-uppercase mx-auto text-center text-light">Credit Card Checker </div>
  <form method="post" action="api.php" role="form" id="form">
    <div class="box-body">
      <div class="box-content"> <label for="cc" class="form-label fs-6 font-monospace badge bg-danger text-light">Card Numbers</label>
        <div> <textarea class="form-control" rows="10" id="cc" name="cc" title="53012724539xxxxx|05|2022|653" placeholder="53012724539xxxxx|05|2022|653" required></textarea> </div>
        <div class="button text-center mb-3 mt-3"> <button type="submit" name="valid" class="btn btn-outline-success text-light">START</button> <button type="button" id="stop" class="btn btn-outline-danger text-light" disabled>STOP</button> </div>
      </div>
    </div> <!-- Info success -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">Live - <span class="badge bg-success live">0</span></h3>
    </div>
    <div class="box-body">
      <div class="box-content alert alert-success">
        <div class="panel-body success"></div>
      </div>
    </div> <!-- Info error -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">Die - <span class="badge bg-danger die">0</span></h3>
    </div>
    <div class="box-body">
      <div class="box-content alert alert-danger">
        <div class="panel-body danger"></div>
      </div>
    </div> <!-- Info unknown -->
    <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">Unknown - <span class="badge bg-warning unknown">0</span></h3>
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
        const $ccInput = $('#cc');
        const $form = $('#form');
        const $live = $('.live');
        const $die = $('.die');
        const $unknown = $('.unknown');
        const $info = $('.info');
        
        let isProcessing = false;
        let shouldStop = false;
    
        function updateCounter($element, value) {
            $element.text(parseInt($element.text()) + value);
        }
    
        function displayMessage(selector, message) {
            $(selector).prepend(message);
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
                // Pre-check with Luhn algorithm
                if (!isValidLuhn(cc.replace(/\D/g, ''))) {
                    displayMessage('.danger', `<div><b style='color:#FF0000;'>Invalid</b> | ${cc} (Failed Luhn check)</div>`);
                    updateCounter($die, 1);
                    return;
                }
    
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
            }
        }
    
        async function processAllCC(ccList) {
            for (let cc of ccList) {
                if (shouldStop) break;
                await processCC(cc.trim());
                await new Promise(resolve => setTimeout(resolve, 100)); // Small delay between requests
            }
            finishProcessing();
        }
    
        function startProcessing() {
            isProcessing = true;
            shouldStop = false;
            $validButton.attr('disabled', true);
            $stopButton.attr('disabled', false);
            $ccInput.attr('disabled', true);
        }
    
        function finishProcessing() {
            isProcessing = false;
            $validButton.attr('disabled', false);
            $stopButton.attr('disabled', true);
            $ccInput.attr('disabled', false).val('');
        }
    
        $form.submit(function(e) {
            e.preventDefault();
            if (isProcessing) return;
    
            const ccList = $ccInput.val().split('\n').filter(cc => cc.trim() !== '');
            if (ccList.length === 0) {
                $info.show().html('<b>Error: No valid input</b>');
                return;
            }
    
            startProcessing();
            processAllCC(ccList);
        });
    
        $stopButton.click(function() {
            shouldStop = true;
        });
    });
  </script>
</body>
</html>
