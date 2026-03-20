<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Get user details
$sql = "SELECT user_id, full_name, email, phone, address, city, state, pincode, 
               user_type, is_active, registration_date, last_login 
        FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);

if ($user = mysqli_fetch_assoc($result)) {
    // Get user statistics
    $stats_sql = "SELECT 
                    (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id) as total_requests,
                    (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id AND status = 'completed') as completed_requests,
                    (SELECT SUM(budget) FROM service_requests WHERE user_id = $user_id AND status = 'completed') as total_spent,
                    (SELECT COUNT(*) FROM passport_assistance WHERE user_id = $user_id) as passport_applications
                  FROM dual";
    $stats_result = mysqli_query($conn, $stats_sql);
    $stats = mysqli_fetch_assoc($stats_result);
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'stats' => $stats
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>