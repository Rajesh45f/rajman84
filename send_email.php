<?php
/**
 * Example Usage Script for Private PHP Mailer
 * This file demonstrates how to use the mailer script
 */

require_once 'mailer.php';
require_once 'config.php';

// Initialize the mailer
$mailer = new PrivateMailer();

// Example 1: Simple HTML Email
function sendWelcomeEmail($recipientEmail, $recipientName) {
    global $mailer;
    
    // Clear any previous errors
    $mailer->clearErrors();
    
    // Set sender information
    $mailer->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
    $mailer->setReplyTo(DEFAULT_REPLY_TO);
    
    // Set subject
    $mailer->setSubject('Welcome to Our Service!');
    
    // Create HTML content
    $htmlContent = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: #007cba; color: white; padding: 20px; text-align: center;">
            <h1>Welcome, ' . htmlspecialchars($recipientName) . '!</h1>
        </div>
        <div style="padding: 20px; background: #f9f9f9;">
            <h2>Thank you for joining us!</h2>
            <p>We\'re excited to have you on board. Here\'s what you can expect:</p>
            <ul>
                <li>Access to premium features</li>
                <li>24/7 customer support</li>
                <li>Regular updates and improvements</li>
            </ul>
            <p>If you have any questions, feel free to reply to this email.</p>
            <p style="text-align: center;">
                <a href="#" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">Get Started</a>
            </p>
        </div>
        <div style="padding: 20px; text-align: center; font-size: 12px; color: #666;">
            <p>© 2024 Your Company. All rights reserved.</p>
        </div>
    </div>';
    
    $mailer->setHtmlBody($htmlContent);
    
    // Set text fallback
    $textContent = "Welcome, $recipientName!\n\n";
    $textContent .= "Thank you for joining us! We're excited to have you on board.\n\n";
    $textContent .= "What you can expect:\n";
    $textContent .= "- Access to premium features\n";
    $textContent .= "- 24/7 customer support\n";
    $textContent .= "- Regular updates and improvements\n\n";
    $textContent .= "If you have any questions, feel free to reply to this email.\n\n";
    $textContent .= "Best regards,\nYour Company Team";
    
    $mailer->setTextBody($textContent);
    
    // Send the email
    return $mailer->sendTo($recipientEmail, $recipientName);
}

// Example 2: Notification Email with Custom Headers
function sendNotificationEmail($recipients, $subject, $message) {
    global $mailer;
    
    $mailer->clearErrors();
    
    // Set sender information
    $mailer->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
    $mailer->setReplyTo(DEFAULT_REPLY_TO);
    
    // Add custom headers
    $mailer->addHeader('X-Priority', '1'); // High priority
    $mailer->addHeader('X-Notification-Type', 'System Alert');
    
    // Set subject
    $mailer->setSubject($subject);
    
    // Create simple HTML content
    $htmlContent = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd;">
        <div style="background: #dc3545; color: white; padding: 15px;">
            <h2 style="margin: 0;">🚨 System Notification</h2>
        </div>
        <div style="padding: 20px;">
            <p><strong>Message:</strong></p>
            <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545;">
                ' . nl2br(htmlspecialchars($message)) . '
            </div>
            <p style="margin-top: 20px; font-size: 12px; color: #666;">
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>';
    
    $mailer->setHtmlBody($htmlContent);
    
    // Text version
    $textContent = "SYSTEM NOTIFICATION\n\n";
    $textContent .= "Message: $message\n\n";
    $textContent .= "This is an automated notification. Please do not reply to this email.";
    
    $mailer->setTextBody($textContent);
    
    // Send to multiple recipients
    return $mailer->sendToMultiple($recipients);
}

// Example 3: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic security check
    if (REQUIRE_AUTHENTICATION && (!isset($_POST['api_key']) || $_POST['api_key'] !== API_KEY)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // Get form data
    $to = $_POST['to'] ?? '';
    $toName = $_POST['to_name'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $fromEmail = $_POST['from_email'] ?? DEFAULT_FROM_EMAIL;
    $fromName = $_POST['from_name'] ?? DEFAULT_FROM_NAME;
    $replyTo = $_POST['reply_to'] ?? DEFAULT_REPLY_TO;
    
    // Validate required fields
    if (empty($to) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Create and send email
    $mailer->clearErrors();
    $mailer->setFrom($fromEmail, $fromName);
    $mailer->setReplyTo($replyTo);
    $mailer->setSubject($subject);
    
    // Simple HTML wrapper for the message
    $htmlMessage = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="padding: 20px;">
            ' . nl2br(htmlspecialchars($message)) . '
        </div>
    </div>';
    
    $mailer->setHtmlBody($htmlMessage);
    $mailer->setTextBody($message);
    
    if ($mailer->sendTo($to, $toName)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $mailer->getLastError()]);
    }
    exit;
}

// If accessed directly, show usage examples
if (basename($_SERVER['PHP_SELF']) === 'send_email.php') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Private PHP Mailer - Test Interface</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            textarea { height: 120px; resize: vertical; }
            button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
            .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .code-example { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
            pre { overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>Private PHP Mailer - Test Interface</h1>
        
        <div class="code-example">
            <h3>Quick Test</h3>
            <p>Use the form below to test the email functionality:</p>
        </div>
        
        <form method="POST" id="emailForm">
            <?php if (REQUIRE_AUTHENTICATION): ?>
            <div class="form-group">
                <label for="api_key">API Key:</label>
                <input type="password" id="api_key" name="api_key" required>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="to">To Email:</label>
                <input type="email" id="to" name="to" required>
            </div>
            
            <div class="form-group">
                <label for="to_name">To Name (optional):</label>
                <input type="text" id="to_name" name="to_name">
            </div>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="from_email">From Email:</label>
                <input type="email" id="from_email" name="from_email" value="<?php echo DEFAULT_FROM_EMAIL; ?>">
            </div>
            
            <div class="form-group">
                <label for="from_name">From Name:</label>
                <input type="text" id="from_name" name="from_name" value="<?php echo DEFAULT_FROM_NAME; ?>">
            </div>
            
            <div class="form-group">
                <label for="reply_to">Reply To:</label>
                <input type="email" id="reply_to" name="reply_to" value="<?php echo DEFAULT_REPLY_TO; ?>">
            </div>
            
            <button type="submit">Send Email</button>
        </form>
        
        <div id="result"></div>
        
        <div class="code-example">
            <h3>Usage Examples</h3>
            <p>Here are some code examples for using the mailer:</p>
            
            <h4>1. Basic Usage</h4>
            <pre><code>require_once 'mailer.php';

$mailer = new PrivateMailer();
$mailer->setFrom('sender@domain.com', 'Sender Name');
$mailer->setReplyTo('reply@domain.com');
$mailer->setSubject('Test Email');
$mailer->setHtmlBody('&lt;h1&gt;Hello World!&lt;/h1&gt;');

if ($mailer->sendTo('recipient@domain.com', 'Recipient Name')) {
    echo 'Email sent successfully!';
} else {
    echo 'Error: ' . $mailer->getLastError();
}</code></pre>
            
            <h4>2. Multiple Recipients</h4>
            <pre><code>$recipients = [
    ['email' => 'user1@domain.com', 'name' => 'User One'],
    ['email' => 'user2@domain.com', 'name' => 'User Two']
];

$mailer->sendToMultiple($recipients);</code></pre>
            
            <h4>3. Custom Headers</h4>
            <pre><code>$mailer->addHeader('X-Priority', '1');
$mailer->addHeader('X-Custom-Header', 'Custom Value');</code></pre>
        </div>
        
        <script>
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            resultDiv.innerHTML = '<p>Sending email...</p>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="result success">' + data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="result error">Error: ' + data.error + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="result error">Error: ' + error.message + '</div>';
            });
        });
        </script>
    </body>
    </html>
    <?php
}
?>