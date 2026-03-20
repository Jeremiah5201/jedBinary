<!-- dashboard.php placeholder -->
 <?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['login_error'] = 'Please login to access dashboard.';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard';
include 'includes/header.php';

// Get user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Get user service requests
$requests = getUserRequests($user_id);

// Get passport assistance requests
$passport_sql = "SELECT * FROM passport_assistance WHERE user_id = $user_id ORDER BY created_at DESC";
$passport_result = mysqli_query($conn, $passport_sql);
$passport_requests = mysqli_fetch_all($passport_result, MYSQLI_ASSOC);
?>

<section class="dashboard-section py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar card">
                    <div class="card-body text-center">
                        <div class="user-avatar mb-3">
                            <img src="<?php echo $user['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" 
                                 alt="Profile" class="rounded-circle" width="100" height="100">
                        </div>
                        <h5><?php echo $user['full_name']; ?></h5>
                        <p class="text-muted"><?php echo $user['email']; ?></p>
                        <hr>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="#overview" data-bs-toggle="tab">
                                <i class="fas fa-tachometer-alt me-2"></i> Overview
                            </a>
                            <a class="nav-link" href="#requests" data-bs-toggle="tab">
                                <i class="fas fa-list me-2"></i> Service Requests
                            </a>
                            <a class="nav-link" href="#passport" data-bs-toggle="tab">
                                <i class="fas fa-passport me-2"></i> Passport Assistance
                            </a>
                            <a class="nav-link" href="#profile" data-bs-toggle="tab">
                                <i class="fas fa-user me-2"></i> Profile Settings
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview">
                        <div class="dashboard-content card">
                            <div class="card-header">
                                <h5 class="mb-0">Dashboard Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="stat-card bg-primary text-white p-3 rounded">
                                            <h6>Total Requests</h6>
                                            <h3><?php echo count($requests); ?></h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="stat-card bg-success text-white p-3 rounded">
                                            <h6>Active Requests</h6>
                                            <h3>
                                                <?php 
                                                $active = array_filter($requests, function($r) {
                                                    return in_array($r['status'], ['pending', 'confirmed', 'in_progress']);
                                                });
                                                echo count($active);
                                                ?>
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="stat-card bg-info text-white p-3 rounded">
                                            <h6>Completed</h6>
                                            <h3>
                                                <?php 
                                                $completed = array_filter($requests, function($r) {
                                                    return $r['status'] == 'completed';
                                                });
                                                echo count($completed);
                                                ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="mt-4 mb-3">Recent Service Requests</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Service</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($requests, 0, 5) as $request): ?>
                                            <tr>
                                                <td><?php echo $request['service_name']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $request['status'] == 'completed' ? 'success' : 
                                                            ($request['status'] == 'cancelled' ? 'danger' : 
                                                            ($request['status'] == 'in_progress' ? 'info' : 'warning')); 
                                                    ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <a href="request-details.php?id=<?php echo $request['request_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Requests Tab -->
                    <div class="tab-pane fade" id="requests">
                        <div class="dashboard-content card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">My Service Requests</h5>
                                <a href="services.php" class="btn btn-primary btn-sm">Request New Service</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($requests)): ?>
                                    <p class="text-center">You haven't made any service requests yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Request ID</th>
                                                    <th>Service</th>
                                                    <th>Budget</th>
                                                    <th>Status</th>
                                                    <th>Request Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td>#<?php echo $request['request_id']; ?></td>
                                                    <td><?php echo $request['service_name']; ?></td>
                                                    <td>$<?php echo $request['budget']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $request['status'] == 'completed' ? 'success' : 
                                                                ($request['status'] == 'cancelled' ? 'danger' : 
                                                                ($request['status'] == 'in_progress' ? 'info' : 'warning')); 
                                                        ?>">
                                                            <?php echo ucfirst($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                                    <td>
                                                        <a href="request-details.php?id=<?php echo $request['request_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">Details</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passport Assistance Tab -->
                    <div class="tab-pane fade" id="passport">
                        <div class="dashboard-content card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Passport Assistance</h5>
                                <a href="passport-application.php" class="btn btn-primary btn-sm">New Application</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($passport_requests)): ?>
                                    <p class="text-center">You haven't applied for passport assistance yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Application #</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Documents Status</th>
                                                    <th>Appointment</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($passport_requests as $passport): ?>
                                                <tr>
                                                    <td><?php echo $passport['application_number'] ?? 'Pending'; ?></td>
                                                    <td><?php echo ucfirst($passport['passport_type']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $passport['status'] == 'completed' ? 'success' : 
                                                                ($passport['status'] == 'rejected' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($passport['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $passport['documents_status'] == 'verified' ? 'success' : 
                                                                ($passport['documents_status'] == 'rejected' ? 'danger' : 'warning'); 
                                                        ?>">
                                                            <?php echo ucfirst($passport['documents_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($passport['appointment_date']) {
                                                            echo date('d M Y', strtotime($passport['appointment_date']));
                                                            echo '<br><small>' . $passport['appointment_time'] . '</small>';
                                                        } else {
                                                            echo 'Not scheduled';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="passport-details.php?id=<?php echo $passport['passport_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">Track</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Settings Tab -->
                    <div class="tab-pane fade" id="profile">
                        <div class="dashboard-content card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Settings</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['profile_success'])): ?>
                                    <div class="alert alert-success">
                                        <?php 
                                        echo $_SESSION['profile_success'];
                                        unset($_SESSION['profile_success']);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="api/update-profile.php" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo $user['full_name']; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?php echo $user['phone']; ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" name="city" 
                                                   value="<?php echo $user['city']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="2"><?php echo $user['address']; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" class="form-control" name="profile_image" accept="image/*">
                                    </div>
                                    
                                    <hr>
                                    <h6>Change Password</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>