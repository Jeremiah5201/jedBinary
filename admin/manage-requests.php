<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

$page_title = 'Manage Service Requests';
include '../includes/header.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
    
    $update_sql = "UPDATE service_requests 
                   SET status = '$new_status', 
                       admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n[Admin] " . date('Y-m-d H:i:s') . ": $admin_notes')
                   WHERE request_id = $request_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // Get user email for notification
        $user_sql = "SELECT u.email, u.full_name, s.service_name 
                     FROM service_requests sr
                     JOIN users u ON sr.user_id = u.user_id
                     JOIN services s ON sr.service_id = s.service_id
                     WHERE sr.request_id = $request_id";
        $user_result = mysqli_query($conn, $user_sql);
        $user_data = mysqli_fetch_assoc($user_result);
        
        // Send notification email
        sendStatusNotification($user_data['email'], $user_data['full_name'], 
                              $user_data['service_name'], $new_status, $admin_notes);
        
        $_SESSION['success_message'] = "Request #$request_id status updated to $new_status";
        logActivity('update_request', "Updated request #$request_id status to $new_status");
    } else {
        $_SESSION['error_message'] = "Failed to update request status";
    }
    
    header("Location: manage-requests.php?view=$request_id");
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $request_id = intval($_GET['delete']);
    
    // Check if request exists
    $check_sql = "SELECT request_id FROM service_requests WHERE request_id = $request_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $delete_sql = "DELETE FROM service_requests WHERE request_id = $request_id";
        if (mysqli_query($conn, $delete_sql)) {
            $_SESSION['success_message'] = "Request #$request_id deleted successfully";
            logActivity('delete_request', "Deleted request #$request_id");
        } else {
            $_SESSION['error_message'] = "Failed to delete request";
        }
    }
    
    header("Location: manage-requests.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$service_filter = isset($_GET['service']) ? intval($_GET['service']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

// Build query
$where_conditions = ["1=1"];

if ($status_filter && $status_filter != 'all') {
    $where_conditions[] = "sr.status = '$status_filter'";
}

if ($service_filter > 0) {
    $where_conditions[] = "sr.service_id = $service_filter";
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR sr.request_details LIKE '%$search%')";
}

if ($date_from) {
    $where_conditions[] = "DATE(sr.request_date) >= '$date_from'";
}

if ($date_to) {
    $where_conditions[] = "DATE(sr.request_date) <= '$date_to'";
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM service_requests sr
              JOIN users u ON sr.user_id = u.user_id
              WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get service requests
$sql = "SELECT 
          sr.*,
          u.full_name as user_name,
          u.email as user_email,
          u.phone as user_phone,
          s.service_name,
          s.icon_class
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.user_id
        JOIN services s ON sr.service_id = s.service_id
        WHERE $where_clause
        ORDER BY sr.request_date DESC
        LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get all services for filter
$services_sql = "SELECT service_id, service_name FROM services WHERE is_active = 1 ORDER BY service_name";
$services_result = mysqli_query($conn, $services_sql);
$services = mysqli_fetch_all($services_result, MYSQLI_ASSOC);

// Get single request view if specified
$view_request = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_sql = "SELECT 
                  sr.*,
                  u.full_name as user_name,
                  u.email as user_email,
                  u.phone as user_phone,
                  u.address,
                  u.city,
                  u.state,
                  u.pincode,
                  s.service_name,
                  s.service_id,
                  s.price_range
                FROM service_requests sr
                JOIN users u ON sr.user_id = u.user_id
                JOIN services s ON sr.service_id = s.service_id
                WHERE sr.request_id = $view_id";
    $view_result = mysqli_query($conn, $view_sql);
    $view_request = mysqli_fetch_assoc($view_result);
}
?>

<!-- Admin Header -->
<div class="admin-header bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Manage Service Requests</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="?export=csv" class="btn btn-light me-2">
                    <i class="fas fa-download me-2"></i>Export
                </a>
                <a href="?print=all" class="btn btn-light" onclick="window.print(); return false;">
                    <i class="fas fa-print me-2"></i>Print
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($view_request): ?>
        <!-- Single Request View -->
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Request Details #<?php echo $view_request['request_id']; ?></h5>
                        <div>
                            <a href="manage-requests.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                            <a href="?delete=<?php echo $view_request['request_id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this request?')">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Service Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Service:</th>
                                        <td><?php echo $view_request['service_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Budget:</th>
                                        <td>$<?php echo number_format($view_request['budget'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Price Range:</th>
                                        <td><?php echo $view_request['price_range']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Priority:</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $view_request['priority'] == 'high' ? 'danger' : 
                                                    ($view_request['priority'] == 'medium' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($view_request['priority']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Client Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Name:</th>
                                        <td><?php echo $view_request['user_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><a href="mailto:<?php echo $view_request['user_email']; ?>"><?php echo $view_request['user_email']; ?></a></td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td><?php echo $view_request['user_phone'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address:</th>
                                        <td><?php echo $view_request['address'] ?? 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Request Details</h6>
                                <p><strong>Preferred Date:</strong> <?php echo date('d M Y', strtotime($view_request['preferred_date'])); ?></p>
                                <p><strong>Preferred Time:</strong> <?php echo $view_request['preferred_time']; ?></p>
                                <p><strong>Request Date:</strong> <?php echo date('d M Y H:i', strtotime($view_request['request_date'])); ?></p>
                                <div class="mt-3">
                                    <strong>Details:</strong>
                                    <p class="mt-2"><?php echo nl2br($view_request['request_details']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Status & Notes</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="request_id" value="<?php echo $view_request['request_id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Update Status</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?php echo $view_request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $view_request['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="in_progress" <?php echo $view_request['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $view_request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $view_request['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Admin Notes</label>
                                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add notes about this request..."></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Status
                                    </button>
                                </form>
                                
                                <?php if ($view_request['admin_notes']): ?>
                                <hr>
                                <h6 class="text-primary">Previous Notes</h6>
                                <div class="bg-light p-3 rounded">
                                    <?php echo nl2br($view_request['admin_notes']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-badge bg-primary">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Request Created</h6>
                                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($view_request['request_date'])); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($view_request['status'] != 'pending'): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge bg-info">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Status: <?php echo ucfirst($view_request['status']); ?></h6>
                                    <small class="text-muted">Last updated recently</small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($view_request['status'] == 'completed'): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Completed</h6>
                                    <small class="text-muted"><?php echo $view_request['completion_date'] ? date('d M Y', strtotime($view_request['completion_date'])) : 'Pending'; ?></small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="mailto:<?php echo $view_request['user_email']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i>Send Email
                            </a>
                            <a href="tel:<?php echo $view_request['user_phone']; ?>" class="btn btn-outline-success">
                                <i class="fas fa-phone me-2"></i>Call Client
                            </a>
                            <a href="?print=<?php echo $view_request['request_id']; ?>" class="btn btn-outline-secondary" onclick="window.print(); return false;">
                                <i class="fas fa-print me-2"></i>Print Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Requests List View -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="all">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="service" class="form-select">
                            <option value="0">All Services</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['service_id']; ?>" 
                                        <?php echo $service_filter == $service['service_id'] ? 'selected' : ''; ?>>
                                    <?php echo $service['service_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" placeholder="From" 
                               value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" placeholder="To" 
                               value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Request Date</th>
                                <th>Preferred Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">No service requests found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td>
                                        <strong><?php echo $request['user_name']; ?></strong>
                                        <br><small class="text-muted"><?php echo $request['user_email']; ?></small>
                                    </td>
                                    <td>
                                        <i class="fas <?php echo $request['icon_class']; ?> text-primary me-1"></i>
                                        <?php echo $request['service_name']; ?>
                                    </td>
                                    <td>$<?php echo number_format($request['budget'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'in_progress' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $class = $status_class[$request['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['priority'] == 'high' ? 'danger' : 
                                                ($request['priority'] == 'medium' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($request['priority']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($request['preferred_date'])); ?></td>
                                    <td>
                                        <a href="?view=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-badge {
    position: absolute;
    left: -40px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline-content {
    padding-bottom: 15px;
    border-bottom: 1px dashed #dee2e6;
}

.timeline-item:last-child .timeline-content {
    border-bottom: none;
}

@media print {
    .admin-header, .btn, form, .card-footer {
        display: none !important;
    }
}
</style>

<?php
// Function to send status notification email
function sendStatusNotification($email, $name, $service, $status, $notes) {
    $subject = "Service Request Status Update - JED BINARY TECH";
    
    $status_colors = [
        'confirmed' => '#28a745',
        'in_progress' => '#17a2b8',
        'completed' => '#28a745',
        'cancelled' => '#dc3545'
    ];
    $color = $status_colors[$status] ?? '#007bff';
    
    $message = "
    <html>
    <head>
        <title>Service Request Status Update</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .status { color: $color; font-weight: bold; font-size: 1.2em; }
            .notes { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Service Request Status Update</h2>
            <p>Dear $name,</p>
            <p>Your service request for <strong>$service</strong> has been updated.</p>
            <p>New Status: <span class='status'>" . ucfirst($status) . "</span></p>
            
            <div class='notes'>
                <strong>Admin Notes:</strong>
                <p>$notes</p>
            </div>
            
            <p>You can track your request in your <a href='" . SITE_URL . "/dashboard.php'>dashboard</a>.</p>
            
            <p>Thank you for choosing JED BINARY TECH!</p>
            <br>
            <p>Best Regards,<br>JED BINARY TECH Team</p>
        </div>
    </body>
    </html>
    ";
    
    sendEmail($email, $subject, $message);
}

include '../includes/footer.php'; 
?>