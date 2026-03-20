<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['login_error'] = 'Please login to access your profile.';
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $state = mysqli_real_escape_string($conn, trim($_POST['state']));
    $pincode = mysqli_real_escape_string($conn, trim($_POST['pincode']));
    
    // Validate phone number
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = "Please enter a valid 10-digit phone number.";
    } else {
        $sql = "UPDATE users SET 
                full_name = '$full_name', 
                phone = '$phone', 
                address = '$address',
                city = '$city',
                state = '$state',
                pincode = '$pincode'
                WHERE user_id = $user_id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $full_name;
            $success_message = "Profile updated successfully!";
            logActivity('profile_update', 'User updated profile');
        } else {
            $error_message = "Failed to update profile: " . mysqli_error($conn);
        }
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = 'uploads/profile_images/';
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            if ($_FILES['profile_image']['size'] <= 2 * 1024 * 1024) {
                $filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $old_sql = "SELECT profile_image FROM users WHERE user_id = $user_id";
                    $old_result = mysqli_query($conn, $old_sql);
                    $old_row = mysqli_fetch_assoc($old_result);
                    if ($old_row['profile_image'] && file_exists($target_dir . $old_row['profile_image'])) {
                        unlink($target_dir . $old_row['profile_image']);
                    }
                    
                    $update_sql = "UPDATE users SET profile_image = '$filename' WHERE user_id = $user_id";
                    if (mysqli_query($conn, $update_sql)) {
                        $success_message = "Profile picture updated successfully!";
                        logActivity('profile_image', 'User updated profile picture');
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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $sql = "SELECT password_hash FROM users WHERE user_id = $user_id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $strength = 0;
                if (preg_match('/[A-Z]/', $new_password)) $strength++;
                if (preg_match('/[a-z]/', $new_password)) $strength++;
                if (preg_match('/[0-9]/', $new_password)) $strength++;
                if (preg_match('/[^a-zA-Z0-9]/', $new_password)) $strength++;
                
                if ($strength >= 3) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE users SET password_hash = '$new_hash' WHERE user_id = $user_id";
                    
                    if (mysqli_query($conn, $update_sql)) {
                        $success_message = "Password changed successfully!";
                        logActivity('password_change', 'User changed password');
                    } else {
                        $error_message = "Failed to change password.";
                    }
                } else {
                    $error_message = "Password must contain uppercase, lowercase, number, and special character.";
                }
            } else {
                $error_message = "Password must be at least 8 characters long.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$page_title = 'My Profile';
include 'includes/header.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
}

.profile-avatar {
    width: 120px;
    height: 120px;
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
    font-size: 48px;
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

.password-strength {
    height: 5px;
    margin-top: 5px;
    border-radius: 3px;
    background: #e0e0e0;
}

.password-strength-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="profile-header text-center">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_image']) && file_exists('uploads/profile_images/' . $user['profile_image'])): ?>
                        <img src="uploads/profile_images/<?php echo $user['profile_image']; ?>" alt="Profile">
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
            
            <!-- Profile Information -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i> Profile Information
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       pattern="[0-9]{10}" maxlength="10">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['pincode']); ?>" 
                                       pattern="[0-9]{6}" maxlength="6">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['city']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['state']); ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="fas fa-key"></i> Change Password
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" id="newPassword" required>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" style="width: 0%;"></div>
                            </div>
                            <small class="text-muted">Minimum 8 characters with at least one uppercase, one lowercase, one number, and one special character</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-exchange-alt me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength meter
const newPassword = document.getElementById('newPassword');
const strengthBar = document.querySelector('.password-strength-bar');

if (newPassword) {
    newPassword.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        const percentage = (strength / 5) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (strength <= 2) {
            strengthBar.style.backgroundColor = '#dc3545';
        } else if (strength <= 4) {
            strengthBar.style.backgroundColor = '#ffc107';
        } else {
            strengthBar.style.backgroundColor = '#28a745';
        }
    });
}

// Preview image before upload
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

// Confirm before password change
const passwordForm = document.getElementById('passwordForm');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        const newPass = document.querySelector('[name="new_password"]').value;
        const confirmPass = document.querySelector('[name="confirm_password"]').value;
        
        if (newPass !== confirmPass) {
            e.preventDefault();
            alert('New passwords do not match!');
        } else if (newPass.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
        }
    });
}

// Format phone number input
const phoneInput = document.querySelector('[name="phone"]');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
}

// Format pincode input
const pincodeInput = document.querySelector('[name="pincode"]');
if (pincodeInput) {
    pincodeInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
}
</script>

<?php include 'includes/footer.php'; ?>