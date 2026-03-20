<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

$page_title = 'Manage Services';
include '../includes/header.php';

// Handle Add/Edit service
if (isset($_POST['save_service'])) {
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $short_description = mysqli_real_escape_string($conn, $_POST['short_description']);
    $full_description = mysqli_real_escape_string($conn, $_POST['full_description']);
    $price_range = mysqli_real_escape_string($conn, $_POST['price_range']);
    $icon_class = mysqli_real_escape_string($conn, $_POST['icon_class']);
    $features = isset($_POST['features']) ? json_encode(array_filter($_POST['features'])) : '[]';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $target_dir = '../uploads/services/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES['service_image']['tmp_name'], $target_file)) {
                $image_url = $filename;
            }
        }
    }
    
    if ($service_id > 0) {
        // Update existing service
        $sql = "UPDATE services SET 
                service_name = '$service_name',
                category = '$category',
                short_description = '$short_description',
                full_description = '$full_description',
                price_range = '$price_range',
                icon_class = '$icon_class',
                features = '$features',
                is_featured = $is_featured,
                is_active = $is_active";
        
        if ($image_url) {
            // Get old image to delete
            $old_sql = "SELECT image_url FROM services WHERE service_id = $service_id";
            $old_result = mysqli_query($conn, $old_sql);
            $old_row = mysqli_fetch_assoc($old_result);
            if ($old_row['image_url'] && file_exists('../uploads/services/' . $old_row['image_url'])) {
                unlink('../uploads/services/' . $old_row['image_url']);
            }
            
            $sql .= ", image_url = '$image_url'";
        }
        
        $sql .= " WHERE service_id = $service_id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "Service updated successfully!";
            logActivity('update_service', "Updated service: $service_name");
        } else {
            $_SESSION['error_message'] = "Failed to update service: " . mysqli_error($conn);
        }
    } else {
        // Insert new service
        $sql = "INSERT INTO services (
                service_name, category, short_description, full_description, 
                price_range, icon_class, features, image_url, is_featured, is_active
                ) VALUES (
                '$service_name', '$category', '$short_description', '$full_description',
                '$price_range', '$icon_class', '$features', '$image_url', $is_featured, $is_active
                )";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "New service added successfully!";
            logActivity('add_service', "Added new service: $service_name");
        } else {
            $_SESSION['error_message'] = "Failed to add service: " . mysqli_error($conn);
        }
    }
    
    header("Location: manage-services.php");
    exit();
}

// Handle Delete service
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    
    // Get service details for logging
    $get_sql = "SELECT service_name, image_url FROM services WHERE service_id = $service_id";
    $get_result = mysqli_query($conn, $get_sql);
    $service = mysqli_fetch_assoc($get_result);
    
    // Delete service image if exists
    if ($service['image_url'] && file_exists('../uploads/services/' . $service['image_url'])) {
        unlink('../uploads/services/' . $service['image_url']);
    }
    
    $delete_sql = "DELETE FROM services WHERE service_id = $service_id";
    if (mysqli_query($conn, $delete_sql)) {
        $_SESSION['success_message'] = "Service deleted successfully!";
        logActivity('delete_service', "Deleted service: " . $service['service_name']);
    } else {
        $_SESSION['error_message'] = "Failed to delete service";
    }
    
    header("Location: manage-services.php");
    exit();
}

// Handle Toggle status
if (isset($_GET['toggle'])) {
    $service_id = intval($_GET['toggle']);
    
    $sql = "UPDATE services SET is_active = NOT is_active WHERE service_id = $service_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Service status toggled successfully!";
    }
    
    header("Location: manage-services.php");
    exit();
}

// Handle Toggle featured
if (isset($_GET['feature'])) {
    $service_id = intval($_GET['feature']);
    
    $sql = "UPDATE services SET is_featured = NOT is_featured WHERE service_id = $service_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Service featured status toggled successfully!";
    }
    
    header("Location: manage-services.php");
    exit();
}

// Get all services
$sql = "SELECT * FROM services ORDER BY 
        CASE 
            WHEN is_featured = 1 THEN 0 
            ELSE 1 
        END, 
        service_name ASC";
$result = mysqli_query($conn, $sql);
$services = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get service for editing
$edit_service = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM services WHERE service_id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_sql);
    $edit_service = mysqli_fetch_assoc($edit_result);
    if ($edit_service['features']) {
        $edit_service['features'] = json_decode($edit_service['features'], true);
    }
}

// Font Awesome icons list for selection
$fa_icons = [
    'fa-code', 'fa-server', 'fa-globe', 'fa-passport', 'fa-download',
    'fa-windows', 'fa-mobile-alt', 'fa-cogs', 'fa-laptop-code', 'fa-cloud',
    'fa-shield-alt', 'fa-database', 'fa-chart-line', 'fa-paint-brush',
    'fa-camera', 'fa-video', 'fa-headset', 'fa-rocket', 'fa-cog',
    'fa-users', 'fa-user-tie', 'fa-briefcase', 'fa-chart-bar', 'fa-wrench'
];
?>

<!-- Admin Header -->
<div class="admin-header bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-cogs me-2"></i>Manage Services</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-light" onclick="showAddServiceForm()">
                    <i class="fas fa-plus me-2"></i>Add New Service
                </button>
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
    
    <!-- Service Form Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas <?php echo $edit_service ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
                        <?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" id="serviceForm">
                    <div class="modal-body">
                        <?php if ($edit_service): ?>
                            <input type="hidden" name="service_id" value="<?php echo $edit_service['service_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Service Name *</label>
                                <input type="text" name="service_name" class="form-control" required
                                       value="<?php echo $edit_service['service_name'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Development" <?php echo ($edit_service['category'] ?? '') == 'Development' ? 'selected' : ''; ?>>Development</option>
                                    <option value="Infrastructure" <?php echo ($edit_service['category'] ?? '') == 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                                    <option value="Consultancy" <?php echo ($edit_service['category'] ?? '') == 'Consultancy' ? 'selected' : ''; ?>>Consultancy</option>
                                    <option value="IT Support" <?php echo ($edit_service['category'] ?? '') == 'IT Support' ? 'selected' : ''; ?>>IT Support</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Short Description *</label>
                            <textarea name="short_description" class="form-control" rows="2" required><?php echo $edit_service['short_description'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Description *</label>
                            <textarea name="full_description" class="form-control" rows="4" required><?php echo $edit_service['full_description'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price Range *</label>
                                <input type="text" name="price_range" class="form-control" 
                                       placeholder="e.g., $500-$5000" required
                                       value="<?php echo $edit_service['price_range'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Icon Class *</label>
                                <select name="icon_class" class="form-select icon-select" required>
                                    <option value="">Select Icon</option>
                                    <?php foreach ($fa_icons as $icon): ?>
                                        <option value="<?php echo $icon; ?>" 
                                                data-icon="<?php echo $icon; ?>"
                                                <?php echo ($edit_service['icon_class'] ?? '') == $icon ? 'selected' : ''; ?>>
                                            <?php echo $icon; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 text-center">
                                    <i class="fas <?php echo $edit_service['icon_class'] ?? 'fa-code'; ?> fa-2x preview-icon"></i>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Service Image</label>
                                <input type="file" name="service_image" class="form-control" accept="image/*">
                                <?php if ($edit_service && $edit_service['image_url']): ?>
                                    <div class="mt-2">
                                        <img src="../uploads/services/<?php echo $edit_service['image_url']; ?>" 
                                             alt="Current" style="max-height: 50px;">
                                        <small class="text-muted">Current image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Features (one per line)</label>
                            <textarea name="features[]" class="form-control" rows="4" 
                                      placeholder="Enter each feature on a new line"><?php 
                                if ($edit_service && isset($edit_service['features'])) {
                                    echo implode("\n", $edit_service['features']);
                                }
                            ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured"
                                           <?php echo ($edit_service['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">
                                        <i class="fas fa-star text-warning me-1"></i> Featured Service
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked
                                           <?php echo !isset($edit_service['is_active']) || ($edit_service['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-check-circle text-success me-1"></i> Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_service" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Services List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">All Services</h5>
                </div>
                <div class="col-md-6">
                    <input type="text" id="serviceSearch" class="form-control" placeholder="Search services...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="servicesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Icon</th>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Price Range</th>
                            <th>Features</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="text-center">
                                <i class="fas <?php echo $service['icon_class']; ?> fa-2x text-primary"></i>
                            </td>
                            <td>
                                <strong><?php echo $service['service_name']; ?></strong>
                                <br><small class="text-muted"><?php echo substr($service['short_description'], 0, 50); ?>...</small>
                            </td>
                            <td><span class="badge bg-info"><?php echo $service['category']; ?></span></td>
                            <td><?php echo $service['price_range']; ?></td>
                            <td>
                                <?php 
                                $features = json_decode($service['features'], true);
                                if ($features && count($features) > 0): 
                                ?>
                                    <span class="badge bg-success"><?php echo count($features); ?> features</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No features</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($service['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($service['is_featured']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                    <i class="far fa-star text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="?edit=<?php echo $service['service_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle=<?php echo $service['service_id']; ?>" 
                                       class="btn btn-sm btn-outline-<?php echo $service['is_active'] ? 'warning' : 'success'; ?>" 
                                       title="<?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-<?php echo $service['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </a>
                                    <a href="?feature=<?php echo $service['service_id']; ?>" 
                                       class="btn btn-sm btn-outline-<?php echo $service['is_featured'] ? 'warning' : 'secondary'; ?>" 
                                       title="<?php echo $service['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <a href="?delete=<?php echo $service['service_id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this service?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Show modal for add/edit
<?php if ($edit_service): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('serviceModal'));
        modal.show();
    });
<?php endif; ?>

function showAddServiceForm() {
    window.location.href = 'manage-services.php?edit=0';
}

// Icon preview
document.querySelector('.icon-select')?.addEventListener('change', function() {
    const selectedIcon = this.value;
    document.querySelector('.preview-icon').className = 'fas ' + selectedIcon + ' fa-2x preview-icon';
});

// Search functionality
document.getElementById('serviceSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#servicesTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Form validation
document.getElementById('serviceForm')?.addEventListener('submit', function(e) {
    const serviceName = this.querySelector('[name="service_name"]').value.trim();
    const category = this.querySelector('[name="category"]').value;
    const shortDesc = this.querySelector('[name="short_description"]').value.trim();
    const priceRange = this.querySelector('[name="price_range"]').value.trim();
    const iconClass = this.querySelector('[name="icon_class"]').value;
    
    if (!serviceName || !category || !shortDesc || !priceRange || !iconClass) {
        e.preventDefault();
        alert('Please fill in all required fields');
    }
});

// Preview image before upload
document.querySelector('[name="service_image"]')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            this.value = '';
            return;
        }
        
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            this.value = '';
            return;
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>