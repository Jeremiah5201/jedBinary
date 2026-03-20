<?php
// This file is included by admin/index.php
global $conn;

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = '../uploads/profile_images/';
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            if ($_FILES['profile_image']['size'] <= 2 * 1024 * 1024) {
                $filename = 'admin_' . $user_id . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Delete old image if exists
                    $old_sql = "SELECT profile_image FROM users WHERE user_id = $user_id";
                    $old_result = mysqli_query($conn, $old_sql);
                    $old_row = mysqli_fetch_assoc($old_result);
                    if ($old_row['profile_image'] && file_exists($target_dir . $old_row['profile_image'])) {
                        unlink($target_dir . $old_row['profile_image']);
                    }
                    
                    $update_sql = "UPDATE users SET profile_image = '$filename' WHERE user_id = $user_id";
                    if (mysqli_query($conn, $update_sql)) {
                        $success_message = "Profile picture updated successfully!";
                        logActivity('profile_image', 'Admin updated profile picture');
                    } else {
                        $error_message = "Failed to update profile picture.";
                    }
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                $error_message = "File size must be less than 2MB.";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $error_message = "Please select an image to upload.";
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Get user activity log
$activity_sql = "SELECT * FROM user_activity_log 
                 WHERE user_id = $user_id 
                 ORDER BY created_at DESC 
                 LIMIT 10";
$activity_result = mysqli_query($conn, $activity_sql);
$activities = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);

// Get statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id) as total_requests,
                (SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id AND status = 'completed') as completed_requests
              FROM dual";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    text-align: center;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    overflow: hidden;
    border: 4px solid rgba(255,255,255,0.3);
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar .avatar-text {
    font-size: 60px;
    font-weight: bold;
    color: #667eea;
}

.settings-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    overflow: hidden;
}

.settings-card .card-header {
    background: white;
    border-bottom: 1px solid #eef2f7;
    padding: 20px;
    font-weight: 600;
}

.settings-card .card-header i {
    margin-right: 10px;
    color: #667eea;
}

.settings-card .card-body {
    padding: 25px;
}

.image-preview {
    max-width: 200px;
    margin-top: 10px;
}

.image-preview img {
    max-height: 150px;
    border-radius: 10px;
}
</style>

<div class="row">
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="profile-header text-center">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image']) && file_exists('../uploads/profile_images/' . $user['profile_image'])): ?>
                    <img src="../uploads/profile_images/<?php echo $user['profile_image']; ?>" alt="Profile">
                <?php else: ?>
                    <div class="avatar-text">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p class="mb-2"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
            <?php if ($user['phone']): ?>
                <p class="mb-2"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?></p>
            <?php endif; ?>
            <p><i class="fas fa-calendar-alt me-2"></i>Member since <?php echo date('F d, Y', strtotime($user['registration_date'])); ?></p>
        </div>
        
        <!-- Upload Profile Picture -->
        <div class="settings-card">
            <div class="card-header">
                <i class="fas fa-camera"></i> Profile Picture
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Upload New Profile Picture</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview" id="imagePreview"></div>
                        <small class="text-muted">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</small>
                    </div>
                    <button type="submit" name="upload_image" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-2"></i>Upload Picture
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Total Requests</h6>
                                <h3 class="mb-0"><?php echo $stats['total_requests'] ?? 0; ?></h3>
                            </div>
                            <i class="fas fa-clipboard-list fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50">Completed Requests</h6>
                                <h3 class="mb-0"><?php echo $stats['completed_requests'] ?? 0; ?></h3>
                            </div>
                            <i class="fas fa-check-circle fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Activity Log -->
        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Recent Activity</span>
                <a href="?page=profile&view=all" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($activities)): ?>
                        <div class="list-group-item text-center py-4">No activity recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-<?php 
                                            echo $activity['action'] == 'login' ? 'sign-in-alt text-success' : 
                                                ($activity['action'] == 'logout' ? 'sign-out-alt text-secondary' : 
                                                ($activity['action'] == 'profile_update' ? 'user-edit text-info' : 'edit text-primary')); 
                                        ?> me-2"></i>
                                        <strong><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></strong>
                                        <?php if ($activity['details']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($activity['details']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height: 150px;">';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}
</script>