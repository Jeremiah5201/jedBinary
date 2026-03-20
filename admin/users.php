<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Process all actions BEFORE any output
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if reset columns exist
$reset_columns_exist = false;
$check_columns = "SHOW COLUMNS FROM users LIKE 'reset_token'";
$columns_result = mysqli_query($conn, $check_columns);
if ($columns_result && mysqli_num_rows($columns_result) > 0) {
    $reset_columns_exist = true;
}

// Handle user status toggle (activate/deactivate)
if (isset($_GET['toggle_status']) && $user_id > 0) {
    // Don't allow admin to deactivate themselves
    if ($user_id != $_SESSION['user_id']) {
        $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = $user_id";
        if (mysqli_query($conn, $sql)) {
            // Get user email for notification
            $user_sql = "SELECT email, full_name FROM users WHERE user_id = $user_id";
            $user_result = mysqli_query($conn, $user_sql);
            $user = mysqli_fetch_assoc($user_result);
            
            $new_status = mysqli_query($conn, "SELECT is_active FROM users WHERE user_id = $user_id");
            $status_row = mysqli_fetch_assoc($new_status);
            
            $_SESSION['success_message'] = "User account " . ($status_row['is_active'] ? 'activated' : 'deactivated') . " successfully!";
            
            // Send notification email
            if (!$status_row['is_active']) {
                sendAccountStatusEmail($user['email'], $user['full_name'], 'deactivated');
            } else {
                sendAccountStatusEmail($user['email'], $user['full_name'], 'activated');
            }
        } else {
            $_SESSION['error_message'] = "Failed to update user status";
        }
    } else {
        $_SESSION['error_message'] = "You cannot deactivate your own account";
    }
    header("Location: users.php");
    exit();
}

// Handle user type change (make admin/remove admin)
if (isset($_GET['change_type']) && $user_id > 0) {
    // Don't allow changing own type
    if ($user_id != $_SESSION['user_id']) {
        $new_type = isset($_GET['make_admin']) ? 'admin' : 'client';
        $sql = "UPDATE users SET user_type = '$new_type' WHERE user_id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "User type changed to " . ucfirst($new_type) . " successfully!";
            
            // Get user email for notification
            $user_sql = "SELECT email, full_name FROM users WHERE user_id = $user_id";
            $user_result = mysqli_query($conn, $user_sql);
            $user = mysqli_fetch_assoc($user_result);
            sendUserTypeChangeEmail($user['email'], $user['full_name'], $new_type);
        } else {
            $_SESSION['error_message'] = "Failed to change user type";
        }
    } else {
        $_SESSION['error_message'] = "You cannot change your own user type";
    }
    header("Location: users.php");
    exit();
}

// Handle password reset request (admin initiated)
if (isset($_GET['reset_password']) && $user_id > 0 && $reset_columns_exist) {
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sql = "UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE user_id = $user_id";
    if (mysqli_query($conn, $sql)) {
        // Get user details
        $user_sql = "SELECT email, full_name FROM users WHERE user_id = $user_id";
        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);
        
        // Send password reset email
        sendAdminResetEmail($user['email'], $user['full_name'], $token);
        
        $_SESSION['success_message'] = "Password reset email sent to " . $user['full_name'];
    } else {
        $_SESSION['error_message'] = "Failed to generate password reset";
    }
    header("Location: users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete_user']) && $user_id > 0) {
    // Don't allow deleting own account
    if ($user_id != $_SESSION['user_id']) {
        // Get user details before deletion
        $user_sql = "SELECT email, full_name FROM users WHERE user_id = $user_id";
        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);
        
        // Delete user's related records first (due to foreign keys)
        mysqli_query($conn, "DELETE FROM service_requests WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM passport_assistance WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM contact_messages WHERE user_id = $user_id");
        
        // Check if user_activity_log exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'user_activity_log'");
        if (mysqli_num_rows($check_table) > 0) {
            mysqli_query($conn, "DELETE FROM user_activity_log WHERE user_id = $user_id");
        }
        
        // Delete user
        $sql = "DELETE FROM users WHERE user_id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "User " . $user['full_name'] . " has been deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete user";
        }
    } else {
        $_SESSION['error_message'] = "You cannot delete your own account";
    }
    header("Location: users.php");
    exit();
}

// Handle bulk action
if (isset($_POST['bulk_action']) && isset($_POST['user_ids'])) {
    $user_ids = $_POST['user_ids'];
    $action = $_POST['bulk_action'];
    $count = 0;
    
    foreach ($user_ids as $uid) {
        $uid = intval($uid);
        if ($uid != $_SESSION['user_id']) { // Don't affect current admin
            if ($action == 'activate') {
                mysqli_query($conn, "UPDATE users SET is_active = 1 WHERE user_id = $uid");
                $count++;
            } elseif ($action == 'deactivate') {
                mysqli_query($conn, "UPDATE users SET is_active = 0 WHERE user_id = $uid");
                $count++;
            } elseif ($action == 'make_admin') {
                mysqli_query($conn, "UPDATE users SET user_type = 'admin' WHERE user_id = $uid");
                $count++;
            } elseif ($action == 'make_client') {
                mysqli_query($conn, "UPDATE users SET user_type = 'client' WHERE user_id = $uid");
                $count++;
            }
        }
    }
    
    $_SESSION['success_message'] = "$count users updated successfully!";
    header("Location: users.php");
    exit();
}

// Now include header (after all redirects)
$page_title = 'User Management';
include '../includes/header.php';

// Get filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$user_type = isset($_GET['user_type']) ? mysqli_real_escape_string($conn, $_GET['user_type']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query
$where_conditions = ["1=1"];

if ($search) {
    $where_conditions[] = "(full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

if ($user_type && $user_type != 'all') {
    $where_conditions[] = "user_type = '$user_type'";
}

if ($status && $status != 'all') {
    $where_conditions[] = "is_active = " . ($status == 'active' ? 1 : 0);
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get total records
$count_sql = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users
$sql = "SELECT * FROM users WHERE $where_clause ORDER BY registration_date DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as total_admins,
                SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as total_clients,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
                SUM(CASE WHEN DATE(registration_date) = CURDATE() THEN 1 ELSE 0 END) as new_today
              FROM users";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get password reset requests (users with reset token) - only if columns exist
$reset_requests = [];
if ($reset_columns_exist) {
    $reset_sql = "SELECT u.user_id, u.full_name, u.email, u.reset_expiry 
                  FROM users u 
                  WHERE u.reset_token IS NOT NULL AND u.reset_expiry > NOW()
                  ORDER BY u.reset_expiry ASC";
    $reset_result = mysqli_query($conn, $reset_sql);
    if ($reset_result) {
        $reset_requests = mysqli_fetch_all($reset_result, MYSQLI_ASSOC);
    }
}
?>

<!-- Rest of the HTML remains the same as previous users.php -->
<style>
.user-stats-card {
    transition: transform 0.3s ease;
}
.user-stats-card:hover {
    transform: translateY(-5px);
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
}
.user-row:hover {
    background-color: #f8f9fa;
}
.bulk-actions {
    display: none;
}
.bulk-actions.show {
    display: block;
}
</style>

<!-- Admin Header -->
<div class="admin-header bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-users me-2"></i>User Management</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="me-3">Total Users: <?php echo $stats['total_users']; ?></span>
                <a href="?export=csv" class="btn btn-light btn-sm">
                    <i class="fas fa-download me-1"></i>Export
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4">
            <div class="card bg-primary text-white user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Users</h6>
                            <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                        </div>
                        <i class="fas fa-users fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4">
            <div class="card bg-success text-white user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Active Users</h6>
                            <h3 class="mb-0"><?php echo $stats['active_users']; ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4">
            <div class="card bg-danger text-white user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Inactive Users</h6>
                            <h3 class="mb-0"><?php echo $stats['inactive_users']; ?></h3>
                        </div>
                        <i class="fas fa-ban fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4">
            <div class="card bg-warning text-dark user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 mb-1">Admins</h6>
                            <h3 class="mb-0"><?php echo $stats['total_admins']; ?></h3>
                        </div>
                        <i class="fas fa-user-shield fa-2x text-dark-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4">
            <div class="card bg-info text-white user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Clients</h6>
                            <h3 class="mb-0"><?php echo $stats['total_clients']; ?></h3>
                        </div>
                        <i class="fas fa-user fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4">
            <div class="card bg-secondary text-white user-stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">New Today</h6>
                            <h3 class="mb-0"><?php echo $stats['new_today']; ?></h3>
                        </div>
                        <i class="fas fa-user-plus fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Password Reset Requests Alert (only if columns exist) -->
    <?php if ($reset_columns_exist && !empty($reset_requests)): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4">
        <h5><i class="fas fa-key me-2"></i>Pending Password Reset Requests</h5>
        <p>The following users have requested password resets:</p>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Expires</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reset_requests as $reset): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reset['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($reset['email']); ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($reset['reset_expiry'])); ?></td>
                        <td>
                            <a href="?reset_password=<?php echo $reset['user_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-envelope me-1"></i>Resend
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filters and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="user_type" class="form-select">
                        <option value="all">All Types</option>
                        <option value="admin" <?php echo $user_type == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="client" <?php echo $user_type == 'client' ? 'selected' : ''; ?>>Client</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="all">All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="users.php" class="btn btn-secondary w-100">
                        <i class="fas fa-sync me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Actions Bar -->
    <div class="card shadow-sm mb-4" id="bulkActionsBar" style="display: none;">
        <div class="card-body py-2">
            <form method="POST" action="" id="bulkActionForm">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <strong><span id="selectedCount">0</span> users selected</strong>
                    </div>
                    <div class="col-auto">
                        <select name="bulk_action" class="form-select form-select-sm">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="make_admin">Make Admin</option>
                            <option value="make_client">Make Client</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-link btn-sm" onclick="clearSelection()">Clear Selection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User List</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                        <i class="fas fa-check-double me-1"></i>Select All
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()">
                            </th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No users found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="user-row">
                                <td>
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['user_id']; ?>">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">ID: #<?php echo $user['user_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($user['email']); ?>
                                    <br>
                                    <?php if ($user['phone']): ?>
                                        <i class="fas fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['user_type'] == 'admin' ? 'warning' : 'info'; ?>">
                                        <i class="fas fa-<?php echo $user['user_type'] == 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($user['registration_date'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo function_exists('timeAgo') ? timeAgo($user['registration_date']) : ''; ?></small>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <?php echo date('d M Y', strtotime($user['last_login'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo function_exists('timeAgo') ? timeAgo($user['last_login']) : ''; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($reset_columns_exist): ?>
                                        <a href="?reset_password=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Send Password Reset Email"
                                           onclick="return confirm('Send password reset email to this user?')">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($user['user_type'] != 'admin'): ?>
                                            <a href="?change_type=<?php echo $user['user_id']; ?>&make_admin=1" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Make Admin"
                                               onclick="return confirm('Make this user an admin?')">
                                                <i class="fas fa-user-shield"></i>
                                            </a>
                                        <?php elseif ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="?change_type=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Remove Admin"
                                               onclick="return confirm('Remove admin privileges from this user?')">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?toggle_status=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" 
                                           title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                           onclick="return confirm('<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> this user account?')">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete_user=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               title="Delete User"
                                               onclick="return confirm('Are you sure you want to delete <?php echo addslashes($user['full_name']); ?>? This action cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo $user_type; ?>&status=<?php echo $status; ?>">
                            Previous
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo $user_type; ?>&status=<?php echo $status; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($total_pages > 10): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo $user_type; ?>&status=<?php echo $status; ?>">
                                <?php echo $total_pages; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&user_type=<?php echo $user_type; ?>&status=<?php echo $status; ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bulk selection handling
let selectedUsers = [];

function updateBulkActionsBar() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    const bar = document.getElementById('bulkActionsBar');
    
    if (count > 0) {
        bar.style.display = 'block';
        document.getElementById('selectedCount').textContent = count;
        
        // Update hidden inputs for bulk action
        selectedUsers = [];
        checkboxes.forEach(cb => {
            selectedUsers.push(cb.value);
        });
        
        // Create hidden inputs for selected users
        const form = document.getElementById('bulkActionForm');
        const existingInputs = form.querySelectorAll('input[name="user_ids[]"]');
        existingInputs.forEach(input => input.remove());
        
        selectedUsers.forEach(userId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = userId;
            form.appendChild(input);
        });
    } else {
        bar.style.display = 'none';
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateBulkActionsBar();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
    updateBulkActionsBar();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
    updateBulkActionsBar();
}

// Add event listeners to checkboxes
document.querySelectorAll('.user-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActionsBar);
});

// View user details
function viewUserDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const modalBody = document.getElementById('userDetailsContent');
    
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch user details via AJAX
    fetch(`api/get-user-details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="user-avatar-lg mb-3">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h5>${escapeHtml(user.full_name)}</h5>
                            <p class="text-muted">${escapeHtml(user.user_type)}</p>
                            <span class="badge bg-${user.is_active ? 'success' : 'danger'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div class="col-md-8">
                            <h6>Contact Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th width="120">Email:</th>
                                    <td><a href="mailto:${escapeHtml(user.email)}">${escapeHtml(user.email)}</a></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>${user.phone ? escapeHtml(user.phone) : 'Not provided'}</td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>${user.address ? escapeHtml(user.address) : 'Not provided'}</td>
                                </tr>
                                <tr>
                                    <th>City:</th>
                                    <td>${user.city ? escapeHtml(user.city) : 'Not provided'}</td>
                                </tr>
                                <tr>
                                    <th>State:</th>
                                    <td>${user.state ? escapeHtml(user.state) : 'Not provided'}</td>
                                </tr>
                                <tr>
                                    <th>Pincode:</th>
                                    <td>${user.pincode ? escapeHtml(user.pincode) : 'Not provided'}</td>
                                </tr>
                                <tr>
                                    <th>Registered:</th>
                                    <td>${new Date(user.registration_date).toLocaleString()}</td>
                                </tr>
                                <tr>
                                    <th>Last Login:</th>
                                    <td>${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <h6>Activity Summary</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>${data.stats?.total_requests || 0}</h5>
                                    <small>Service Requests</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>${data.stats?.completed_requests || 0}</h5>
                                    <small>Completed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>${data.stats?.total_spent ? '$' + data.stats.total_spent : '$0'}</h5>
                                    <small>Total Spent</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading user details</div>';
        });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php
// Email functions
function sendAccountStatusEmail($email, $name, $status) {
    $subject = "Account " . ucfirst($status) . " - JED BINARY TECH";
    
    $message = "
    <html>
    <head>
        <title>Account Status Update</title>
    </head>
    <body>
        <h2>Account Status Update</h2>
        <p>Dear $name,</p>
        <p>Your JED BINARY TECH account has been <strong>" . ucfirst($status) . "</strong>.</p>";
    
    if ($status == 'deactivated') {
        $message .= "<p>If you believe this is an error, please contact our support team.</p>";
    } else {
        $message .= "<p>You can now <a href='" . SITE_URL . "/login.php'>login to your account</a>.</p>";
    }
    
    $message .= "
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>";
    
    sendEmail($email, $subject, $message);
}

function sendUserTypeChangeEmail($email, $name, $new_type) {
    $subject = "Account Privileges Updated - JED BINARY TECH";
    
    $message = "
    <html>
    <head>
        <title>Account Privileges Update</title>
    </head>
    <body>
        <h2>Account Privileges Updated</h2>
        <p>Dear $name,</p>
        <p>Your account privileges have been updated to: <strong>" . ucfirst($new_type) . "</strong>.</p>
        <p>If you have any questions, please contact our support team.</p>
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>";
    
    sendEmail($email, $subject, $message);
}

function sendAdminResetEmail($email, $name, $token) {
    $reset_link = SITE_URL . "/reset-password.php?token=" . urlencode($token);
    
    $subject = "Password Reset Request - JED BINARY TECH";
    
    $message = "
    <html>
    <head>
        <title>Password Reset Request</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Dear $name,</p>
        <p>An administrator has initiated a password reset for your account.</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='$reset_link'>$reset_link</a></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you did not request this, please contact support immediately.</p>
        <br>
        <p>Best Regards,<br>JED BINARY TECH Team</p>
    </body>
    </html>";
    
    sendEmail($email, $subject, $message);
}

include '../includes/footer.php';
?>