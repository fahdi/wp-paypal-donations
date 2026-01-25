/**
 * Test setup file for Jest
 * Sets up the testing environment and mocks
 */

// Mock jQuery
global.jQuery = require('./mocks/jquery-mock');
global.$ = global.jQuery;

// Mock WordPress AJAX object
global.wpd_ajax = {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'test_nonce_123',
    processing_text: 'Processing...',
    currency: 'USD'
};

// Mock currency symbols
global.window.wpdCurrencySymbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'CAD': '$',
    'AUD': '$',
    'JPY': '¥'
};

// Mock window location
delete window.location;
window.location = {
    href: 'http://localhost',
    origin: 'http://localhost',
    protocol: 'http:',
    host: 'localhost'
};

// Mock console methods for cleaner test output
global.console = {
    ...console,
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn(),
    info: jest.fn()
};

// Add custom matchers if needed
expect.extend({
    toBeValidEmail(received) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const pass = emailRegex.test(received);
        
        return {
            message: () =>
                `expected ${received} ${pass ? 'not ' : ''}to be a valid email`,
            pass
        };
    }
});

// Reset DOM before each test
beforeEach(() => {
    document.body.innerHTML = '';
    jest.clearAllMocks();
});