# WP PayPal Donations Plugin

A comprehensive WordPress plugin for collecting donations through PayPal with both modern REST API and legacy Standard integration, complete with Gutenberg block support, automated testing, and a professional admin interface.

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)
![PayPal](https://img.shields.io/badge/PayPal-REST%20API%20%26%20Standard-yellow)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-red)

## 🚀 Features

### Core Functionality
- **Dual PayPal Integration**: Modern REST API + Legacy Standard support
- **Gutenberg Block**: Visual donation form builder with live preview
- **Shortcode Support**: `[donation_form]` with customizable parameters
- **Recurring Donations**: Monthly subscription support
- **Multi-Currency**: USD, EUR, GBP, CAD, AUD, JPY
- **Sandbox Testing**: Full PayPal sandbox environment support
- **Mobile Responsive**: Works perfectly on all devices

### Admin Features
- **Donation Dashboard**: Analytics, totals, and donation history
- **Settings Panel**: Easy PayPal configuration with API credentials
- **Email Notifications**: Automated thank you and admin alerts
- **Security**: Comprehensive sanitization, validation, and nonce protection
- **Database Tracking**: Complete donation history with transaction details

### Developer Features
- **Comprehensive Test Suite**: PHPUnit, Jest, integration tests
- **Modern Code Standards**: PSR-4 autoloading, WordPress coding standards
- **Extensible Architecture**: Hooks, filters, and class-based design
- **REST API Integration**: Modern PayPal payment processing
- **CI/CD Ready**: GitHub Actions compatible

## 📦 Installation

### WordPress Admin Installation
1. Download the latest release
2. Upload to `/wp-content/plugins/wp-paypal-donations/`
3. Activate through WordPress admin → Plugins
4. Configure PayPal settings in Donations → Settings

### Development Installation
```bash
git clone https://github.com/fahdi/wp-paypal-donations.git
cd wp-paypal-donations
composer install
npm install
```

## ⚙️ Configuration

### PayPal REST API (Recommended)
1. Create PayPal Developer account at [developer.paypal.com](https://developer.paypal.com)
2. Create new App in PayPal Developer Dashboard
3. Copy Client ID and Client Secret
4. In WordPress admin → Donations → Settings:
   - Select "PayPal REST API"
   - Enter Client ID and Client Secret
   - Enable Sandbox Mode for testing

### PayPal Standard (Legacy)
1. In WordPress admin → Donations → Settings:
   - Select "PayPal Standard"
   - Enter PayPal business email
   - Enable Sandbox Mode for testing

## 🎯 Usage

### Shortcode
Add donation forms anywhere using the shortcode:

```php
[donation_form]

// With custom parameters
[donation_form amount="50" title="Support Our Mission" currency="USD"]
```

### Gutenberg Block
1. Add new block in Gutenberg editor
2. Search for "Donation Form"
3. Customize settings in sidebar panel
4. Live preview in editor

### Template Integration
```php
// In theme templates
echo do_shortcode('[donation_form amount="25"]');

// Or use the plugin method directly
if (class_exists('WP_PayPal_Donations')) {
    $plugin = new WP_PayPal_Donations();
    echo $plugin->donation_form_shortcode(['amount' => '25']);
}
```

## 🧪 Testing

The plugin includes comprehensive automated tests:

### PHP Tests (PHPUnit)
```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:integration

# Generate coverage report
composer test:coverage
```

### JavaScript Tests (Jest)
```bash
# Install dependencies
cd tests/js && npm install

# Run tests
npm test

# Watch mode
npm run test:watch

# Coverage report
npm run test:coverage
```

### Code Quality
```bash
# PHP Code Sniffer
composer lint

# Auto-fix issues
composer lint:fix
```

## 🏗️ Architecture

### File Structure
```
wp-paypal-donations/
├── wp-paypal-donations.php          # Main plugin file
├── admin/                           # Admin interface
│   ├── settings-page.php           # Settings form
│   └── donations-list.php          # Dashboard
├── assets/                          # Frontend assets
│   ├── css/frontend.css            # Styles
│   └── js/frontend.js              # JavaScript
├── blocks/donation-form/            # Gutenberg block
│   ├── block.json                  # Block configuration
│   ├── src/index.js                # Block editor code
│   └── build/                      # Compiled assets
├── includes/                        # Core classes
│   └── class-paypal-rest-api.php   # PayPal API handler
├── templates/                       # Template files
│   └── donation-form.php           # Form template
└── tests/                          # Test suites
    ├── unit/                       # Unit tests
    ├── integration/                # Integration tests
    └── js/                         # JavaScript tests
```

### Database Schema
```sql
CREATE TABLE wp_donations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    donor_name varchar(100) NOT NULL,
    donor_email varchar(100) NOT NULL,
    amount decimal(10,2) NOT NULL,
    currency varchar(3) NOT NULL DEFAULT 'USD',
    payment_status varchar(50) NOT NULL,
    transaction_id varchar(100),
    paypal_response text,
    donation_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 🔧 Development

### Contributing
1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Run tests: `composer test && cd tests/js && npm test`
5. Push to branch: `git push origin feature/amazing-feature`
6. Open Pull Request

### Coding Standards
- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Write comprehensive tests for new features
- Document all public methods
- Use semantic versioning

### Hooks and Filters

#### Actions
```php
// Before donation processing
do_action('wpd_before_process_donation', $donation_data);

// After successful payment
do_action('wpd_donation_completed', $donation_id);

// Before sending emails
do_action('wpd_before_send_notifications', $donation_id);
```

#### Filters
```php
// Modify donation form output
apply_filters('wpd_donation_form_html', $html, $attributes);

// Customize email templates
apply_filters('wpd_donor_email_message', $message, $donation);

// PayPal arguments
apply_filters('wpd_paypal_args', $args, $donation_data);
```

## 🔒 Security

- All user inputs are sanitized and validated
- Nonce verification for form submissions
- Prepared statements for database queries
- PayPal IPN verification for payment validation
- Secure credential storage
- CSRF protection

## 📊 Analytics

### Available Metrics
- Total donations raised
- Number of donations
- Average donation amount
- Donation history with details
- Payment status tracking
- Currency breakdown

### Export Options
- CSV export (planned)
- PDF reports (planned)
- API endpoints (planned)

## 🌐 Compatibility

- **WordPress**: 6.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+
- **Browsers**: Chrome, Firefox, Safari, Edge
- **PayPal**: REST API v2, Standard (legacy)

## 📝 Changelog

### Version 1.0.0
- Initial release
- PayPal Standard integration
- Gutenberg block support
- Comprehensive test suite
- PayPal REST API integration
- Sandbox testing support
- Email notifications
- Admin dashboard
- Multi-currency support
- Recurring donations

## 📄 License

This project is licensed under the GPL v2 License - see the [LICENSE](LICENSE) file for details.

## 🤝 Support

- **Documentation**: [Plugin Documentation](docs/)
- **Issues**: [GitHub Issues](https://github.com/fahdi/wp-paypal-donations/issues)
- **Discussions**: [GitHub Discussions](https://github.com/fahdi/wp-paypal-donations/discussions)

## 🙏 Acknowledgments

- PayPal for comprehensive API documentation
- WordPress community for coding standards
- Contributors and testers

---

**Made with ❤️ for the WordPress community**