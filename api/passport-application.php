<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['application_error'] = 'Please login to submit passport application.';
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/passport-assistance.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['application_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . SITE_URL . '/passport-assistance.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get and sanitize form data
$full_name = sanitize($_POST['full_name']);
$date_of_birth = sanitize($_POST['date_of_birth']);
$place_of_birth = sanitize($_POST['place_of_birth']);
$gender = sanitize($_POST['gender']);
$mobile = sanitize($_POST['mobile']);
$email = sanitize($_POST['email']);
$address_line1 = sanitize($_POST['address_line1']);
$address_line2 = sanitize($_POST['address_line2']);
$city = sanitize($_POST['city']);
$state = sanitize($_POST['state']);
$pincode = sanitize($_POST['pincode']);
$passport_type = sanitize($_POST['passport_type']);
$current_passport_number = isset($_POST['current_passport_number']) ? sanitize($_POST['current_passport_number']) : null;
$application_type = sanitize($_POST['application_type']);
$preferred_appointment_date = !empty($_POST['preferred_appointment_date']) ? sanitize($_POST['preferred_appointment_date']) : null;
$special_requirements = sanitize($_POST['special_requirements']);
$how_heard = sanitize($_POST['how_heard']);
$package = sanitize($_POST['package']);

// Validate required fields
$errors = [];

if (empty($full_name)) $errors[] = 'Full name is required.';
if (empty($date_of_birth)) $errors[] = 'Date of birth is required.';
if (empty($place_of_birth)) $errors[] = 'Place of birth is required.';
if (empty($gender)) $errors[] = 'Gender is required.';
if (empty($mobile)) $errors[] = 'Mobile number is required.';
if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = 'Invalid mobile number.';
if (empty($address_line1)) $errors[] = 'Address is required.';
if (empty($city)) $errors[] = 'City is required.';
if (empty($state)) $errors[] = 'State is required.';
if (empty($pincode) || !preg_match('/^[0-9]{6}$/', $pincode)) $errors[] = 'Valid pincode is required.';
if ($passport_type == 'renewal' && empty($current_passport_number)) $errors[] = 'Current passport number is required for renewal.';

// Handle file uploads
$upload_dir = '../uploads/passport_documents/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Upload proof of address
$proof_of_address = '';
if (isset($_FILES['proof_of_address']) && $_FILES['proof_of_address']['error'] == 0) {
    $result = uploadFile($_FILES['proof_of_address'], $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
    if ($result['success']) {
        $proof_of_address = $result['filename'];
    } else {
        $errors[] = 'Proof of address: ' . $result['message'];
    }
} else {
    $errors[] = 'Proof of address is required.';
}

// Upload proof of identity
$proof_of_identity = '';
if (isset($_FILES['proof_of_identity']) && $_FILES['proof_of_identity']['error'] == 0) {
    $result = uploadFile($_FILES['proof_of_identity'], $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
    if ($result['success']) {
        $proof_of_identity = $result['filename'];
    } else {
        $errors[] = 'Proof of identity: ' . $result['message'];
    }
} else {
    $errors[] = 'Proof of identity is required.';
}

// Upload date of birth proof
$date_of_birth_proof = '';
if (isset($_FILES['date_of_birth_proof']) && $_FILES['date_of_birth_proof']['error'] == 0) {
    $result = uploadFile($_FILES['date_of_birth_proof'], $upload_dir, ['pdf', 'jpg', 'jpeg', 'png']);
    if ($result['success']) {
        $date_of_birth_proof = $result['filename'];
    } else {
        $errors[] = 'Date of birth proof: ' . $result['message'];
    }
} else {
    $errors[] = 'Date of birth proof is required.';
}

// Upload passport photo
$passport_photo = '';
if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] == 0) {
    $result = uploadFile($_FILES['passport_photo'], $upload_dir, ['jpg', 'jpeg', 'png']);
    if ($result['success']) {
        $passport_photo = $result['filename'];
    } else {
        $errors[] = 'Passport photo: ' . $result['message'];
    }
} else {
    $errors[] = 'Passport photo is required.';
}

// If there are errors, redirect back
if (!empty($errors)) {
    $_SESSION['application_error'] = implode('<br>', $errors);
    header('Location: ' . SITE_URL . '/passport-assistance.php');
    exit();
}

// Generate application number
$application_number = 'PASS-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

// Insert into database
$sql = "INSERT INTO passport_assistance (
    user_id, full_name, date_of_birth, place_of_birth, gender,
    mobile, email, address_line1, address_line2, city, state, pincode,
    passport_type, current_passport_number, application_type,
    preferred_appointment_date, special_requirements, how_heard,
    package, application_number, proof_of_address, proof_of_identity,
    date_of_birth_proof, passport_photo, status, documents_status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'application_initiated', 'pending')";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isssssssssssssssssssssss", 
    $user_id, $full_name, $date_of_birth, $place_of_birth, $gender,
    $mobile, $email, $address_line1, $address_line2, $city, $state, $pincode,
    $passport_type, $current_passport_number, $application_type,
    $preferred_appointment_date, $special_requirements, $how_heard,
    $package, $application_number, $proof_of_address, $proof_of_identity,
    $date_of_birth_proof, $passport_photo
);

if (mysqli_stmt_execute($stmt)) {
    $passport_id = mysqli_insert_id($conn);
    
    // Send confirmation email to user
    $subject = "Passport Assistance Application Received - JED BINARY TECH";
    $message = "
    <html>
    <head>
        <title>Application Received</title>
    </head>
    <body>
        <h2>Dear $full_name,</h2>
        <p>Thank you for choosing JED BINARY TECH for your passport assistance. We have received your application and will begin processing shortly.</p>
        
        <h3>Application Details:</h3>
        <p><strong>Application Number:</strong> $application_number</p>
        <p><strong>Application Type:</strong> " . ucfirst($passport_type) . " Passport</p>
        <p><strong>Package:</strong> " . ucfirst($package) . "</p>
        <p><strong>Submitted on:</strong> " . date('d M Y H:i') . "</p>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Our team will verify your documents within 24 hours</li>
            <li>You will receive a call to confirm your details</li>
            <li>We will schedule your passport office appointment</li>
            <li>We'll guide you through the complete process</li>
        </ol>
        
        <p>You can track your application status in your <a href='" . SITE_URL . "/dashboard.php'>dashboard</a>.</p>
        
        <p>For any queries, contact us at support@jedbinary.com or call +91 9876543210</p>
        
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>
    ";
    
    sendEmail($email, $subject, $message);
    
    // Send notification to admin
    $admin_subject = "New Passport Assistance Application";
    $admin_message = "
    <html>
    <head>
        <title>New Passport Application</title>
    </head>
    <body>
        <h2>New Passport Assistance Application</h2>
        <p><strong>Application Number:</strong> $application_number</p>
        <p><strong>Name:</strong> $full_name</p>
        <p><strong>Mobile:</strong> $mobile</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Type:</strong> " . ucfirst($passport_type) . "</p>
        <p><strong>Package:</strong> " . ucfirst($package) . "</p>
        <p><a href='" . SITE_URL . "/admin/passport-details.php?id=$passport_id'>View Application</a></p>
    </body>
    </html>
    ";
    
    sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
    
    $_SESSION['application_success'] = "Application submitted successfully! Your application number is: $application_number";
} else {
    $_SESSION['application_error'] = 'Failed to submit application. Please try again.';
}

header('Location: ' . SITE_URL . '/passport-assistance.php');
exit();
?>