jQuery(document).ready(function($) {
    
    // Quick amount buttons
    $('.wpd-quick-amount').on('click', function(e) {
        e.preventDefault();
        var amount = $(this).data('amount');
        $('#wpd-amount').val(amount);
        $('.wpd-quick-amount').removeClass('active');
        $(this).addClass('active');
    });
    
    // Form submission
    $('#wpd-donation-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.wpd-donate-btn');
        var btnText = submitBtn.find('.wpd-btn-text');
        var btnLoading = submitBtn.find('.wpd-btn-loading');
        var messageDiv = $('#wpd-message');
        
        // Validate form
        var donorName = form.find('input[name="donor_name"]').val().trim();
        var donorEmail = form.find('input[name="donor_email"]').val().trim();
        var amount = parseFloat(form.find('input[name="amount"]').val());
        
        if (!donorName || !donorEmail || !amount || amount <= 0) {
            showMessage('Please fill in all required fields with valid information.', 'error');
            return;
        }
        
        // Validate email
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(donorEmail)) {
            showMessage('Please enter a valid email address.', 'error');
            return;
        }
        
        // Show loading state
        submitBtn.prop('disabled', true);
        btnText.hide();
        btnLoading.show();
        
        // Prepare form data
        var formData = {
            action: 'process_donation',
            donor_name: donorName,
            donor_email: donorEmail,
            amount: amount,
            recurring: form.find('input[name="recurring"]').is(':checked'),
            nonce: form.find('input[name="wpd_nonce"]').val()
        };
        
        // Submit to WordPress AJAX
        $.ajax({
            url: wpd_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.redirect_url) {
                    // Redirect to PayPal
                    showMessage('Redirecting to PayPal...', 'success');
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    showMessage('Something went wrong. Please try again.', 'error');
                    resetButton();
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
                resetButton();
            }
        });
        
        function resetButton() {
            submitBtn.prop('disabled', false);
            btnText.show();
            btnLoading.hide();
        }
    });
    
    // Show success/error messages
    function showMessage(message, type) {
        var messageDiv = $('#wpd-message');
        messageDiv.removeClass('success error').addClass(type);
        messageDiv.text(message).fadeIn();
        
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 5000);
    }
    
    // Auto-hide success/error overlays
    if ($('.wpd-success-message, .wpd-error-message').length) {
        setTimeout(function() {
            $('.wpd-success-message, .wpd-error-message').fadeOut();
        }, 5000);
    }
    
    // Allow clicking overlay to dismiss
    $(document).on('click', '.wpd-success-message, .wpd-error-message', function() {
        $(this).fadeOut();
    });
    
    // Update quick amount buttons with current currency
    var currency = wpd_ajax.currency || 'USD';
    var currencySymbol = window.wpdCurrencySymbols ? window.wpdCurrencySymbols[currency] : '$';
    
    $('.wpd-quick-amount').each(function() {
        var amount = $(this).data('amount');
        $(this).text(currencySymbol + amount);
    });
});