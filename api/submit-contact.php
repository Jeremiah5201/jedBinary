<!-- submit-contact.php placeholder -->
 <?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/contact.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['contact_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . SITE_URL . '/contact.php');
    exit();
}

// Get and sanitize input
$name = sanitize($_POST['name']);
$email = sanitize($_POST['email']);
$phone = sanitize($_POST['phone']);
$service_interest = sanitize($_POST['service_interest']);
$subject = sanitize($_POST['subject']);
$message = sanitize($_POST['message']);

// Validate input
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

if (!empty($errors)) {
    $_SESSION['contact_error'] = implode('<br>', $errors);
    header('Location: ' . SITE_URL . '/contact.php');
    exit();
}

// Insert into database
$sql = "INSERT INTO contact_messages (name, email, phone, service_interest, subject, message) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $phone, $service_interest, $subject, $message);

if (mysqli_stmt_execute($stmt)) {
    // Send confirmation email to user
    $user_subject = "Thank you for contacting JED BINARY TECH";
    $user_message = "
    <html>
    <head>
        <title>Thank you for contacting us</title>
    </head>
    <body>
        <h2>Dear $name,</h2>
        <p>Thank you for contacting JED BINARY TECH SOLUTIONS. We have received your message and will get back to you within 24 hours.</p>
        <p><strong>Your message:</strong></p>
        <p>$message</p>
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>
    ";
    sendEmail($email, $user_subject, $user_message);
    
    // Send notification to admin
    $admin_subject = "New Contact Form Submission";
    $admin_message = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
    </head>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Service Interest:</strong> $service_interest</p>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Message:</strong></p>
        <p>$message</p>
    </body>
    </html>
    ";
    sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
    
    $_SESSION['contact_success'] = 'Thank you for contacting us. We will get back to you soon!';
} else {
    $_SESSION['contact_error'] = 'Failed to send message. Please try again.';
}

header('Location: ' . SITE_URL . '/contact.php');
exit();
?>