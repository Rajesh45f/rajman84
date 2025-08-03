<?php
/**
 * Private PHP Mailer Script
 * Supports HTML emails with custom headers for cPanel/Shell servers
 * 
 * @author AI Assistant
 * @version 1.0
 */

class PrivateMailer {
    
    private $fromName;
    private $fromEmail;
    private $replyTo;
    private $subject;
    private $htmlBody;
    private $textBody;
    private $headers;
    private $errors = [];
    
    public function __construct() {
        $this->headers = [];
        $this->setDefaultHeaders();
    }
    
    /**
     * Set sender information
     */
    public function setFrom($email, $name = '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid FROM email address: $email";
            return false;
        }
        
        $this->fromEmail = $email;
        $this->fromName = $name;
        return true;
    }
    
    /**
     * Set reply-to address
     */
    public function setReplyTo($email, $name = '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid REPLY-TO email address: $email";
            return false;
        }
        
        $this->replyTo = $email;
        return true;
    }
    
    /**
     * Set email subject
     */
    public function setSubject($subject) {
        $this->subject = $this->cleanHeader($subject);
        return true;
    }
    
    /**
     * Set HTML body content
     */
    public function setHtmlBody($html) {
        $this->htmlBody = $html;
        return true;
    }
    
    /**
     * Set text body content (fallback for non-HTML clients)
     */
    public function setTextBody($text) {
        $this->textBody = $text;
        return true;
    }
    
    /**
     * Add custom header
     */
    public function addHeader($name, $value) {
        $this->headers[$name] = $this->cleanHeader($value);
        return true;
    }
    
    /**
     * Send email to single recipient
     */
    public function sendTo($email, $name = '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid recipient email address: $email";
            return false;
        }
        
        $to = !empty($name) ? "$name <$email>" : $email;
        return $this->send($to);
    }
    
    /**
     * Send email to multiple recipients
     */
    public function sendToMultiple($recipients) {
        $toList = [];
        
        foreach ($recipients as $recipient) {
            if (is_array($recipient)) {
                $email = $recipient['email'];
                $name = isset($recipient['name']) ? $recipient['name'] : '';
            } else {
                $email = $recipient;
                $name = '';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Invalid recipient email address: $email";
                continue;
            }
            
            $toList[] = !empty($name) ? "$name <$email>" : $email;
        }
        
        if (empty($toList)) {
            $this->errors[] = "No valid recipients found";
            return false;
        }
        
        return $this->send(implode(', ', $toList));
    }
    
    /**
     * Main send function
     */
    private function send($to) {
        if (empty($this->fromEmail)) {
            $this->errors[] = "FROM email address is required";
            return false;
        }
        
        if (empty($this->subject)) {
            $this->errors[] = "Subject is required";
            return false;
        }
        
        if (empty($this->htmlBody) && empty($this->textBody)) {
            $this->errors[] = "Email body is required";
            return false;
        }
        
        $headers = $this->buildHeaders();
        $body = $this->buildBody();
        
        // Use PHP's mail() function
        $result = mail($to, $this->subject, $body, $headers);
        
        if (!$result) {
            $this->errors[] = "Failed to send email";
            return false;
        }
        
        return true;
    }
    
    /**
     * Build email headers
     */
    private function buildHeaders() {
        $headers = [];
        
        // FROM header
        if (!empty($this->fromName)) {
            $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        } else {
            $headers[] = "From: {$this->fromEmail}";
        }
        
        // REPLY-TO header
        if (!empty($this->replyTo)) {
            $headers[] = "Reply-To: {$this->replyTo}";
        }
        
        // Content type for HTML/multipart
        if (!empty($this->htmlBody)) {
            if (!empty($this->textBody)) {
                // Multipart email
                $boundary = uniqid('boundary_');
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
                $this->boundary = $boundary;
            } else {
                // HTML only
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: text/html; charset=UTF-8";
            }
        } else {
            // Text only
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        // Additional headers
        $headers[] = "X-Mailer: Private PHP Mailer v1.0";
        $headers[] = "X-Priority: 3";
        
        // Custom headers
        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Build email body
     */
    private function buildBody() {
        if (!empty($this->htmlBody) && !empty($this->textBody)) {
            // Multipart email
            $body = "--{$this->boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $this->textBody . "\r\n\r\n";
            
            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $this->htmlBody . "\r\n\r\n";
            
            $body .= "--{$this->boundary}--";
            
            return $body;
        } elseif (!empty($this->htmlBody)) {
            return $this->htmlBody;
        } else {
            return $this->textBody;
        }
    }
    
    /**
     * Set default headers to prevent spam
     */
    private function setDefaultHeaders() {
        $this->headers['Return-Path'] = '';
        $this->headers['Errors-To'] = '';
    }
    
    /**
     * Clean header value to prevent injection
     */
    private function cleanHeader($value) {
        return str_replace(["\r", "\n", "%0a", "%0d"], '', $value);
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if there are errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Get last error
     */
    public function getLastError() {
        return end($this->errors);
    }
}

// Example usage (uncomment to test)
/*
$mailer = new PrivateMailer();

// Set sender information
$mailer->setFrom('noreply@yourdomain.com', 'Your Company Name');
$mailer->setReplyTo('support@yourdomain.com', 'Support Team');

// Set email content
$mailer->setSubject('Test Email from Private Mailer');

// HTML body
$htmlBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007cba; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome!</h1>
        </div>
        <div class="content">
            <h2>Hello there!</h2>
            <p>This is a test email sent using our private PHP mailer script.</p>
            <p>The script supports:</p>
            <ul>
                <li>Custom FROM name and email</li>
                <li>Reply-To headers</li>
                <li>HTML and text content</li>
                <li>Multiple recipients</li>
                <li>Custom headers</li>
            </ul>
        </div>
        <div class="footer">
            <p>This email was sent by Private PHP Mailer v1.0</p>
        </div>
    </div>
</body>
</html>';

$mailer->setHtmlBody($htmlBody);

// Text fallback
$textBody = "Hello!\n\nThis is a test email sent using our private PHP mailer script.\n\nBest regards,\nYour Company";
$mailer->setTextBody($textBody);

// Send email
if ($mailer->sendTo('recipient@example.com', 'Recipient Name')) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email: " . $mailer->getLastError();
}
*/
?>