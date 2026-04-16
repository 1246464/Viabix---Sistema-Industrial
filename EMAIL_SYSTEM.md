# Email System & Verification

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Configuration](#configuration)
4. [Usage Patterns](#usage-patterns)
5. [Email Templates](#email-templates)
6. [Testing & Validation](#testing--validation)
7. [Deployment Checklist](#deployment-checklist)
8. [Troubleshooting](#troubleshooting)
9. [API Reference](#api-reference)

---

## Overview

This document describes the comprehensive email system implemented for the Viabix SaaS platform. The system handles all user communications including verification, password resets, payment confirmations, and invoices.

### Key Features
- **Status**: ✅ Implemented & Integrated
- **Version**: 1.0
- **Multi-Provider Support**: SMTP, SendGrid, Mailgun, AWS SES
- **Template System**: File-based or database-driven
- **Queue System**: Async email delivery via job queue
- **Error Tracking**: Sentry integration for delivery failures
- **Built-in Templates**: Welcome, password reset, verification, invoices

---

## Architecture

### Core Components

#### 1. `api/email.php` - Email Service Module
**Purpose**: Centralized email handling (~350 lines)  
**Features**:
- Multi-provider abstraction (SMTP, SendGrid, Mailgun)
- Template rendering with variable interpolation
- Queue system for async delivery
- Built-in template helpers
- Error logging to Sentry

**Key Functions**:

| Function | Purpose |
|----------|---------|
| `viabixSendEmail()` | Send email with template |
| `viabixQueueEmail()` | Queue email for async delivery |
| `viabixProcessEmailQueue()` | Process queued emails (cron job) |
| `viabixSendWelcomeEmail()` | Send welcome email |
| `viabixSendPasswordResetEmail()` | Send password reset |
| `viabixSendVerificationEmail()` | Send email verification |
| `viabixSendPaymentConfirmationEmail()` | Send payment receipt |
| `viabixSendInvoiceEmail()` | Send invoice |

#### 2. Email Templates Directory
**Location**: `templates/email/`  
**Files**:
- `welcome.php` - New user welcome
- `password_reset.php` - Password recovery
- `email_verification.php` - Email confirmation
- `payment_confirmation.php` - Payment receipt
- `invoice.php` - Invoice delivery

**Format**: Each returns array with `['subject', 'html', 'text', ...]`

#### 3. Integration Points
| File | Addition | Purpose |
|------|----------|---------|
| `api/config.php` | `require 'email.php'` | Auto-initialize |
| `.env.example` | Email config variables | Configuration template |
| Database | `email_queue` table | Queue storage (optional) |

#### 4. Email Flow
```
User Action (signup, login, etc)
    ↓
Call viabixSendEmail() or viabixQueueEmail()
    ↓
Load Template (database or file)
    ↓
Render Variables ({{name}}, {{url}}, etc)
    ↓
Select Provider (SMTP, SendGrid, Mailgun)
    ↓
Send via Provider API
    ↓
Log Success/Failure to Sentry
    ↓
Return Result
```

---

## Configuration

### Environment Variables

#### SMTP (Default)
```env
MAIL_PROVIDER=smtp
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=Viabix
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

#### SendGrid
```env
MAIL_PROVIDER=sendgrid
MAIL_SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=Viabix
```

#### Mailgun
```env
MAIL_PROVIDER=mailgun
MAIL_MAILGUN_API_KEY=mg-xxxxxxxxxxxxxxxxxxxxxxxx
MAIL_MAILGUN_DOMAIN=mail.yourdomain.com
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=Viabix
```

#### Application URLs
```env
APP_URL=https://yourdomain.com
# Used in email links: {{APP_URL}}/login.html, etc.
```

### Recommended Providers by Use Case

| Use Case | Provider | Pros | Cons |
|----------|----------|------|------|
| **Development/Testing** | SMTP (Mailtrap) | Free tier, easy setup | Limited to 100/day |
| **Small Volume (<5k/month)** | SendGrid | Good deliverability, free tier | API key management |
| **Medium Volume (5k-100k/month)** | Mailgun | Affordable, webhooks | More complex config |
| **High Volume (>100k/month)** | AWS SES | Cheapest per message | Requires AWS account |

---

## Usage Patterns

### Pattern 1: Send Single Email
```php
$result = viabixSendEmail(
    'user@example.com',           // Recipient
    'welcome',                     // Template
    [                             // Template data
        'name' => 'John Doe',
        'login_url' => 'https://app.viabix.com/login'
    ]
);

if ($result['success']) {
    echo "Email sent: " . $result['message_id'];
} else {
    echo "Failed: " . $result['message'];
}
```

### Pattern 2: Send Welcome Email Helper
```php
// In signup.php after successful account creation
$result = viabixSendWelcomeEmail(
    $email_address,
    $user_name
);
```

### Pattern 3: Send Password Reset
```php
// In password recovery endpoint
$reset_token = generateSecureToken();
// Save token to database with expiry

$result = viabixSendPasswordResetEmail(
    $email,
    $name,
    $reset_token
);
```

### Pattern 4: Queue Email for Async Delivery
```php
// Queue instead of sending immediately (good for high traffic)
viabixQueueEmail(
    'user@example.com',
    'payment_confirmation',
    ['amount' => 99.90, 'invoice_id' => 'INV-123'],
    0  // Send immediately (or add delay in seconds)
);

// Later, cron job processes the queue
viabixProcessEmailQueue();
```

### Pattern 5: Bulk Email Sending
```php
// Send to multiple users
$users = $pdo->query("SELECT email, name FROM usuarios WHERE active = 1");

foreach ($users as $user) {
    viabixQueueEmail(
        $user['email'],
        'newsletter',
        ['name' => $user['name']],
        0  // Send in queue
    );
}

// Process all queued emails
viabixProcessEmailQueue();
```

### Pattern 6: Custom Template with Variables
```php
// Create template file: templates/email/custom.php
// Return ['subject' => '...', 'html' => '<p>{{custom_var}}</p>', ...]

$result = viabixSendEmail(
    'recipient@example.com',
    'custom',
    [
        'custom_var' => 'My Custom Value',
        'user_name' => 'John'
    ]
);
```

---

## Email Templates

### Template Format
Each template file returns an array:
```php
return [
    'subject' => 'Email Subject - can use {{variables}}',
    'html' => '<html>...with {{variables}}...</html>',
    'text' => 'Plain text version (optional)'
];
```

### Built-in Templates

#### welcome.php
Sent when new user signs up.

**Variables**:
- `{{name}}` - User's full name
- `{{login_url}}` - Link to login page
- `{{company_name}}` - Organization name

**Usage**:
```php
viabixSendWelcomeEmail($email, $name, $login_url);
```

#### password_reset.php
Sent when user requests password reset.

**Variables**:
- `{{name}}` - User's full name
- `{{reset_url}}` - Password reset link
- `{{token}}` - Reset token
- `{{expiry_hours}}` - Hours until expiry (default: 24)

**Usage**:
```php
viabixSendPasswordResetEmail($email, $name, $token, $reset_url);
```

#### email_verification.php
Sent when user needs to verify email.

**Variables**:
- `{{name}}` - User's full name
- `{{verify_url}}` - Verification link
- `{{token}}` - Verification token

**Usage**:
```php
viabixSendVerificationEmail($email, $name, $token, $verify_url);
```

#### payment_confirmation.php
Sent when payment is received.

**Variables**:
- `{{name}}` - User's full name
- `{{amount}}` - Payment amount
- `{{currency}}` - Currency code (e.g., BRL, USD)
- `{{invoice_id}}` - Invoice number
- `{{date}}` - Transaction date

**Usage**:
```php
viabixSendPaymentConfirmationEmail($email, $name, [
    'amount' => '99.90',
    'currency' => 'BRL',
    'invoice_id' => 'INV-2024-001'
]);
```

#### invoice.php
Sent when invoice is generated.

**Variables**:
- `{{name}}` - User's full name
- `{{invoice_id}}` - Invoice number
- `{{invoice_url}}` - Link to view invoice

**Usage**:
```php
viabixSendInvoiceEmail($email, $name, $invoice_id, $invoice_url);
```

### Creating Custom Templates

1. Create file: `templates/email/my_template.php`
2. Return array with subject, html, text
3. Use variables with `{{variable_name}}` syntax
4. Send with: `viabixSendEmail($to, 'my_template', $data)`

**Example**: `templates/email/my_template.php`
```php
<?php
return [
    'subject' => 'Hello {{name}}!',
    'html' => '<p>Hi {{name}},</p><p>{{message}}</p>',
    'text' => 'Hi {{name}}, {{message}}'
];
?>
```

Usage:
```php
viabixSendEmail('user@example.com', 'my_template', [
    'name' => 'John',
    'message' => 'This is my custom email!'
]);
```

---

## Testing & Validation

### Web-Based Test Interface
**File**: `api/test_email.php`

Open in browser: `http://localhost/ANVI/api/test_email.php`

**Test Cases**:
1. **Send Test Email** - Send to configured address
2. **Verify SMTP** - Check SMTP connection
3. **Test Template** - Render template with variables
4. **Queue Email** - Test queue functionality
5. **Process Queue** - Process queued emails

### Manual Testing with cURL

#### Test 1: Send Welcome Email
```bash
curl -X POST http://localhost/ANVI/api/test_email.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=send_test&template=welcome&to=test@example.com&name=John"
```

#### Test 2: Check Template Rendering
```bash
curl -X POST http://localhost/ANVI/api/test_email.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=render_template&template=welcome&name=John&login_url=https://app.viabix.com"
```

### Email Provider Testing

#### Mailtrap (SMTP)
1. Sign up at https://mailtrap.io
2. Get SMTP credentials
3. Add to .env:
```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```
4. Check inbox at mailtrap.io dashboard

#### SendGrid
1. Sign up at https://sendgrid.com
2. Generate API key from Settings → API Keys
3. Add to .env:
```env
MAIL_PROVIDER=sendgrid
MAIL_SENDGRID_API_KEY=SG.xxxxxxxxxxxx
```
4. Test send, check SendGrid dashboard

#### Mailgun
1. Sign up at https://mailgun.com
2. Get API key from your account
3. Add to .env:
```env
MAIL_PROVIDER=mailgun
MAIL_MAILGUN_API_KEY=mg-xxxxxxxxxxxx
MAIL_MAILGUN_DOMAIN=your-domain.com
```

### Browser Testing

1. Open `api/test_email.php` in browser
2. Fill form with test email address
3. Click "Send Test Email"
4. Check your inbox for delivery
5. Verify HTML rendering in email client

### Command Line Testing

#### Using mailhog (local SMTP server)
```bash
# Start mailhog
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

# Configure for mailhog
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=

# Send test email
php -r "require 'api/config.php'; var_dump(viabixSendWelcomeEmail('test@example.com', 'John'));"

# View in mailhog UI: http://localhost:8025
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Choose email provider (SMTP, SendGrid, Mailgun)
- [ ] Get API credentials if using third-party
- [ ] Add credentials to .env file
- [ ] Test email sending in staging
- [ ] Verify templates render correctly
- [ ] Check all email links point to production URLs
- [ ] Set up bounce/complaint handling (if provider supports)
- [ ] Configure SPF/DKIM/DMARC records for domain
- [ ] Whitelist sender address with email provider

### SMTP Configuration (php.ini)
```ini
[mail function]
SMTP = smtp.yourdomain.com
smtp_port = 587
sendmail_from = noreply@yourdomain.com
sendmail_path = "/usr/sbin/sendmail -t -i"
```

### DNS Configuration (for SPF/DKIM)
#### SPF Record
```dns
yourdomain.com. TXT "v=spf1 include:sendgrid.net ~all"
```

#### DKIM Record
Get from email provider (SendGrid, Mailgun, Mailbox, etc)

### Production Deployment
```bash
# 1. Verify email module exists
ls -la api/email.php

# 2. Check templates directory
ls -la templates/email/

# 3. Verify integration in config.php
grep "email.php" api/config.php

# 4. Test email sending
php -r "require 'api/config.php'; var_dump(viabixSendEmail('admin@yourdomain.com', 'welcome', ['name' => 'Admin', 'login_url' => 'https://app.yourdomain.com']));"

# 5. Check logs
tail -f /var/log/mail.log
```

### Post-Deployment
- [ ] Monitor email delivery rates in Sentry
- [ ] Check for bounce/complaint notifications
- [ ] Test welcome emails for new signups
- [ ] Test password reset emails
- [ ] Verify payment confirmation emails
- [ ] Get stakeholder confirmation of working emails
- [ ] Document provider account details securely

---

## Troubleshooting

### Issue 1: "Email not sending, no errors"
**Symptom**: Function returns success but email never arrives  
**Causes**:
- SMTP credentials wrong
- Firewall blocking SMTP port
- Email flagged as spam
- Sender domain not authenticated

**Solution**:
```bash
# Test SMTP connection
telnet smtp.yourdomain.com 587

# Check mail logs
tail -f /var/log/mail.log
grep "your-email" /var/log/mail.log

# Verify SPF/DKIM records
dig yourdomain.com TXT
dig mail._domainkey.yourdomain.com TXT

# Test with -v flag
echo "Subject: Test" | sendmail -v user@example.com
```

### Issue 2: "SMTP authentication failed"
**Symptom**: Sentry shows "SMTP authentication error"  
**Causes**:
- Wrong username/password
- Wrong authentication method (TLS vs SSL)
- Special characters not escaped in password

**Solution**:
```env
# Verify in .env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-actual-username
MAIL_PASSWORD=your-actual-password
MAIL_ENCRYPTION=tls

# For Gmail, use app-specific password:
# https://myaccount.google.com/apppasswords
```

### Issue 3: "Emails going to spam folder"
**Symptom**: Emails arrive but in spam/junk folder  
**Causes**:
- Missing SPF/DKIM/DMARC records
- Sender reputation low
- HTML content flagged
- Link analysis triggered spam filters

**Solution**:
1. Add SPF/DKIM/DMARC records to DNS
2. Warm up sender reputation (start with low volume)
3. Avoid spam trigger words (FREE, BUY NOW, etc)
4. Use proper subject lines
5. Include unsubscribe link

### Issue 4: "Template rendering fails"
**Symptom**: Email sent but variables not replaced  
**Causes**:
- Template file not found
- Variable name misspelled
- Template syntax wrong

**Solution**:
```php
// Verify template exists
if (!file_exists('templates/email/my_template.php')) {
    die('Template not found');
}

// Debug template rendering
$template = viabixLoadEmailTemplate('welcome', ['name' => 'John']);
var_dump($template);

// Verify variable syntax: {{variable_name}}
// NOT {variable_name} or {% variable %}
```

### Issue 5: "Queue not processing"
**Symptom**: Emails queued but never sent  
**Causes**:
- Cron job not configured
- Database email_queue table doesn't exist
- Processing function not called

**Solution**:
```bash
# Set up cron job (every 5 minutes)
*/5 * * * * php /var/www/viabix/api/process-email-queue.php

# Or call manually
curl http://localhost/ANVI/api/process-email-queue.php

# Check queue table
mysql> SELECT COUNT(*) FROM email_queue WHERE sent_at IS NULL;

# Manually process queue
php -r "require 'api/config.php'; var_dump(viabixProcessEmailQueue());"
```

---

## API Reference

### `viabixSendEmail($to, $template, $data, $subject)`
**Purpose**: Send email with template  
**Parameters**:
- `$to` (string) - Recipient email
- `$template` (string) - Template name
- `$data` (array) - Template variables
- `$subject` (string, optional) - Override subject

**Returns**: `['success' => bool, 'message' => string, 'message_id' => string]`

**Example**:
```php
$result = viabixSendEmail(
    'user@example.com',
    'welcome',
    ['name' => 'John', 'login_url' => 'https://app.viabix.com'],
    'Welcome to Viabix!'  // Optional override
);
```

### `viabixQueueEmail($to, $template, $data, $delay_seconds)`
**Purpose**: Queue email for async delivery  
**Parameters**:
- `$to` (string) - Recipient email
- `$template` (string) - Template name
- `$data` (array) - Template variables
- `$delay_seconds` (int) - Delay before sending (optional)

**Returns**: `['success' => bool, 'message' => string, 'message_id' => string|int]`

**Example**:
```php
viabixQueueEmail(
    'user@example.com',
    'newsletter',
    ['name' => 'John'],
    300  // Send in 5 minutes
);
```

### `viabixProcessEmailQueue()`
**Purpose**: Process queued emails  
**Returns**: `['processed' => int, 'failed' => int, 'error' => string]`

**Example**:
```php
$result = viabixProcessEmailQueue();
echo "Processed: {$result['processed']}, Failed: {$result['failed']}";
```

### `viabixSendWelcomeEmail($email, $name, $login_url)`
**Purpose**: Send welcome email to new user  
**Parameters**:
- `$email` (string) - User email
- `$name` (string) - User name
- `$login_url` (string, optional) - Custom login URL

**Example**:
```php
viabixSendWelcomeEmail('john@example.com', 'John Doe');
```

### `viabixSendPasswordResetEmail($email, $name, $token, $reset_url)`
**Purpose**: Send password reset email  
**Parameters**:
- `$email` (string) - User email
- `$name` (string) - User name
- `$token` (string) - Reset token
- `$reset_url` (string, optional) - Custom reset URL

### `viabixSendVerificationEmail($email, $name, $token, $verify_url)`
**Purpose**: Send email verification link  

### `viabixSendPaymentConfirmationEmail($email, $name, $payment_data)`
**Purpose**: Send payment confirmation  
**Parameters**:
- `$email` (string) - User email
- `$name` (string) - User name
- `$payment_data` (array) - Payment info ['amount', 'currency', 'invoice_id']

### `viabixSendInvoiceEmail($email, $name, $invoice_id, $invoice_url)`
**Purpose**: Send invoice email

---

## Best Practices

1. **Always Queue Bulk Emails**: Don't send directly to many users in one request
2. **Validate Before Sending**: Check email format before queuing
3. **Use Transactional Provider**: SendGrid/Mailgun for critical emails
4. **Include Unsubscribe Link**: Required by law (CAN-SPAM, GDPR)
5. **Monitor Deliverability**: Track bounces and complaints
6. **Warm Up Sender**: Start with low volume, gradually increase
7. **SPF/DKIM/DMARC**: Configure all three for best results
8. **Test Headers**: Verify email headers for authenticity
9. **Log All Sends**: Track what was sent and when
10. **Set Appropriate Retry Logic**: Retry failed sends 3-5 times

---

## Related Documentation

- [MONITORING.md](MONITORING.md) - Sentry error tracking
- [RATE_LIMITING.md](RATE_LIMITING.md) - Rate limiting (prevents email abuse)
- [CSRF_PROTECTION.md](CSRF_PROTECTION.md) - CSRF protection
- [SendGrid Integration](https://sendgrid.com/docs/for-developers/sending-email/)
- [Mailgun Integration](https://documentation.mailgun.com/)

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024 | Initial implementation with SMTP, SendGrid, Mailgun support |

---

## Support & Questions

For issues or questions about email system:
1. Check [Troubleshooting](#troubleshooting) section
2. Review test interface: `api/test_email.php`
3. Check Sentry dashboard for email errors
4. Verify provider status pages
5. Consult `EMAIL_SYSTEM.md` documentation

---

**Last Updated**: 2024  
**Maintainer**: Viabix Development Team  
**Status**: ✅ Production Ready
