<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['login_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password'];
$remember = isset($_POST['remember']);

// Validate input
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Please fill in all fields.';
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Get user from database
$sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Verify password
    if (password_verify($password, $row['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_name'] = $row['full_name'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_type'] = $row['user_type'];
        
        // Update last login
        $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $row['user_id']);
        mysqli_stmt_execute($update_stmt);
        
        // Set remember me cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $token_sql = "UPDATE users SET remember_token = ? WHERE user_id = ?";
            $token_stmt = mysqli_prepare($conn, $token_sql);
            mysqli_stmt_bind_param($token_stmt, "si", $token, $row['user_id']);
            mysqli_stmt_execute($token_stmt);
            
            setcookie('remember_token', $token, $expiry, '/');
        }
        
        // Redirect to dashboard or requested page
        $redirect = isset($_SESSION['requested_url']) ? $_SESSION['requested_url'] : '/dashboard.php';
        unset($_SESSION['requested_url']);
        redirect($redirect);
    } else {
        $_SESSION['login_error'] = 'Invalid email or password.';
    }
} else {
    $_SESSION['login_error'] = 'Invalid email or password.';
}

header('Location: ' . SITE_URL . '/login.php');
exit();
?>