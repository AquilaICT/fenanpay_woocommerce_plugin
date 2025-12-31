# FenanPay WooCommerce Payment Gateway

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/plugins/)
[![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-Compatible-96588a.svg)](https://woocommerce.com/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

**Version:** 0.1.0  
**Contributors:** FenanPay Development Team  
**Requires:** WordPress 5.0+, WooCommerce 4.0+, PHP 7.4+  
**Tested up to:** WordPress 6.4, WooCommerce 8.0  
**License:** GPL v2 or later  
**Stable tag:** 0.1.0

A professional WooCommerce payment gateway integration for FenanPay's secure payment processing platform.

## Overview

The FenanPay WooCommerce Gateway provides seamless integration between your WooCommerce store and FenanPay's payment infrastructure. Built with enterprise-grade security and reliability in mind, this plugin enables merchants to accept payments through FenanPay's comprehensive payment processing platform.

### Core Features

- **Secure Payment Processing** - Industry-standard encryption and security protocols
- **Payment Intent API Integration** - Modern payment flow with enhanced security
- **Multi-Currency Support** - ETB and USD currency processing
- **Real-time Webhooks** - Instant payment status notifications with HMAC verification
- **Sandbox Environment** - Complete testing environment for development
- **Flexible Configuration** - Comprehensive admin settings panel
- **Order Management Integration** - Seamless WooCommerce order status synchronization
- **Mobile Optimized** - Responsive payment experience across all devices

## Installation

### Automatic Installation (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Search for "FenanPay WooCommerce Gateway"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate through **Plugins** menu in WordPress

### Post-Installation Setup

1. Navigate to **WooCommerce** → **Settings** → **Payments**
2. Locate **FenanPay** and click **Set up**
3. Configure your API credentials and preferences
4. Test the integration using sandbox mode

### System Requirements

| Component | Minimum Version | Recommended |
|-----------|----------------|-------------|
| WordPress | 5.0 | 6.0+ |
| WooCommerce | 4.0 | 7.0+ |
| PHP | 7.4 | 8.1+ |
| MySQL | 5.6 | 8.0+ |
| SSL Certificate | Required for production | |
| cURL Extension | Required | |
| JSON Extension | Required | |

## Configuration Guide

### 1. Gateway Activation

- Navigate to **WooCommerce** → **Settings** → **Payments**
- Locate **FenanPay** in the payment methods list
- Click **Set up** to access configuration options
- Toggle **Enable/Disable** to activate the gateway

### 2. API Configuration

| Setting | Description | Required |
|---------|-------------|----------|
| **API Key** | Your FenanPay API authentication key | ✅ |
| **Webhook Secret** | HMAC signature verification key | ⚠️ Recommended |
| **Test Mode** | Enable sandbox environment | 🧪 Development |

### 3. Payment Settings

- **Title**: Display name during checkout (default: "FenanPay")
- **Description**: Customer-facing payment method description
- **Currency Support**: ETB (Ethiopian Birr) and USD
- **Payment Flow**: External redirect to FenanPay checkout

### 4. Development & Testing

**Sandbox Configuration:**
- Enable **Test Mode** in plugin settings
- Use sandbox API credentials from FenanPay dashboard
- Test endpoint: `https://api.fenanpay.com/api/v1/payment/sandbox/intent`
- Production endpoint: `https://api.fenanpay.com/api/v1/payment/intent`

## Webhook Configuration

Webhooks enable real-time payment status synchronization between FenanPay and your WooCommerce store.

### Setup Instructions

1. **Access FenanPay Dashboard**
   - Log in to your FenanPay merchant account
   - Navigate to **Developer** → **Webhooks**

2. **Configure Webhook Endpoint**
   ```
   Webhook URL: https://yoursite.com/?wc-api=wc_fenanpay
   Alternative: https://yoursite.com/fenanpay-webhook
   ```

3. **Security Configuration**
   - **Authentication Method**: HMAC-SHA256
   - **Secret Key**: Copy from plugin settings
   - **Header Name**: `X-FenanPay-Signature`

4. **Event Subscription**
   - ✅ Payment Success
   - ✅ Payment Failed
   - ✅ Payment Expired
   - ✅ Payment Cancelled

### Webhook Security

The plugin implements HMAC-SHA256 signature verification to ensure webhook authenticity:

```php
$computed_signature = hash_hmac('sha256', $payload, $webhook_secret);
$is_valid = hash_equals($computed_signature, $received_signature);
```

## Technical Documentation

### API Integration Details

**Payment Intent Flow:**
1. Customer initiates checkout
2. Plugin creates payment intent via FenanPay API
3. Customer redirects to FenanPay checkout
4. Payment processing on FenanPay platform
5. Webhook notification updates order status
6. Customer returns to store confirmation page

**Supported Payment Methods:**
- Credit/Debit Cards (Visa, Mastercard)
- Mobile Money (M-Birr, HelloCash)
- Bank Transfers
- Digital Wallets

### Order Status Management

| FenanPay Status | WooCommerce Status | Action |
|----------------|-------------------|--------|
| `SUCCESS/PAID/COMPLETED` | `processing` | Payment Complete |
| `FAILED` | `failed` | Order Failed |
| `EXPIRED` | `cancelled` | Session Timeout |
| `PENDING` | `pending` | Awaiting Payment |

### Frequently Asked Questions

**Q: How do I obtain API credentials?**  
A: Contact FenanPay support at support@fenanpay.com or access your merchant dashboard.

**Q: Is the plugin compatible with my theme?**  
A: Yes, the plugin follows WooCommerce standards and works with any compliant theme.

**Q: Can I customize the payment flow?**  
A: The plugin uses FenanPay's hosted checkout for security. Customization options are available through hooks and filters.

**Q: What currencies are supported?**  
A: Currently supports ETB (Ethiopian Birr) and USD (US Dollar).

**Q: How do I troubleshoot payment issues?**  
A: Check WooCommerce logs, verify API credentials, and ensure webhook configuration is correct.

## Support & Resources

### Technical Support

- **Email**: support@fenanpay.com
- **Documentation**: [https://fenanpay.com/docs](https://fenanpay.com/docs)
- **Developer Portal**: [https://developer.fenanpay.com](https://developer.fenanpay.com)
- **Status Page**: [https://status.fenanpay.com](https://status.fenanpay.com)

### Community & Development

- **GitHub Repository**: [https://github.com/fenanpay/woocommerce-gateway](https://github.com/fenanpay/woocommerce-gateway)
- **Issue Tracker**: Report bugs and feature requests
- **Contributing**: Pull requests welcome
- **WordPress Plugin Directory**: [Coming Soon]

### Business Inquiries

- **Sales**: sales@fenanpay.com
- **Partnerships**: partners@fenanpay.com
- **Website**: [https://fenanpay.com](https://fenanpay.com)

## Development & Contributing

### Local Development Setup

```bash
# Clone repository
git clone https://github.com/fenanpay/woocommerce-gateway.git
cd woocommerce-gateway

# Install dependencies (if using Composer)
composer install

# Activate in WordPress
wp plugin activate fenanpay-woocommerce
```

### Code Standards

- **PHP**: WordPress Coding Standards
- **JavaScript**: WordPress JavaScript Standards  
- **CSS**: WordPress CSS Standards
- **Documentation**: PHPDoc standards

### Testing

```bash
# Run PHP tests
composer test

# Code quality checks
composer lint
```

## Changelog

### Version 0.1.0 (Initial Release)

**Features:**
- ✅ Payment Intent API integration
- ✅ Webhook support with HMAC verification
- ✅ Sandbox/Production environment switching
- ✅ Multi-currency support (ETB, USD)
- ✅ WooCommerce order status synchronization
- ✅ Comprehensive error handling
- ✅ Security best practices implementation

**Technical Implementation:**
- Modern PHP namespaced architecture
- RESTful API integration
- Secure webhook handling
- WordPress/WooCommerce compliance

## Security & Compliance

- **PCI DSS**: Compliant payment processing
- **Data Protection**: No sensitive data stored locally
- **Encryption**: TLS 1.2+ for all communications
- **Authentication**: API key-based authentication
- **Webhooks**: HMAC-SHA256 signature verification

## License

This plugin is licensed under the **GNU General Public License v2.0 or later**.

```
Copyright (C) 2024 FenanPay

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

**Disclaimer**: *FenanPay and associated trademarks are property of FenanPay. This plugin is developed to integrate with FenanPay services and follows their API specifications.*
