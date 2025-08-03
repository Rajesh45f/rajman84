# Private PHP Mailer Script

A powerful, lightweight PHP mailer script with HTML body support and custom headers, designed for cPanel and shell servers.

## 🚀 Features

- **Custom Headers**: Set FROM name, FROM email, REPLY-TO, and SUBJECT headers
- **HTML Email Support**: Send beautiful HTML emails with text fallbacks
- **Multiple Recipients**: Send to single or multiple recipients
- **Security Features**: Header injection protection and email validation
- **Error Handling**: Comprehensive error reporting and logging
- **Template System**: Ready-to-use HTML email templates
- **Easy Configuration**: Centralized configuration file
- **cPanel Compatible**: Works on shared hosting and VPS servers

## 📁 File Structure

```
├── mailer.php              # Main mailer class
├── config.php              # Configuration settings
├── send_email.php          # Usage examples and test interface
├── templates/              # HTML email templates
│   ├── welcome.html        # Welcome email template
│   └── notification.html   # Notification email template
├── logs/                   # Log files (create manually)
└── README.md              # This file
```

## 🛠️ Installation

1. **Upload Files**: Upload all files to your server
2. **Set Permissions**: Ensure PHP can write to the `logs/` directory
3. **Configure Settings**: Edit `config.php` with your email settings
4. **Test**: Access `send_email.php` in your browser to test

### Quick Setup

```bash
# Create necessary directories
mkdir -p logs templates

# Set permissions (if needed)
chmod 755 logs
chmod 644 *.php
```

## ⚙️ Configuration

Edit `config.php` to customize your settings:

```php
// Basic Configuration
define('DEFAULT_FROM_EMAIL', 'noreply@yourdomain.com');
define('DEFAULT_FROM_NAME', 'Your Company Name');
define('DEFAULT_REPLY_TO', 'support@yourdomain.com');

// Security (optional)
define('REQUIRE_AUTHENTICATION', true);
define('API_KEY', 'your-secret-api-key-here');
```

## 📧 Basic Usage

### Simple Email

```php
<?php
require_once 'mailer.php';
require_once 'config.php';

$mailer = new PrivateMailer();

// Set sender information
$mailer->setFrom('sender@domain.com', 'Sender Name');
$mailer->setReplyTo('reply@domain.com');

// Set email content
$mailer->setSubject('Test Email');
$mailer->setHtmlBody('<h1>Hello World!</h1><p>This is a test email.</p>');
$mailer->setTextBody('Hello World! This is a test email.');

// Send email
if ($mailer->sendTo('recipient@domain.com', 'Recipient Name')) {
    echo 'Email sent successfully!';
} else {
    echo 'Error: ' . $mailer->getLastError();
}
?>
```

### Multiple Recipients

```php
$recipients = [
    ['email' => 'user1@domain.com', 'name' => 'User One'],
    ['email' => 'user2@domain.com', 'name' => 'User Two'],
    'user3@domain.com' // Just email address
];

$mailer->sendToMultiple($recipients);
```

### Custom Headers

```php
$mailer->addHeader('X-Priority', '1');           // High priority
$mailer->addHeader('X-Custom-Header', 'Value'); // Custom header
```

## 🎨 HTML Templates

Use the included templates for professional-looking emails:

### Welcome Email Template

```php
// Load and customize welcome template
$template = file_get_contents('templates/welcome.html');
$template = str_replace('{{COMPANY_NAME}}', 'Your Company', $template);
$template = str_replace('{{USER_NAME}}', 'John Doe', $template);
$template = str_replace('{{DASHBOARD_URL}}', 'https://yoursite.com/dashboard', $template);

$mailer->setHtmlBody($template);
```

### Available Template Variables

**Welcome Template:**
- `{{COMPANY_NAME}}` - Your company name
- `{{USER_NAME}}` - Recipient's name
- `{{USER_EMAIL}}` - Recipient's email
- `{{DASHBOARD_URL}}` - Link to dashboard
- `{{COMPANY_ADDRESS}}` - Company address
- `{{UNSUBSCRIBE_URL}}` - Unsubscribe link

**Notification Template:**
- `{{NOTIFICATION_TYPE}}` - Type of notification
- `{{NOTIFICATION_TITLE}}` - Notification title
- `{{NOTIFICATION_MESSAGE}}` - Main message
- `{{PRIORITY_LEVEL}}` - Priority (high, medium, low, info)
- `{{TIMESTAMP}}` - When notification was sent
- `{{REFERENCE_ID}}` - Reference identifier

## 🔒 Security Features

### Header Injection Protection
The script automatically sanitizes headers to prevent injection attacks:

```php
// This is handled automatically
$mailer->setSubject("Safe Subject\r\nBCC: hacker@evil.com"); // Cleaned automatically
```

### Email Validation
All email addresses are validated before sending:

```php
$mailer->setFrom('invalid-email'); // Returns false, sets error
```

### Authentication (Optional)
Enable API key authentication for form submissions:

```php
// In config.php
define('REQUIRE_AUTHENTICATION', true);
define('API_KEY', 'your-secret-key-here');
```

## 🌐 Web Interface

Access `send_email.php` in your browser for a web-based testing interface. This provides:

- Form-based email sending
- Real-time error reporting
- Code examples
- API endpoint for AJAX requests

### AJAX Usage

```javascript
fetch('send_email.php', {
    method: 'POST',
    body: new FormData(document.getElementById('emailForm'))
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Email sent!');
    } else {
        console.error('Error:', data.error);
    }
});
```

## 🛡️ Error Handling

The mailer provides comprehensive error handling:

```php
// Check for errors
if ($mailer->hasErrors()) {
    foreach ($mailer->getErrors() as $error) {
        echo "Error: $error\n";
    }
}

// Get last error
echo $mailer->getLastError();

// Clear errors
$mailer->clearErrors();
```

## 📊 Common Use Cases

### 1. User Registration Welcome Email
```php
function sendWelcomeEmail($userEmail, $userName) {
    global $mailer;
    
    $mailer->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
    $mailer->setSubject('Welcome to Our Platform!');
    
    $html = file_get_contents('templates/welcome.html');
    $html = str_replace('{{USER_NAME}}', $userName, $html);
    $html = str_replace('{{COMPANY_NAME}}', 'Your Company', $html);
    
    $mailer->setHtmlBody($html);
    return $mailer->sendTo($userEmail, $userName);
}
```

### 2. System Notifications
```php
function sendAlert($adminEmails, $message) {
    global $mailer;
    
    $mailer->setFrom(DEFAULT_FROM_EMAIL, 'System Alert');
    $mailer->addHeader('X-Priority', '1'); // High priority
    $mailer->setSubject('System Alert: Immediate Attention Required');
    
    $html = "<h2>⚠️ System Alert</h2><p>$message</p>";
    $mailer->setHtmlBody($html);
    
    return $mailer->sendToMultiple($adminEmails);
}
```

### 3. Password Reset
```php
function sendPasswordReset($userEmail, $resetToken) {
    global $mailer;
    
    $resetLink = "https://yoursite.com/reset?token=$resetToken";
    
    $mailer->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
    $mailer->setSubject('Password Reset Request');
    
    $html = "
    <h2>Password Reset</h2>
    <p>Click the link below to reset your password:</p>
    <a href='$resetLink'>Reset Password</a>
    <p>This link expires in 1 hour.</p>
    ";
    
    $mailer->setHtmlBody($html);
    return $mailer->sendTo($userEmail);
}
```

## 🔧 Server Requirements

- **PHP**: 5.6 or higher (7.0+ recommended)
- **Mail Function**: PHP `mail()` function enabled
- **Permissions**: Write access to logs directory
- **Optional**: SMTP server for better deliverability

### cPanel Hosting
Most cPanel hosting providers have PHP mail() enabled by default. If emails aren't being delivered:

1. Check your hosting provider's email policies
2. Ensure your domain has proper SPF/DKIM records
3. Consider using SMTP authentication for better deliverability

### VPS/Dedicated Servers
You may need to install and configure a mail server:

```bash
# Ubuntu/Debian
sudo apt-get install sendmail

# CentOS/RHEL
sudo yum install sendmail
```

## 📈 Best Practices

1. **SPF Records**: Add SPF records to your domain DNS
2. **DKIM**: Configure DKIM signing for better deliverability
3. **Rate Limiting**: Don't send too many emails too quickly
4. **Bounce Handling**: Monitor bounce rates and remove invalid addresses
5. **Content**: Avoid spammy content and excessive promotional language
6. **Testing**: Always test emails before sending to large lists

## 🚨 Troubleshooting

### Emails Not Being Sent
```php
// Check if mail() function is available
if (!function_exists('mail')) {
    echo "PHP mail() function is not available";
}

// Check for errors
if ($mailer->hasErrors()) {
    print_r($mailer->getErrors());
}
```

### Emails Going to Spam
- Check your server's IP reputation
- Ensure proper DNS records (SPF, DKIM, DMARC)
- Avoid spam trigger words in subject/content
- Include a text version alongside HTML

### Headers Not Working
- Verify your hosting provider allows custom headers
- Check for header injection protection interference
- Test with simple headers first

## 📄 License

This script is released under the MIT License. Feel free to use, modify, and distribute as needed.

## 🤝 Support

For support and questions:
1. Check this README for common solutions
2. Review the code comments for detailed explanations
3. Test with the included web interface
4. Consult your hosting provider's documentation

---

**Made with ❤️ for the PHP community**