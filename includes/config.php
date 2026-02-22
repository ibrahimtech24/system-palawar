<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '12345678');
define('DB_NAME', 'poultry_system');

// Site Configuration
define('SITE_NAME', 'سیستەمی بەڕێوەبردنی هەوێردە');
define('SITE_URL', 'http://localhost/system_basir');define('CURRENCY', 'د.ع');
// Timezone
date_default_timezone_set('Asia/Baghdad');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
