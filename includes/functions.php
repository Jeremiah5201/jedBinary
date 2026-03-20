<?php
// Sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Redirect to specific page
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Get all services
function getServices($limit = null, $category = null) {
    global $conn;
    $sql = "SELECT * FROM services WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '" . sanitize($category) . "'";
    }
    $sql .= " ORDER BY is_featured DESC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = mysqli_query($conn, $sql);
    $services = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['features'] = json_decode($row['features'], true);
        $services[] = $row;
    }
    return $services;
}

// Get single service
function getService($service_id) {
    global $conn;
    $service_id = intval($service_id);
    $sql = "SELECT * FROM services WHERE service_id = $service_id AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $row['features'] = json_decode($row['features'], true);
        return $row;
    }
    return null;
}

// Create service request
function createServiceRequest($user_id, $service_id, $details, $preferred_date, $preferred_time, $budget) {
    global $conn;
    $user_id = intval($user_id);
    $service_id = intval($service_id);
    $details = sanitize($details);
    $preferred_date = sanitize($preferred_date);
    $preferred_time = sanitize($preferred_time);
    $budget = floatval($budget);
    
    $sql = "INSERT INTO service_requests (user_id, service_id, request_details, preferred_date, preferred_time, budget) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisssd", $user_id, $service_id, $details, $preferred_date, $preferred_time, $budget);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    return false;
}

// Get user requests
function getUserRequests($user_id) {
    global $conn;
    $user_id = intval($user_id);
    $sql = "SELECT sr.*, s.service_name, s.icon_class 
            FROM service_requests sr 
            JOIN services s ON sr.service_id = s.service_id 
            WHERE sr.user_id = $user_id 
            ORDER BY sr.request_date DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get portfolio items
function getPortfolio($limit = null, $category = null) {
    global $conn;
    $sql = "SELECT * FROM portfolio WHERE 1=1";
    if ($category) {
        $sql .= " AND category = '" . sanitize($category) . "'";
    }
    $sql .= " ORDER BY is_featured DESC, completion_date DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get testimonials
function getTestimonials($limit = null) {
    global $conn;
    $sql = "SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY testimonial_id DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Send email
function sendEmail($to, $subject, $message, $from = ADMIN_EMAIL) {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Upload file
function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large.'];
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    }
    
    return ['success' => false, 'message' => 'Error uploading file.'];
}

// Get page title
function getPageTitle($page) {
    $titles = [
        'home' => 'Home - JED BINARY TECH SOLUTIONS',
        'services' => 'Our Services - JED BINARY TECH SOLUTIONS',
        'portfolio' => 'Portfolio - JED BINARY TECH SOLUTIONS',
        'contact' => 'Contact Us - JED BINARY TECH SOLUTIONS',
        'about' => 'About Us - JED BINARY TECH SOLUTIONS',
        'login' => 'Login - JED BINARY TECH SOLUTIONS',
        'register' => 'Register - JED BINARY TECH SOLUTIONS',
        'dashboard' => 'Dashboard - JED BINARY TECH SOLUTIONS'
    ];
    
    return isset($titles[$page]) ? $titles[$page] : SITE_NAME;
}
?>