# WP PayPal Donations

A simple and secure WordPress plugin for collecting donations through PayPal with sandbox testing support.

## Features

- **Easy Setup**: Simple configuration through WordPress admin
- **PayPal Integration**: Secure payments through PayPal Standard
- **Sandbox Testing**: Full PayPal Sandbox support for testing
- **Multiple Currencies**: Support for USD, EUR, GBP, CAD, AUD, JPY
- **Recurring Donations**: Optional monthly recurring payments
- **Donation Tracking**: Complete donation history and analytics
- **Email Notifications**: Automatic thank you emails to donors and admin notifications
- **Responsive Design**: Mobile-friendly donation forms
- **Shortcode Support**: Easy integration with `[donation_form]` shortcode

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-paypal-donations/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Donations' > 'Settings' to configure the plugin

## Configuration

### Basic Settings

1. **PayPal Email**: Enter your PayPal account email address
2. **Sandbox Mode**: Enable for testing, disable for live donations
3. **Currency**: Choose your preferred currency
4. **Default Amount**: Set default donation amount
5. **Success Message**: Customize the thank you message
6. **Recurring Donations**: Enable/disable monthly recurring option

### PayPal Sandbox Testing

1. Create a PayPal Developer account at https://developer.paypal.com
2. Create Sandbox business and buyer accounts
3. Enable Sandbox Mode in plugin settings
4. Use Sandbox business email in PayPal Email field
5. Test donations with Sandbox buyer accounts
6. Disable Sandbox Mode for live donations

## Usage

### Adding Donation Forms

Use the shortcode to add donation forms anywhere on your site:

```
[donation_form]
```

### Shortcode Parameters

- `amount`: Default donation amount (default: from settings)
- `currency`: Currency code (default: from settings)  
- `title`: Form title (default: "Make a Donation")

Example:
```
[donation_form amount="50" title="Support Our Mission"]
```

### Quick Amount Buttons

The form includes preset amount buttons ($10, $25, $50, $100) that automatically adjust to your selected currency.

### Recurring Donations

When enabled, users can choose to make monthly recurring donations through PayPal's subscription service.

## Donation Management

### Admin Dashboard

Access donation data through 'Donations' in WordPress admin:

- **Dashboard**: View donation statistics and recent donations
- **Settings**: Configure PayPal and plugin settings

### Donation Data

The plugin tracks:
- Donor name and email
- Donation amount and currency
- Payment status (pending, completed, failed)
- PayPal transaction ID
- Donation date and time
- Full PayPal response data

### Email Notifications

Automatic emails are sent:
- **Thank you email** to donors with donation details
- **Notification email** to site admin for new donations

## PayPal Integration

### Payment Flow

1. User fills out donation form
2. Form data is validated and saved to database
3. User is redirected to PayPal for payment
4. PayPal processes payment and sends IPN (Instant Payment Notification)
5. Plugin verifies IPN and updates donation status
6. Email notifications are sent automatically

### Security

- All form data is sanitized and validated
- PayPal IPN verification for payment confirmation
- Nonce verification for AJAX requests
- Secure database queries with prepared statements

## File Structure

```
wp-paypal-donations/
├── wp-paypal-donations.php     # Main plugin file
├── admin/
│   ├── settings-page.php       # Settings page template
│   └── donations-list.php      # Donations dashboard
├── templates/
│   └── donation-form.php       # Donation form template
├── assets/
│   ├── css/
│   │   └── frontend.css        # Frontend styles
│   └── js/
│       └── frontend.js         # Frontend JavaScript
└── README.md                   # This file
```

## Customization

### Styling

The plugin includes comprehensive CSS that can be overridden in your theme:

- `.wpd-donation-form-container`: Main form container
- `.wpd-donate-btn`: Donation button
- `.wpd-quick-amount`: Quick amount buttons
- `.wpd-success-message`: Success overlay
- `.wpd-error-message`: Error overlay

### Hooks and Filters

The plugin uses standard WordPress hooks and can be extended through custom code.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- PayPal Business account
- SSL certificate (recommended for live sites)

## Support

This plugin includes:
- Comprehensive error handling
- Detailed logging of PayPal responses  
- Clear user feedback for all actions
- Mobile-responsive design
- Cross-browser compatibility

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- PayPal Standard integration
- Sandbox testing support
- Donation tracking and management
- Email notifications
- Responsive design
- Shortcode support
- Multiple currency support
- Recurring donations option