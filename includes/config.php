<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jedbinary_tech');

// Site configuration
define('SITE_NAME', 'JED BINARY TECH SOLUTIONS');
define('SITE_URL', 'http://localhost/jedbinary');
define('ADMIN_EMAIL', 'admin@jedbinary.com');
define('SITE_ROOT', dirname(__DIR__));

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Time zone
date_default_timezone_set('Asia/Kolkata');

// Include functions
require_once 'functions.php';

// Include auth
require_once 'auth.php';
?>
