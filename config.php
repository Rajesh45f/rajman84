<?php
/**
 * Email Configuration File
 * Store your email settings here for easy management
 */

// Email Configuration
define('DEFAULT_FROM_EMAIL', 'noreply@yourdomain.com');
define('DEFAULT_FROM_NAME', 'Your Company Name');
define('DEFAULT_REPLY_TO', 'support@yourdomain.com');

// SMTP Settings (if using SMTP instead of mail())
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'

// Email Templates Directory
define('TEMPLATE_DIR', __DIR__ . '/templates/');

// Logging
define('LOG_EMAILS', true);
define('LOG_FILE', __DIR__ . '/logs/email.log');

// Rate Limiting (emails per hour)
define('RATE_LIMIT', 100);
define('RATE_LIMIT_FILE', __DIR__ . '/logs/rate_limit.json');

// Whitelist/Blacklist
$email_whitelist = [
    // Add allowed email domains or addresses
    // 'example.com',
    // 'user@domain.com'
];

$email_blacklist = [
    // Add blocked email domains or addresses
    // 'spam.com',
    // 'blocked@domain.com'
];

// Security Settings
define('REQUIRE_AUTHENTICATION', false); // Set to true to require authentication
define('API_KEY', 'your-secret-api-key-here'); // Change this to a secure random string

// Default HTML Template
$default_html_template = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{SUBJECT}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #007cba, #0056b3);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-content {
            padding: 30px 20px;
        }
        .email-content h2 {
            color: #007cba;
            margin-top: 0;
        }
        .email-content p {
            margin-bottom: 15px;
        }
        .email-content ul {
            padding-left: 20px;
        }
        .email-content li {
            margin-bottom: 5px;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        .button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{{HEADER_TITLE}}</h1>
        </div>
        <div class="email-content">
            {{CONTENT}}
        </div>
        <div class="email-footer">
            <p>{{FOOTER_TEXT}}</p>
            <p>This email was sent by {{FROM_NAME}}</p>
        </div>
    </div>
</body>
</html>';

// Default text template
$default_text_template = '
{{HEADER_TITLE}}

{{CONTENT}}

---
{{FOOTER_TEXT}}
This email was sent by {{FROM_NAME}}
';

?>