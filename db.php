<?php
/**
 * Database Configuration & Session Management
 * Enhanced with professional security and error handling
 */

// ========================
// ERROR REPORTING SETTINGS
// ========================
ini_set('display_errors', 0); // Hide errors from users in production
error_reporting(E_ALL);       // Report all errors internally

// ========================
// DATABASE CONFIGURATION
// ========================
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP default

// Database DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// ========================
// DATABASE CONNECTION
// ========================
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Fetch rows as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                     // Use native prepared statements (security)
        PDO::ATTR_PERSISTENT         => false,                     // Better for concurrent connections
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // Optional: Set timezone for database
    $pdo->exec("SET time_zone = '+05:30';"); // Adjust to your timezone
    
} catch (PDOException $e) {
    // ========================
    // SECURE ERROR HANDLING
    // ========================
    
    // Log the error for debugging (create logs/ directory first)
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . "\n";
    error_log($logMessage, 3, __DIR__ . '/logs/database_errors.log');
    
    // User-friendly message
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
        // Show detailed error in development
        die('<div style="font-family: monospace; background: #f8d7da; color: #721c24; 
             padding: 20px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
             <h3>Database Connection Failed (Development Mode)</h3>
             <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
             <p>Check your database credentials and ensure MySQL is running.</p>
             </div>');
    } else {
        // Generic message in production
        die('<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
             <h2 style="color: #dc3545;">⚠️ Service Temporarily Unavailable</h2>
             <p>We\'re experiencing technical difficulties. Please try again later.</p>
             <p><small>If the problem persists, contact support.</small></p>
             </div>');
    }
}

// ========================
// SESSION CONFIGURATION
// ========================
if (session_status() === PHP_SESSION_NONE) {
    // Session security settings
    ini_set('session.use_only_cookies', 1);        // Only use cookies for session
    ini_set('session.cookie_httponly', 1);         // Prevent JavaScript access to cookies
    ini_set('session.cookie_secure', 0);           // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
    
    // Session configuration
    session_name('BLOG_SESSION');                  // Custom session name
    session_set_cookie_params([
        'lifetime' => 86400,                       // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,                         // Set true for HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
    
    // ========================
    // SESSION SECURITY CHECKS
    // ========================
    
    // Regenerate session ID every 5 minutes for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 300 seconds = 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Validate session fingerprint (prevents session hijacking)
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipFragment = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 15);
    $sessionFingerprint = md5($userAgent . $ipFragment);
    
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $sessionFingerprint;
    } elseif ($_SESSION['fingerprint'] !== $sessionFingerprint) {
        // Possible session hijacking - destroy session
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['fingerprint'] = $sessionFingerprint;
    }
}

// ========================
// HELPER FUNCTIONS
// ========================

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirect with optional message
 */
function redirect($url, $message = '') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message and clear it
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return '';
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require authentication
 */
function require_auth() {
    if (!is_logged_in()) {
        redirect('login.php', 'Please login to access this page.');
    }
}

/**
 * Require specific role
 */
function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        redirect('index.php', 'You do not have permission to access this page.');
    }
}

// ========================
// CUSTOM HEADER FOR JSON RESPONSES
// ========================
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    header('Content-Type: application/json; charset=utf-8');
}

// ========================
// SET TIMEZONE
// ========================
date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

// ========================
// CROSS-SITE REQUEST FORGERY (CSRF) PROTECTION
// ========================
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate token on first load
$csrf_token = generate_csrf_token();

?>