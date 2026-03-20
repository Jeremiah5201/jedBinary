<!-- auth.php placeholder -->
 <?php
/**
 * JED BINARY TECH SOLUTIONS
 * Authentication and Authorization Management
 */

// Prevent direct access
if (!defined('SITE_URL')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed.');
}

/**
 * User Authentication Class
 */
class Auth {
    
    private $conn;
    private $user_id;
    private $user_data;
    private $logged_in = false;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->checkSession();
        $this->checkRememberMe();
    }
    
    /**
     * Check if user is logged in via session
     */
    private function checkSession() {
        if (isset($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->logged_in = true;
            $this->loadUserData();
        }
    }
    
    /**
     * Check remember me cookie
     */
    private function checkRememberMe() {
        if (!$this->logged_in && isset($_COOKIE['remember_token'])) {
            $token = mysqli_real_escape_string($this->conn, $_COOKIE['remember_token']);
            
            $sql = "SELECT user_id FROM users WHERE remember_token = '$token' AND is_active = 1";
            $result = mysqli_query($this->conn, $sql);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $this->user_id = $row['user_id'];
                $this->logged_in = true;
                $this->loadUserData();
                
                // Regenerate session
                $_SESSION['user_id'] = $this->user_id;
                $_SESSION['user_name'] = $this->user_data['full_name'];
                $_SESSION['user_email'] = $this->user_data['email'];
                $_SESSION['user_type'] = $this->user_data['user_type'];
            }
        }
    }
    
    /**
     * Load user data from database
     */
    private function loadUserData() {
        $sql = "SELECT * FROM users WHERE user_id = {$this->user_id} AND is_active = 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $this->user_data = $row;
        } else {
            $this->logout();
        }
    }
    
    /**
     * Attempt user login
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Remember me flag
     * @return array Result with status and message
     */
    public function login($email, $password, $remember = false) {
        $response = [
            'success' => false,
            'message' => '',
            'redirect' => ''
        ];
        
        // Validate input
        if (empty($email) || empty($password)) {
            $response['message'] = 'Email and password are required.';
            return $response;
        }
        
        // Sanitize email
        $email = mysqli_real_escape_string($this->conn, $email);
        
        // Get user from database
        $sql = "SELECT * FROM users WHERE email = '$email' AND is_active = 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $row['password_hash'])) {
                // Check account status
                if ($row['is_active'] == 0) {
                    $response['message'] = 'Your account has been deactivated. Please contact admin.';
                    return $response;
                }
                
                // Set session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_type'] = $row['user_type'];
                
                $this->user_id = $row['user_id'];
                $this->user_data = $row;
                $this->logged_in = true;
                
                // Update last login
                $this->updateLastLogin($row['user_id']);
                
                // Handle remember me
                if ($remember) {
                    $this->setRememberMe($row['user_id']);
                }
                
                // Clear failed attempts
                $this->clearFailedAttempts($email);
                
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                $response['redirect'] = $this->getRedirectUrl();
                
                // Log successful login
                $this->logActivity($row['user_id'], 'login', 'User logged in successfully');
            } else {
                // Log failed attempt
                $this->logFailedAttempt($email);
                $response['message'] = 'Invalid email or password.';
            }
        } else {
            $response['message'] = 'Invalid email or password.';
        }
        
        return $response;
    }
    
    /**
     * Register new user
     * @param array $user_data User registration data
     * @return array Result with status and message
     */
    public function register($user_data) {
        $response = [
            'success' => false,
            'message' => '',
            'user_id' => null
        ];
        
        // Validate required fields
        $required = ['full_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($user_data[$field])) {
                $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                return $response;
            }
        }
        
        // Validate email
        if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address.';
            return $response;
        }
        
        // Validate password strength
        $password_strength = $this->checkPasswordStrength($user_data['password']);
        if ($password_strength < 3) {
            $response['message'] = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
            return $response;
        }
        
        // Check if email already exists
        $email = mysqli_real_escape_string($this->conn, $user_data['email']);
        $check_sql = "SELECT user_id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($this->conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $response['message'] = 'Email already registered. Please login.';
            return $response;
        }
        
        // Hash password
        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
        
        // Prepare fields for insertion
        $fields = [
            'full_name' => mysqli_real_escape_string($this->conn, $user_data['full_name']),
            'email' => $email,
            'password_hash' => "'$password_hash'",
            'phone' => isset($user_data['phone']) ? "'" . mysqli_real_escape_string($this->conn, $user_data['phone']) . "'" : 'NULL',
            'address' => isset($user_data['address']) ? "'" . mysqli_real_escape_string($this->conn, $user_data['address']) . "'" : 'NULL',
            'city' => isset($user_data['city']) ? "'" . mysqli_real_escape_string($this->conn, $user_data['city']) . "'" : 'NULL',
            'state' => isset($user_data['state']) ? "'" . mysqli_real_escape_string($this->conn, $user_data['state']) . "'" : 'NULL',
            'pincode' => isset($user_data['pincode']) ? "'" . mysqli_real_escape_string($this->conn, $user_data['pincode']) . "'" : 'NULL',
            'user_type' => "'client'",
            'registration_date' => 'NOW()',
            'is_active' => '1'
        ];
        
        // Build insert query
        $sql = "INSERT INTO users (" . implode(', ', array_keys($fields)) . ") 
                VALUES (" . implode(', ', array_values($fields)) . ")";
        
        if (mysqli_query($this->conn, $sql)) {
            $user_id = mysqli_insert_id($this->conn);
            
            $response['success'] = true;
            $response['message'] = 'Registration successful! Please login.';
            $response['user_id'] = $user_id;
            
            // Log registration
            $this->logActivity($user_id, 'register', 'New user registered');
            
            // Send welcome email
            $this->sendWelcomeEmail($user_data['email'], $user_data['full_name']);
        } else {
            $response['message'] = 'Registration failed. Please try again.';
        }
        
        return $response;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear remember me token from database
        if ($this->user_id) {
            $sql = "UPDATE users SET remember_token = NULL WHERE user_id = {$this->user_id}";
            mysqli_query($this->conn, $sql);
            
            // Log logout
            $this->logActivity($this->user_id, 'logout', 'User logged out');
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        $this->logged_in = false;
        $this->user_id = null;
        $this->user_data = null;
    }
    
    /**
     * Set remember me token
     * @param int $user_id User ID
     */
    private function setRememberMe($user_id) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        $token = mysqli_real_escape_string($this->conn, $token);
        $sql = "UPDATE users SET remember_token = '$token' WHERE user_id = $user_id";
        mysqli_query($this->conn, $sql);
        
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    /**
     * Update last login timestamp
     * @param int $user_id User ID
     */
    private function updateLastLogin($user_id) {
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = $user_id";
        mysqli_query($this->conn, $sql);
    }
    
    /**
     * Log failed login attempt
     * @param string $email Email used in attempt
     */
    private function logFailedAttempt($email) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = mysqli_real_escape_string($this->conn, $_SERVER['HTTP_USER_AGENT']);
        $email = mysqli_real_escape_string($this->conn, $email);
        
        $sql = "INSERT INTO login_attempts (email, ip_address, user_agent, attempt_time) 
                VALUES ('$email', '$ip', '$user_agent', NOW())";
        mysqli_query($this->conn, $sql);
        
        // Check for too many failed attempts
        $this->checkBruteForce($email);
    }
    
    /**
     * Check for brute force attacks
     * @param string $email Email to check
     */
    private function checkBruteForce($email) {
        $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $email = mysqli_real_escape_string($this->conn, $email);
        
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                WHERE email = '$email' AND attempt_time > '$time_limit'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['attempts'] >= 5) {
            // Temporarily lock account
            $sql = "UPDATE users SET is_active = 0 WHERE email = '$email'";
            mysqli_query($this->conn, $sql);
            
            // Notify admin
            $this->notifyAdmin('Brute Force Attempt', "Account locked for email: $email due to multiple failed attempts.");
        }
    }
    
    /**
     * Clear failed login attempts
     * @param string $email Email
     */
    private function clearFailedAttempts($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $sql = "DELETE FROM login_attempts WHERE email = '$email'";
        mysqli_query($this->conn, $sql);
    }
    
    /**
     * Check password strength
     * @param string $password Password to check
     * @return int Strength score (0-5)
     */
    private function checkPasswordStrength($password) {
        $strength = 0;
        
        if (strlen($password) >= 8) $strength++;
        if (preg_match('/[a-z]/', $password)) $strength++;
        if (preg_match('/[A-Z]/', $password)) $strength++;
        if (preg_match('/[0-9]/', $password)) $strength++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
        
        return $strength;
    }
    
    /**
     * Log user activity
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param string $details Additional details
     */
    public function logActivity($user_id, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = mysqli_real_escape_string($this->conn, $_SERVER['HTTP_USER_AGENT']);
        $action = mysqli_real_escape_string($this->conn, $action);
        $details = mysqli_real_escape_string($this->conn, $details);
        
        $sql = "INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES ($user_id, '$action', '$details', '$ip', '$user_agent', NOW())";
        mysqli_query($this->conn, $sql);
    }
    
    /**
     * Send welcome email to new user
     * @param string $email User email
     * @param string $name User name
     */
    private function sendWelcomeEmail($email, $name) {
        $subject = "Welcome to JED BINARY TECH SOLUTIONS";
        
        $message = "
        <html>
        <head>
            <title>Welcome to JED BINARY TECH</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to JED BINARY TECH SOLUTIONS!</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>Thank you for registering with JED BINARY TECH SOLUTIONS. We're excited to have you on board!</p>
                    
                    <h3>Your Account Benefits:</h3>
                    <ul>
                        <li>Request our services easily</li>
                        <li>Track your service requests in real-time</li>
                        <li>Access exclusive resources and support</li>
                        <li>Get priority assistance for urgent needs</li>
                    </ul>
                    
                    <p>To get started, <a href='" . SITE_URL . "/services.php'>browse our services</a> or <a href='" . SITE_URL . "/contact.php'>contact us</a> for any questions.</p>
                    
                    <p>Best Regards,<br>
                    <strong>JED BINARY TECH Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " JED BINARY TECH SOLUTIONS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        sendEmail($email, $subject, $message);
    }
    
    /**
     * Notify admin
     * @param string $subject Email subject
     * @param string $message Email message
     */
    private function notifyAdmin($subject, $message) {
        sendEmail(ADMIN_EMAIL, "Admin Alert: $subject", $message);
    }
    
    /**
     * Get redirect URL after login
     * @return string Redirect URL
     */
    private function getRedirectUrl() {
        if (isset($_SESSION['requested_url'])) {
            $url = $_SESSION['requested_url'];
            unset($_SESSION['requested_url']);
            return $url;
        }
        
        if ($this->isAdmin()) {
            return SITE_URL . '/admin/dashboard.php';
        }
        
        return SITE_URL . '/dashboard.php';
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return $this->logged_in;
    }
    
    /**
     * Check if user is admin
     * @return bool
     */
    public function isAdmin() {
        return $this->logged_in && isset($this->user_data['user_type']) && $this->user_data['user_type'] === 'admin';
    }
    
    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId() {
        return $this->user_id;
    }
    
    /**
     * Get current user data
     * @return array|null
     */
    public function getUserData() {
        return $this->user_data;
    }
    
    /**
     * Get user by ID
     * @param int $user_id User ID
     * @return array|null
     */
    public function getUserById($user_id) {
        $user_id = intval($user_id);
        $sql = "SELECT * FROM users WHERE user_id = $user_id";
        $result = mysqli_query($this->conn, $sql);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Update user profile
     * @param int $user_id User ID
     * @param array $data Profile data
     * @return array Result
     */
    public function updateProfile($user_id, $data) {
        $response = ['success' => false, 'message' => ''];
        
        $updates = [];
        $allowed_fields = ['full_name', 'phone', 'address', 'city', 'state', 'pincode'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $value = mysqli_real_escape_string($this->conn, $data[$field]);
                $updates[] = "$field = '$value'";
            }
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = $user_id";
            
            if (mysqli_query($this->conn, $sql)) {
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully.';
                
                // Log activity
                $this->logActivity($user_id, 'profile_update', 'User updated profile');
            } else {
                $response['message'] = 'Failed to update profile.';
            }
        }
        
        return $response;
    }
    
    /**
     * Change user password
     * @param int $user_id User ID
     * @param string $current_password Current password
     * @param string $new_password New password
     * @return array Result
     */
    public function changePassword($user_id, $current_password, $new_password) {
        $response = ['success' => false, 'message' => ''];
        
        // Verify current password
        $sql = "SELECT password_hash FROM users WHERE user_id = $user_id";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        if (password_verify($current_password, $row['password_hash'])) {
            // Check password strength
            if ($this->checkPasswordStrength($new_password) < 3) {
                $response['message'] = 'New password is not strong enough.';
                return $response;
            }
            
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $new_hash = mysqli_real_escape_string($this->conn, $new_hash);
            
            $update_sql = "UPDATE users SET password_hash = '$new_hash' WHERE user_id = $user_id";
            
            if (mysqli_query($this->conn, $update_sql)) {
                $response['success'] = true;
                $response['message'] = 'Password changed successfully.';
                
                // Log activity
                $this->logActivity($user_id, 'password_change', 'User changed password');
                
                // Send notification email
                $this->sendPasswordChangeEmail($this->user_data['email'], $this->user_data['full_name']);
            } else {
                $response['message'] = 'Failed to change password.';
            }
        } else {
            $response['message'] = 'Current password is incorrect.';
        }
        
        return $response;
    }
    
    /**
     * Send password change notification email
     * @param string $email User email
     * @param string $name User name
     */
    private function sendPasswordChangeEmail($email, $name) {
        $subject = "Your Password Has Been Changed - JED BINARY TECH";
        
        $message = "
        <html>
        <head>
            <title>Password Change Notification</title>
        </head>
        <body>
            <h2>Password Change Notification</h2>
            <p>Dear $name,</p>
            <p>Your password for JED BINARY TECH SOLUTIONS has been successfully changed.</p>
            <p>If you did not make this change, please contact us immediately at security@jedbinary.com</p>
            <br>
            <p>Best Regards,<br>JED BINARY TECH Team</p>
        </body>
        </html>
        ";
        
        sendEmail($email, $subject, $message);
    }
    
    /**
     * Request password reset
     * @param string $email User email
     * @return array Result
     */
    public function requestPasswordReset($email) {
        $response = ['success' => false, 'message' => ''];
        
        $email = mysqli_real_escape_string($this->conn, $email);
        
        // Check if email exists
        $sql = "SELECT user_id, full_name FROM users WHERE email = '$email'";
        $result = mysqli_query($this->conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $token = mysqli_real_escape_string($this->conn, $token);
            $update_sql = "UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE email = '$email'";
            
            if (mysqli_query($this->conn, $update_sql)) {
                // Send reset email
                $this->sendPasswordResetEmail($email, $row['full_name'], $token);
                
                $response['success'] = true;
                $response['message'] = 'Password reset instructions have been sent to your email.';
            }
        } else {
            // Don't reveal if email exists
            $response['success'] = true;
            $response['message'] = 'If the email exists, reset instructions will be sent.';
        }
        
        return $response;
    }
    
    /**
     * Send password reset email
     * @param string $email User email
     * @param string $name User name
     * @param string $token Reset token
     */
    private function sendPasswordResetEmail($email, $name, $token) {
        $reset_link = SITE_URL . "/reset-password.php?token=" . urlencode($token);
        
        $subject = "Password Reset Request - JED BINARY TECH";
        
        $message = "
        <html>
        <head>
            <title>Password Reset Request</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .button { 
                    background: #007bff; 
                    color: white; 
                    padding: 10px 20px; 
                    text-decoration: none; 
                    border-radius: 5px;
                    display: inline-block;
                }
            </style>
        </head>
        <body>
            <h2>Password Reset Request</h2>
            <p>Dear $name,</p>
            <p>We received a request to reset your password for your JED BINARY TECH account.</p>
            <p>Click the button below to reset your password. This link will expire in 1 hour.</p>
            <p><a href='$reset_link' class='button'>Reset Password</a></p>
            <p>If you didn't request this, please ignore this email or contact support.</p>
            <br>
            <p>Best Regards,<br>JED BINARY TECH Team</p>
        </body>
        </html>
        ";
        
        sendEmail($email, $subject, $message);
    }
    
    /**
     * Reset password with token
     * @param string $token Reset token
     * @param string $new_password New password
     * @return array Result
     */
    public function resetPassword($token, $new_password) {
        $response = ['success' => false, 'message' => ''];
        
        $token = mysqli_real_escape_string($this->conn, $token);
        
        // Check token validity
        $sql = "SELECT user_id FROM users 
                WHERE reset_token = '$token' 
                AND reset_expiry > NOW() 
                AND is_active = 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Check password strength
            if ($this->checkPasswordStrength($new_password) < 3) {
                $response['message'] = 'Password is not strong enough.';
                return $response;
            }
            
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_hash = mysqli_real_escape_string($this->conn, $password_hash);
            
            $update_sql = "UPDATE users 
                          SET password_hash = '$password_hash', 
                              reset_token = NULL, 
                              reset_expiry = NULL 
                          WHERE user_id = {$row['user_id']}";
            
            if (mysqli_query($this->conn, $update_sql)) {
                $response['success'] = true;
                $response['message'] = 'Password has been reset successfully. You can now login.';
                
                // Log activity
                $this->logActivity($row['user_id'], 'password_reset', 'User reset password via email');
            }
        } else {
            $response['message'] = 'Invalid or expired reset token.';
        }
        
        return $response;
    }
    
    /**
     * Require authentication - redirect if not logged in
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            $_SESSION['requested_url'] = $_SERVER['REQUEST_URI'];
            $_SESSION['login_error'] = 'Please login to access this page.';
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
    
    /**
     * Require admin privileges
     */
    public function requireAdmin() {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            header('HTTP/1.0 403 Forbidden');
            include  SITE_ROOT . '/403.php';
            exit();
        }
    }
    
    /**
     * Generate CSRF token
     * @return string CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get user activity log
     * @param int $user_id User ID
     * @param int $limit Number of records
     * @return array Activity log
     */
    public function getUserActivity($user_id, $limit = 50) {
        $user_id = intval($user_id);
        $limit = intval($limit);
        
        $sql = "SELECT * FROM user_activity_log 
                WHERE user_id = $user_id 
                ORDER BY created_at DESC 
                LIMIT $limit";
        $result = mysqli_query($this->conn, $sql);
        
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Initialize Auth object
$auth = new Auth($conn);

// Global functions for easy access

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    global $auth;
    return $auth->isAdmin();
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    global $auth;
    return $auth->getUserId();
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    global $auth;
    return $auth->getUserData();
}

/**
 * Require authentication
 */
function requireAuth() {
    global $auth;
    $auth->requireAuth();
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    global $auth;
    $auth->requireAdmin();
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    global $auth;
    return $auth->generateCSRFToken();
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    global $auth;
    return $auth->verifyCSRFToken($token);
}

/**
 * Log user activity
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    global $auth;
    if ($auth->isLoggedIn()) {
        $auth->logActivity($auth->getUserId(), $action, $details);
    }
}
?>