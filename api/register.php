<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/register.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['register_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . SITE_URL . '/register.php');
    exit();
}

// Get and sanitize input
$full_name = sanitize($_POST['full_name']);
$email = sanitize($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$phone = sanitize($_POST['phone']);
$address = sanitize($_POST['address']);
$city = sanitize($_POST['city']);
$terms = isset($_POST['terms']);

// Validate input
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Full name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}

if (empty($password) || strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!$terms) {
    $errors[] = 'You must agree to the terms and conditions.';
}

// Check if email already exists
$check_sql = "SELECT user_id FROM users WHERE email = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    $errors[] = 'Email already registered. Please login.';
}

if (!empty($errors)) {
    $_SESSION['register_error'] = implode('<br>', $errors);
    header('Location: ' . SITE_URL . '/register.php');
    exit();
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$sql = "INSERT INTO users (full_name, email, password_hash, phone, address, city, registration_date) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $email, $password_hash, $phone, $address, $city);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['register_success'] = 'Registration successful! Please login.';
    
    // Send welcome email
    $subject = "Welcome to JED BINARY TECH SOLUTIONS";
    $message = "
    <html>
    <head>
        <title>Welcome to JED BINARY TECH</title>
    </head>
    <body>
        <h2>Welcome $full_name!</h2>
        <p>Thank you for registering with JED BINARY TECH SOLUTIONS. We're excited to have you on board!</p>
        <p>You can now:</p>
        <ul>
            <li>Request our services</li>
            <li>Track your service requests</li>
            <li>Access your dashboard</li>
            <li>Get priority support</li>
        </ul>
        <p><a href='" . SITE_URL . "/login.php'>Click here to login</a></p>
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>
    ";
    
    sendEmail($email, $subject, $message);
    
    header('Location: ' . SITE_URL . '/login.php');
} else {
    $_SESSION['register_error'] = 'Registration failed. Please try again.';
    header('Location: ' . SITE_URL . '/register.php');
}

exit();
?>