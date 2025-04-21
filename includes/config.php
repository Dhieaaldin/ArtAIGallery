<?php
/**
 * Configuration settings for the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'AI Artistry');
define('SITE_URL', 'http://localhost:5000'); // Change this in production

// Set timezone
date_default_timezone_set('UTC');

// Database configuration
define('DB_HOST', getenv('PGHOST'));
define('DB_NAME', getenv('PGDATABASE'));
define('DB_USER', getenv('PGUSER'));
define('DB_PASS', getenv('PGPASSWORD'));
define('DB_CHARSET', 'utf8');

// Default pagination limits
define('DEFAULT_PAGE_SIZE', 12);

// Subscription plans
define('PLAN_FREE', 'free');
define('PLAN_PREMIUM', 'premium');
define('PREMIUM_PRICE', 9.99);

// Security settings
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); // 30 days

// Set error reporting based on environment
$environment = 'development'; // Change to 'production' for live site

if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
