<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/services.php');
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['requested_url'] = $_SERVER['HTTP_REFERER'];
    $_SESSION['login_error'] = 'Please login to request a service.';
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$service_id = intval($_POST['service_id']);
$details = sanitize($_POST['details']);
$budget = floatval($_POST['budget']);
$preferred_date = sanitize($_POST['preferred_date']);
$preferred_time = sanitize($_POST['preferred_time'] ?? '09:00');

// Validate input
$errors = [];

if (!$service_id) {
    $errors[] = 'Invalid service selected.';
}

if (empty($details)) {
    $errors[] = 'Please provide project details.';
}

if ($budget <= 0) {
    $errors[] = 'Please provide a valid budget.';
}

if (empty($preferred_date)) {
    $errors[] = 'Please select preferred start date.';
}

if (!empty($errors)) {
    $_SESSION['request_error'] = implode('<br>', $errors);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Create service request
$request_id = createServiceRequest($user_id, $service_id, $details, $preferred_date, $preferred_time, $budget);

if ($request_id) {
    // Get service details for email
    $service = getService($service_id);
    $user_sql = "SELECT full_name, email FROM users WHERE user_id = $user_id";
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);
    
    // Send confirmation email to user
    $user_subject = "Service Request Confirmation - JED BINARY TECH";
    $user_message = "
    <html>
    <head>
        <title>Service Request Confirmation</title>
    </head>
    <body>
        <h2>Dear {$user['full_name']},</h2>
        <p>Thank you for requesting our service. We have received your request and will contact you shortly.</p>
        
        <h3>Request Details:</h3>
        <p><strong>Service:</strong> {$service['service_name']}</p>
        <p><strong>Budget:</strong> $$budget</p>
        <p><strong>Preferred Start Date:</strong> $preferred_date</p>
        <p><strong>Details:</strong> $details</p>
        
        <p>You can track your request status in your <a href='" . SITE_URL . "/dashboard.php'>dashboard</a>.</p>
        
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>
    ";
    sendEmail($user['email'], $user_subject, $user_message);
    
    // Send notification to admin
    $admin_subject = "New Service Request";
    $admin_message = "
    <html>
    <head>
        <title>New Service Request</title>
    </head>
    <body>
        <h2>New Service Request Received</h2>
        <p><strong>Client:</strong> {$user['full_name']} ({$user['email']})</p>
        <p><strong>Service:</strong> {$service['service_name']}</p>
        <p><strong>Budget:</strong> $$budget</p>
        <p><strong>Preferred Date:</strong> $preferred_date</p>
        <p><strong>Details:</strong> $details</p>
        <p><strong>Request ID:</strong> $request_id</p>
    </body>
    </html>
    ";
    sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
    
    $_SESSION['request_success'] = 'Service request submitted successfully! We will contact you soon.';
} else {
    $_SESSION['request_error'] = 'Failed to submit request. Please try again.';
}

header('Location: ' . SITE_URL . '/dashboard.php');
exit();
?>