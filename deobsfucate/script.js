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
