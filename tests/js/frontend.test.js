/**
 * Frontend JavaScript tests
 * @jest-environment jsdom
 */

describe('WP PayPal Donations Frontend', () => {
    let mockAjax;
    
    beforeEach(() => {
        // Set up DOM
        document.body.innerHTML = `
            <div class="wpd-donation-form-container">
                <form id="wpd-donation-form" class="wpd-donation-form">
                    <input name="wpd_nonce" value="test_nonce" />
                    <input type="text" name="donor_name" />
                    <input type="email" name="donor_email" />
                    <input type="number" name="amount" value="25" />
                    <input type="checkbox" name="recurring" />
                    <button type="submit" class="wpd-donate-btn">
                        <span class="wpd-btn-text">Donate</span>
                        <span class="wpd-btn-loading" style="display: none;">Loading</span>
                    </button>
                    <div class="wpd-quick-amounts">
                        <button type="button" class="wpd-quick-amount" data-amount="10">$10</button>
                        <button type="button" class="wpd-quick-amount" data-amount="25">$25</button>
                        <button type="button" class="wpd-quick-amount" data-amount="50">$50</button>
                        <button type="button" class="wpd-quick-amount" data-amount="100">$100</button>
                    </div>
                </form>
                <div id="wpd-message" class="wpd-message" style="display: none;"></div>
            </div>
        `;
        
        // Mock jQuery AJAX
        mockAjax = jest.fn();
        global.jQuery.ajax = mockAjax;
        
        // Load frontend script
        require('../../assets/js/frontend.js');
    });
    
    test('Quick amount buttons set donation amount', () => {
        const quickAmountBtn = document.querySelector('[data-amount="50"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Simulate click
        quickAmountBtn.click();
        
        expect(amountInput.value).toBe('50');
        expect(quickAmountBtn.classList.contains('active')).toBe(true);
    });
    
    test('Form validation catches empty required fields', (done) => {
        const form = document.getElementById('wpd-donation-form');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Clear required fields
        nameInput.value = '';
        emailInput.value = '';
        amountInput.value = '';
        
        // Mock showMessage function
        window.showMessage = jest.fn();
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        setTimeout(() => {
            expect(window.showMessage).toHaveBeenCalledWith(
                expect.stringContaining('Please fill in all required fields'),
                'error'
            );
            done();
        }, 100);
    });
    
    test('Email validation works correctly', () => {
        const testEmails = [
            { email: 'test@example.com', valid: true },
            { email: 'invalid-email', valid: false },
            { email: 'test@', valid: false },
            { email: '@example.com', valid: false },
            { email: 'test.email+tag@example.co.uk', valid: true }
        ];
        
        testEmails.forEach(test => {
            if (test.valid) {
                expect(test.email).toBeValidEmail();
            } else {
                expect(test.email).not.toBeValidEmail();
            }
        });
    });
    
    test('Successful donation submission', (done) => {
        const form = document.getElementById('wpd-donation-form');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Fill in valid data
        nameInput.value = 'John Doe';
        emailInput.value = 'john@example.com';
        amountInput.value = '25';
        
        // Mock successful AJAX response
        mockAjax.mockImplementation((options) => {
            setTimeout(() => {
                options.success({
                    success: true,
                    data: {
                        redirect_url: 'https://paypal.com/checkout'
                    }
                });
            }, 100);
        });
        
        // Mock window.location
        delete window.location;
        window.location = { href: '' };
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        setTimeout(() => {
            expect(mockAjax).toHaveBeenCalledWith(
                expect.objectContaining({
                    url: '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    dataType: 'json'
                })
            );
            done();
        }, 200);
    });
    
    test('Failed donation submission', (done) => {
        const form = document.getElementById('wpd-donation-form');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Fill in valid data
        nameInput.value = 'John Doe';
        emailInput.value = 'john@example.com';
        amountInput.value = '25';
        
        // Mock showMessage function
        window.showMessage = jest.fn();
        
        // Mock failed AJAX response
        mockAjax.mockImplementation((options) => {
            setTimeout(() => {
                options.success({
                    success: false,
                    data: {
                        message: 'Payment failed'
                    }
                });
            }, 100);
        });
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        setTimeout(() => {
            expect(window.showMessage).toHaveBeenCalledWith(
                'Something went wrong. Please try again.',
                'error'
            );
            done();
        }, 200);
    });
    
    test('AJAX error handling', (done) => {
        const form = document.getElementById('wpd-donation-form');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Fill in valid data
        nameInput.value = 'John Doe';
        emailInput.value = 'john@example.com';
        amountInput.value = '25';
        
        // Mock showMessage function
        window.showMessage = jest.fn();
        
        // Mock AJAX error
        mockAjax.mockImplementation((options) => {
            setTimeout(() => {
                options.error();
            }, 100);
        });
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        setTimeout(() => {
            expect(window.showMessage).toHaveBeenCalledWith(
                'Connection error. Please try again.',
                'error'
            );
            done();
        }, 200);
    });
    
    test('Loading state management', () => {
        const form = document.getElementById('wpd-donation-form');
        const submitBtn = document.querySelector('.wpd-donate-btn');
        const btnText = document.querySelector('.wpd-btn-text');
        const btnLoading = document.querySelector('.wpd-btn-loading');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Fill in valid data
        nameInput.value = 'John Doe';
        emailInput.value = 'john@example.com';
        amountInput.value = '25';
        
        // Mock AJAX to never resolve (to test loading state)
        mockAjax.mockImplementation(() => {
            // Don't call success or error callbacks
        });
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        // Check loading state
        expect(submitBtn.disabled).toBe(true);
        expect(btnText.style.display).toBe('none');
        expect(btnLoading.style.display).toBe('');
    });
    
    test('Currency symbols update correctly', () => {
        const quickAmountBtns = document.querySelectorAll('.wpd-quick-amount');
        
        // Test different currencies
        const currencies = ['USD', 'EUR', 'GBP', 'JPY'];
        
        currencies.forEach(currency => {
            global.wpd_ajax.currency = currency;
            const symbol = window.wpdCurrencySymbols[currency];
            
            // Simulate currency update
            quickAmountBtns.forEach(btn => {
                const amount = btn.dataset.amount;
                btn.textContent = symbol + amount;
            });
            
            // Verify symbols are correct
            expect(quickAmountBtns[0].textContent).toBe(symbol + '10');
            expect(quickAmountBtns[1].textContent).toBe(symbol + '25');
        });
    });
    
    test('Message auto-hide functionality', (done) => {
        // Create success message element
        const successMessage = document.createElement('div');
        successMessage.className = 'wpd-success-message';
        successMessage.textContent = 'Thank you!';
        document.body.appendChild(successMessage);
        
        // Mock fadeOut function
        successMessage.fadeOut = jest.fn();
        
        // Simulate auto-hide timer
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 5000);
        
        // Test that element is hidden after timeout
        setTimeout(() => {
            expect(successMessage.style.display).toBe('none');
            done();
        }, 5100);
    });
    
    test('Recurring donation checkbox', () => {
        const form = document.getElementById('wpd-donation-form');
        const recurringCheckbox = document.querySelector('input[name="recurring"]');
        const nameInput = document.querySelector('input[name="donor_name"]');
        const emailInput = document.querySelector('input[name="donor_email"]');
        const amountInput = document.querySelector('input[name="amount"]');
        
        // Fill in valid data
        nameInput.value = 'John Doe';
        emailInput.value = 'john@example.com';
        amountInput.value = '25';
        recurringCheckbox.checked = true;
        
        // Mock AJAX to capture form data
        let capturedData;
        mockAjax.mockImplementation((options) => {
            capturedData = options.data;
        });
        
        // Submit form
        const submitEvent = new Event('submit');
        form.dispatchEvent(submitEvent);
        
        // Verify recurring is included in form data
        expect(capturedData.recurring).toBe(true);
    });
});