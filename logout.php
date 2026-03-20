<?php
require_once 'includes/config.php';

// Destroy session
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to home page
header('Location: ' . SITE_URL . '/index.php');
exit();
?>