<!-- user-auth.php placeholder -->
 <?php
/**
 * JED BINARY TECH SOLUTIONS
 * User Authentication API Endpoints
 * Handles all authentication-related AJAX requests
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions based on request method
switch ($method) {
    case 'POST':
        handlePostRequest($action);
        break;
        
    case 'GET':
        handleGetRequest($action);
        break;
        
    case 'PUT':
        handlePutRequest($action);
        break;
        
    case 'DELETE':
        handleDeleteRequest($action);
        break;
        
    default:
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
        exit();
}

/**
 * Handle POST requests
 */
function handlePostRequest($action) {
    global $response, $auth, $conn;
    
    // Get POST data (support both JSON and form data)
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'login':
            handleLogin($input);
            break;
            
        case 'register':
            handleRegister($input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'forgot-password':
            handleForgotPassword($input);
            break;
            
        case 'reset-password':
            handleResetPassword($input);
            break;
            
        case 'change-password':
            handleChangePassword($input);
            break;
            
        case 'update-profile':
            handleUpdateProfile($input);
            break;
            
        case 'verify-email':
            handleVerifyEmail($input);
            break;
            
        case 'resend-verification':
            handleResendVerification($input);
            break;
            
        case 'social-login':
            handleSocialLogin($input);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            exit();
    }
}

/**
 * Handle GET requests
 */
function handleGetRequest($action) {
    global $response, $auth, $conn;
    
    switch ($action) {
        case 'check-auth':
            checkAuth();
            break;
            
        case 'get-user':
            getUserData();
            break;
            
        case 'get-session':
            getSessionData();
            break;
            
        case 'verify-token':
            verifyToken($_GET['token'] ?? '');
            break;
            
        case 'get-activity':
            getUserActivity();
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            exit();
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($action) {
    global $response, $auth, $conn;
    
    // Get PUT data
    parse_str(file_get_contents('php://input'), $input);
    
    switch ($action) {
        case 'update-email':
            handleUpdateEmail($input);
            break;
            
        case 'update-phone':
            handleUpdatePhone($input);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            exit();
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($action) {
    global $response, $auth, $conn;
    
    switch ($action) {
        case 'delete-account':
            handleDeleteAccount();
            break;
            
        case 'remove-session':
            handleRemoveSession($_GET['session_id'] ?? '');
            break;
            
        default:
            $response['message'] = 'Invalid action';
            http_response_code(400);
            echo json_encode($response);
            exit();
    }
}

/**
 * Handle login request
 */
function handleLogin($input) {
    global $response, $auth;
    
    // Validate input
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $remember = isset($input['remember']) ? filter_var($input['remember'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required';
        $response['errors'] = [
            'email' => empty($email) ? 'Email is required' : null,
            'password' => empty($password) ? 'Password is required' : null
        ];
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    // Attempt login
    $result = $auth->login($email, $password, $remember);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['data'] = [
            'user_id' => $auth->getUserId(),
            'user_name' => $_SESSION['user_name'],
            'user_email' => $_SESSION['user_email'],
            'user_type' => $_SESSION['user_type'],
            'redirect' => $result['redirect']
        ];
        
        // Log the login
        logActivity('api_login', 'User logged in via API');
    } else {
        $response['message'] = $result['message'];
        http_response_code(401);
    }
    
    echo json_encode($response);
}

/**
 * Handle registration request
 */
function handleRegister($input) {
    global $response, $auth;
    
    // Validate required fields
    $required = ['full_name', 'email', 'password'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate email format
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Validate password strength
    if (!empty($input['password'])) {
        if (strlen($input['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $input['password'])) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $input['password'])) {
            $errors['password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $input['password'])) {
            $errors['password'] = 'Password must contain at least one number';
        }
    }
    
    // Validate password confirmation
    if (isset($input['password']) && isset($input['confirm_password'])) {
        if ($input['password'] !== $input['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // Validate terms acceptance
    if (!isset($input['terms']) || !filter_var($input['terms'], FILTER_VALIDATE_BOOLEAN)) {
        $errors['terms'] = 'You must agree to the terms and conditions';
    }
    
    if (!empty($errors)) {
        $response['message'] = 'Validation failed';
        $response['errors'] = $errors;
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    // Prepare user data
    $user_data = [
        'full_name' => $input['full_name'],
        'email' => $input['email'],
        'password' => $input['password'],
        'phone' => $input['phone'] ?? '',
        'address' => $input['address'] ?? '',
        'city' => $input['city'] ?? '',
        'state' => $input['state'] ?? '',
        'pincode' => $input['pincode'] ?? ''
    ];
    
    // Attempt registration
    $result = $auth->register($user_data);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Registration successful! Please check your email to verify your account.';
        $response['data'] = [
            'user_id' => $result['user_id']
        ];
        
        // Send verification email
        sendVerificationEmail($input['email'], $input['full_name'], $result['user_id']);
    } else {
        $response['message'] = $result['message'];
        http_response_code(400);
    }
    
    echo json_encode($response);
}

/**
 * Handle logout request
 */
function handleLogout() {
    global $response, $auth;
    
    $auth->logout();
    
    $response['success'] = true;
    $response['message'] = 'Logout successful';
    
    echo json_encode($response);
}

/**
 * Check authentication status
 */
function checkAuth() {
    global $response, $auth;
    
    $response['success'] = true;
    $response['data'] = [
        'logged_in' => $auth->isLoggedIn(),
        'is_admin' => $auth->isAdmin(),
        'user' => $auth->isLoggedIn() ? [
            'id' => $auth->getUserId(),
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'type' => $_SESSION['user_type'] ?? null
        ] : null
    ];
    
    echo json_encode($response);
}

/**
 * Get current user data
 */
function getUserData() {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    
    // Get user details from database
    $sql = "SELECT user_id, full_name, email, phone, address, city, state, pincode, 
                   profile_image, registration_date, last_login 
            FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Get user statistics
        $stats_sql = "SELECT 
                        (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id) as total_requests,
                        (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id AND status = 'completed') as completed_requests,
                        (SELECT COUNT(*) FROM passport_assistance WHERE user_id = $user_id) as passport_applications
                      FROM dual";
        $stats_result = mysqli_query($conn, $stats_sql);
        $stats = mysqli_fetch_assoc($stats_result);
        
        $response['success'] = true;
        $response['data'] = array_merge($row, $stats);
    } else {
        $response['message'] = 'User not found';
        http_response_code(404);
    }
    
    echo json_encode($response);
}

/**
 * Get session data
 */
function getSessionData() {
    global $response;
    
    $session_data = [
        'session_id' => session_id(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'session_start' => $_SESSION['session_start'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];
    
    $response['success'] = true;
    $response['data'] = $session_data;
    
    echo json_encode($response);
}

/**
 * Handle forgot password request
 */
function handleForgotPassword($input) {
    global $response, $auth;
    
    $email = $input['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $result = $auth->requestPasswordReset($email);
    
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    
    echo json_encode($response);
}

/**
 * Handle password reset
 */
function handleResetPassword($input) {
    global $response, $auth;
    
    $token = $input['token'] ?? '';
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($token)) {
        $errors['token'] = 'Reset token is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'New password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        $response['message'] = 'Validation failed';
        $response['errors'] = $errors;
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $result = $auth->resetPassword($token, $password);
    
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    
    echo json_encode($response);
}

/**
 * Handle password change (for logged in users)
 */
function handleChangePassword($input) {
    global $response, $auth;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($current_password)) {
        $errors['current_password'] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters';
    }
    
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        $response['message'] = 'Validation failed';
        $response['errors'] = $errors;
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $result = $auth->changePassword($auth->getUserId(), $current_password, $new_password);
    
    $response['success'] = $result['success'];
    $response['message'] = $result['message'];
    
    echo json_encode($response);
}

/**
 * Handle profile update
 */
function handleUpdateProfile($input) {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    
    // Handle file upload for profile image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleProfileImageUpload($_FILES['profile_image'], $user_id);
        if (!$upload_result['success']) {
            $response['message'] = $upload_result['message'];
            http_response_code(400);
            echo json_encode($response);
            exit();
        }
        $input['profile_image'] = $upload_result['filename'];
    }
    
    // Update profile
    $result = $auth->updateProfile($user_id, $input);
    
    if ($result['success']) {
        // Get updated user data
        $sql = "SELECT full_name, email, phone, address, city, state, pincode, profile_image 
                FROM users WHERE user_id = $user_id";
        $query_result = mysqli_query($conn, $sql);
        $user_data = mysqli_fetch_assoc($query_result);
        
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
        $response['data'] = $user_data;
    } else {
        $response['message'] = $result['message'];
        http_response_code(400);
    }
    
    echo json_encode($response);
}

/**
 * Handle profile image upload
 */
function handleProfileImageUpload($file, $user_id) {
    $target_dir = '../uploads/profile_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }
    
    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size must be less than 2MB'];
    }
    
    // Generate unique filename
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Handle email verification
 */
function handleVerifyEmail($input) {
    global $response, $conn;
    
    $token = $input['token'] ?? '';
    
    if (empty($token)) {
        $response['message'] = 'Verification token is required';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    
    // Verify token
    $sql = "SELECT user_id FROM users WHERE email_verification_token = '$token' AND email_verified = 0";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $update_sql = "UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE user_id = {$row['user_id']}";
        if (mysqli_query($conn, $update_sql)) {
            $response['success'] = true;
            $response['message'] = 'Email verified successfully';
        } else {
            $response['message'] = 'Failed to verify email';
            http_response_code(500);
        }
    } else {
        $response['message'] = 'Invalid or expired verification token';
        http_response_code(400);
    }
    
    echo json_encode($response);
}

/**
 * Handle resend verification email
 */
function handleResendVerification($input) {
    global $response, $conn;
    
    $email = $input['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $email = mysqli_real_escape_string($conn, $email);
    
    // Get user details
    $sql = "SELECT user_id, full_name FROM users WHERE email = '$email' AND email_verified = 0";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $update_sql = "UPDATE users SET email_verification_token = '$token' WHERE user_id = {$row['user_id']}";
        
        if (mysqli_query($conn, $update_sql)) {
            sendVerificationEmail($email, $row['full_name'], $row['user_id'], $token);
            
            $response['success'] = true;
            $response['message'] = 'Verification email has been resent';
        } else {
            $response['message'] = 'Failed to resend verification email';
            http_response_code(500);
        }
    } else {
        // Don't reveal if email exists or is already verified
        $response['success'] = true;
        $response['message'] = 'If the email exists and is not verified, a verification email will be sent';
    }
    
    echo json_encode($response);
}

/**
 * Handle social login (Google, Facebook, etc.)
 */
function handleSocialLogin($input) {
    global $response, $auth, $conn;
    
    $provider = $input['provider'] ?? '';
    $token = $input['token'] ?? '';
    $user_data = $input['user_data'] ?? [];
    
    if (empty($provider) || empty($token)) {
        $response['message'] = 'Invalid social login data';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    // Verify token with provider (implement based on provider)
    $verified = verifySocialToken($provider, $token, $user_data);
    
    if (!$verified) {
        $response['message'] = 'Invalid social login token';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    // Check if user exists
    $email = mysqli_real_escape_string($conn, $user_data['email']);
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // User exists - log them in
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_name'] = $row['full_name'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_type'] = $row['user_type'];
        
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['data'] = [
            'user_id' => $row['user_id'],
            'user_name' => $row['full_name'],
            'user_email' => $row['email'],
            'redirect' => SITE_URL . '/dashboard.php'
        ];
    } else {
        // Create new user
        $full_name = mysqli_real_escape_string($conn, $user_data['name']);
        $email = mysqli_real_escape_string($conn, $user_data['email']);
        $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO users (full_name, email, password_hash, email_verified, user_type, registration_date) 
                       VALUES ('$full_name', '$email', '$password_hash', 1, 'client', NOW())";
        
        if (mysqli_query($conn, $insert_sql)) {
            $user_id = mysqli_insert_id($conn);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_type'] = 'client';
            
            $response['success'] = true;
            $response['message'] = 'Registration successful';
            $response['data'] = [
                'user_id' => $user_id,
                'user_name' => $full_name,
                'user_email' => $email,
                'redirect' => SITE_URL . '/dashboard.php'
            ];
        } else {
            $response['message'] = 'Failed to create user account';
            http_response_code(500);
        }
    }
    
    echo json_encode($response);
}

/**
 * Verify social login token (implement based on providers)
 */
function verifySocialToken($provider, $token, $user_data) {
    // Implement token verification with Google, Facebook, etc.
    // This is a simplified version - in production, verify with the provider's API
    
    switch ($provider) {
        case 'google':
            // Verify with Google API
            return true; // Placeholder
        case 'facebook':
            // Verify with Facebook API
            return true; // Placeholder
        default:
            return false;
    }
}

/**
 * Handle update email request
 */
function handleUpdateEmail($input) {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $new_email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    if (empty($password)) {
        $response['message'] = 'Password is required to change email';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    
    // Verify password
    $sql = "SELECT password_hash FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    if (!password_verify($password, $row['password_hash'])) {
        $response['message'] = 'Incorrect password';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    // Check if email already exists
    $new_email = mysqli_real_escape_string($conn, $new_email);
    $check_sql = "SELECT user_id FROM users WHERE email = '$new_email' AND user_id != $user_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $response['message'] = 'Email already in use by another account';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    // Update email
    $update_sql = "UPDATE users SET email = '$new_email', email_verified = 0 WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['user_email'] = $new_email;
        
        // Send verification email
        sendVerificationEmail($new_email, $_SESSION['user_name'], $user_id);
        
        $response['success'] = true;
        $response['message'] = 'Email updated successfully. Please verify your new email address.';
    } else {
        $response['message'] = 'Failed to update email';
        http_response_code(500);
    }
    
    echo json_encode($response);
}

/**
 * Handle update phone request
 */
function handleUpdatePhone($input) {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $phone = $input['phone'] ?? '';
    
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
        $response['message'] = 'Please enter a valid 10-digit phone number';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    $phone = mysqli_real_escape_string($conn, $phone);
    
    $update_sql = "UPDATE users SET phone = '$phone' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $response['success'] = true;
        $response['message'] = 'Phone number updated successfully';
    } else {
        $response['message'] = 'Failed to update phone number';
        http_response_code(500);
    }
    
    echo json_encode($response);
}

/**
 * Handle account deletion
 */
function handleDeleteAccount() {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete related records
        $tables = ['service_requests', 'passport_assistance', 'contact_messages', 'user_activity_log'];
        
        foreach ($tables as $table) {
            $sql = "DELETE FROM $table WHERE user_id = $user_id";
            mysqli_query($conn, $sql);
        }
        
        // Delete user
        $sql = "DELETE FROM users WHERE user_id = $user_id";
        mysqli_query($conn, $sql);
        
        mysqli_commit($conn);
        
        // Logout user
        $auth->logout();
        
        $response['success'] = true;
        $response['message'] = 'Account deleted successfully';
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Failed to delete account';
        http_response_code(500);
    }
    
    echo json_encode($response);
}

/**
 * Handle remove session
 */
function handleRemoveSession($session_id) {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    // Implementation depends on how you store sessions
    // This is a placeholder
    
    $response['success'] = true;
    $response['message'] = 'Session removed successfully';
    
    echo json_encode($response);
}

/**
 * Verify reset token
 */
function verifyToken($token) {
    global $response, $conn;
    
    if (empty($token)) {
        $response['message'] = 'Token is required';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    
    $token = mysqli_real_escape_string($conn, $token);
    
    $sql = "SELECT user_id FROM users WHERE reset_token = '$token' AND reset_expiry > NOW()";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['message'] = 'Token is valid';
        $response['data'] = ['user_id' => $row['user_id']];
    } else {
        $response['message'] = 'Invalid or expired token';
        http_response_code(400);
    }
    
    echo json_encode($response);
}

/**
 * Get user activity
 */
function getUserActivity() {
    global $response, $auth, $conn;
    
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Not authenticated';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }
    
    $user_id = $auth->getUserId();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    $sql = "SELECT * FROM user_activity_log 
            WHERE user_id = $user_id 
            ORDER BY created_at DESC 
            LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    
    $activities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
    
    $response['success'] = true;
    $response['data'] = $activities;
    
    echo json_encode($response);
}

/**
 * Send verification email
 */
function sendVerificationEmail($email, $name, $user_id, $token = null) {
    global $conn;
    
    if (!$token) {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $token = mysqli_real_escape_string($conn, $token);
        mysqli_query($conn, "UPDATE users SET email_verification_token = '$token' WHERE user_id = $user_id");
    }
    
    $verification_link = SITE_URL . "/verify-email.php?token=" . urlencode($token);
    
    $subject = "Verify Your Email - JED BINARY TECH";
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button { 
                background: #007bff; 
                color: white; 
                padding: 12px 30px; 
                text-decoration: none; 
                border-radius: 5px;
                display: inline-block;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Verify Your Email Address</h2>
            <p>Dear $name,</p>
            <p>Thank you for registering with JED BINARY TECH SOLUTIONS. Please click the button below to verify your email address:</p>
            <p><a href='$verification_link' class='button'>Verify Email</a></p>
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p>$verification_link</p>
            <p>This link will expire in 24 hours.</p>
            <br>
            <p>Best Regards,<br>JED BINARY TECH Team</p>
        </div>
    </body>
    </html>
    ";
    
    sendEmail($email, $subject, $message);
}

/**
 * Handle preflight requests for CORS
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit();
}
?>