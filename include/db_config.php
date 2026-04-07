<?php
/*
 * Database Configuration File
 * Mikhmon - MikroTik Hotspot Management System
 * 
 * Multi-admin, multi-router database configuration
 */

// Database Connection Settings
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'mikhmon_user');
define('DB_PASS', 'mikhmon_secure_pass_123');
define('DB_NAME', 'mikhmon_db');
define('DB_CHARSET', 'utf8mb4');

// Database Connection Options
define('DB_DEBUG', false);  // Set to true for debugging
define('DB_SHOW_ERRORS', false);  // Show database errors to user

// Environment Detection
define('ENVIRONMENT', 'development');  // 'production' or 'development'

// Error Logging
define('LOG_ERRORS', true);
define('LOG_PATH', './logs/database');

// Ensure logs directory exists
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
?>
