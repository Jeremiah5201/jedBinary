<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Handle all POST and GET actions BEFORE any output
// This is crucial to avoid "headers already sent" errors

// Handle Add/Edit service (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $service_name = mysqli_real_escape_string($conn, trim($_POST['service_name']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $short_description = mysqli_real_escape_string($conn, trim($_POST['short_description']));
    $full_description = mysqli_real_escape_string($conn, trim($_POST['full_description']));
    $price_range = mysqli_real_escape_string($conn, $_POST['price_range']);
    $icon_class = mysqli_real_escape_string($conn, $_POST['icon_class']);
    
    // Handle features
    $features_text = isset($_POST['features']) ? trim($_POST['features']) : '';
    $features_array = array();
    if (!empty($features_text)) {
        $features_array = array_filter(array_map('trim', explode("\n", $features_text)));
    }
    $features_json = json_encode($features_array);
    
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0 && $_FILES['service_image']['size'] > 0) {
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
                features = '$features_json',
                is_featured = $is_featured,
                is_active = $is_active";
        
        if ($image_url) {
            $old_sql = "SELECT image_url FROM services WHERE service_id = $service_id";
            $old_result = mysqli_query($conn, $old_sql);
            if ($old_result && $old_row = mysqli_fetch_assoc($old_result)) {
                if ($old_row['image_url'] && file_exists('../uploads/services/' . $old_row['image_url'])) {
                    unlink('../uploads/services/' . $old_row['image_url']);
                }
            }
            $sql .= ", image_url = '$image_url'";
        }
        
        $sql .= " WHERE service_id = $service_id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "Service updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update service: " . mysqli_error($conn);
        }
    } else {
        // Insert new service
        $sql = "INSERT INTO services (
                service_name, category, short_description, full_description, 
                price_range, icon_class, features, image_url, is_featured, is_active, created_at
                ) VALUES (
                '$service_name', '$category', '$short_description', '$full_description',
                '$price_range', '$icon_class', '$features_json', '$image_url', $is_featured, $is_active, NOW()
                )";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success_message'] = "New service added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add service: " . mysqli_error($conn);
        }
    }
    
    // Redirect after processing
    header("Location: manage-services.php");
    exit();
}

// Handle Delete service (GET)
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    
    $get_sql = "SELECT service_name, image_url FROM services WHERE service_id = $service_id";
    $get_result = mysqli_query($conn, $get_sql);
    if ($service = mysqli_fetch_assoc($get_result)) {
        if ($service['image_url'] && file_exists('../uploads/services/' . $service['image_url'])) {
            unlink('../uploads/services/' . $service['image_url']);
        }
        
        $delete_sql = "DELETE FROM services WHERE service_id = $service_id";
        if (mysqli_query($conn, $delete_sql)) {
            $_SESSION['success_message'] = "Service deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete service";
        }
    }
    
    header("Location: manage-services.php");
    exit();
}

// Handle Toggle status (GET)
if (isset($_GET['toggle'])) {
    $service_id = intval($_GET['toggle']);
    
    $sql = "UPDATE services SET is_active = NOT is_active WHERE service_id = $service_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Service status toggled successfully!";
    }
    
    header("Location: manage-services.php");
    exit();
}

// Handle Toggle featured (GET)
if (isset($_GET['feature'])) {
    $service_id = intval($_GET['feature']);
    
    $sql = "UPDATE services SET is_featured = NOT is_featured WHERE service_id = $service_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Service featured status toggled successfully!";
    }
    
    header("Location: manage-services.php");
    exit();
}

// Now include header (after all redirects)
requireAdmin();
$page_title = 'Manage Services';
include '../includes/header.php';

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
if (isset($_GET['edit']) && $_GET['edit'] !== '0') {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM services WHERE service_id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_sql);
    $edit_service = mysqli_fetch_assoc($edit_result);
    if ($edit_service && isset($edit_service['features'])) {
        $features_decoded = json_decode($edit_service['features'], true);
        if (is_array($features_decoded)) {
            $edit_service['features_text'] = implode("\n", $features_decoded);
        } else {
            $edit_service['features_text'] = '';
        }
    } elseif ($edit_service) {
        $edit_service['features_text'] = '';
    }
}

// Font Awesome icons list
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
                <a href="?add=1" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i>Add New Service
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
    
    <?php if (isset($_GET['add']) || $edit_service): ?>
        <!-- Service Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas <?php echo $edit_service ? 'fa-edit' : 'fa-plus'; ?> me-2 text-primary"></i>
                    <?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="serviceForm">
                    <?php if ($edit_service && isset($edit_service['service_id'])): ?>
                        <input type="hidden" name="service_id" value="<?php echo $edit_service['service_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Service Name *</label>
                            <input type="text" name="service_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($edit_service['service_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Development" <?php echo (isset($edit_service['category']) && $edit_service['category'] == 'Development') ? 'selected' : ''; ?>>Development</option>
                                <option value="Infrastructure" <?php echo (isset($edit_service['category']) && $edit_service['category'] == 'Infrastructure') ? 'selected' : ''; ?>>Infrastructure</option>
                                <option value="Consultancy" <?php echo (isset($edit_service['category']) && $edit_service['category'] == 'Consultancy') ? 'selected' : ''; ?>>Consultancy</option>
                                <option value="IT Support" <?php echo (isset($edit_service['category']) && $edit_service['category'] == 'IT Support') ? 'selected' : ''; ?>>IT Support</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description *</label>
                        <textarea name="short_description" class="form-control" rows="2" required><?php echo htmlspecialchars($edit_service['short_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description *</label>
                        <textarea name="full_description" class="form-control" rows="4" required><?php echo htmlspecialchars($edit_service['full_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price Range *</label>
                            <input type="text" name="price_range" class="form-control" 
                                   placeholder="e.g., $500-$5000" required
                                   value="<?php echo htmlspecialchars($edit_service['price_range'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon Class *</label>
                            <select name="icon_class" class="form-select" required>
                                <option value="">Select Icon</option>
                                <?php foreach ($fa_icons as $icon): ?>
                                    <option value="<?php echo $icon; ?>" 
                                            <?php echo (isset($edit_service['icon_class']) && $edit_service['icon_class'] == $icon) ? 'selected' : ''; ?>>
                                        <i class="fas <?php echo $icon; ?>"></i> <?php echo $icon; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <i class="fas <?php echo htmlspecialchars($edit_service['icon_class'] ?? 'fa-code'); ?> fa-2x preview-icon"></i>
                                <span class="ms-2 text-muted">Preview</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Service Image</label>
                            <input type="file" name="service_image" class="form-control" accept="image/*">
                            <?php if ($edit_service && !empty($edit_service['image_url'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/services/<?php echo htmlspecialchars($edit_service['image_url']); ?>" 
                                         alt="Current" style="max-height: 50px;">
                                    <small class="text-muted d-block">Current image (leave empty to keep)</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features (one per line)</label>
                        <textarea name="features" class="form-control" rows="4" 
                                  placeholder="Responsive Design&#10;SEO Optimized&#10;Fast Loading"><?php 
                            echo htmlspecialchars($edit_service['features_text'] ?? '');
                        ?></textarea>
                        <small class="text-muted">Enter each feature on a new line</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" value="1"
                                       <?php echo (isset($edit_service['is_featured']) && $edit_service['is_featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    <i class="fas fa-star text-warning me-1"></i> Featured Service
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" checked
                                       <?php echo (!isset($edit_service['is_active']) || (isset($edit_service['is_active']) && $edit_service['is_active'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-check-circle text-success me-1"></i> Active
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="manage-services.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="save_service" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
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
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No services found. <a href="?add=1">Click here to add your first service</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="text-center">
                                    <i class="fas <?php echo htmlspecialchars($service['icon_class']); ?> fa-2x text-primary"></i>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($service['short_description'], 0, 50)); ?>...</small>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($service['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($service['price_range']); ?></td>
                                <td>
                                    <?php 
                                    $features = json_decode($service['features'], true);
                                    if ($features && is_array($features) && count($features) > 0): 
                                    ?>
                                        <span class="badge bg-success"><?php echo count($features); ?> features</span>
                                        <button class="btn btn-sm btn-link p-0 ms-2" type="button" 
                                                onclick="alert('<?php echo addslashes(implode("\\n", $features)); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
                                           onclick="return confirm('Are you sure you want to delete "<?php echo addslashes($service['service_name']); ?>"?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Icon preview
const iconSelect = document.querySelector('select[name="icon_class"]');
if (iconSelect) {
    iconSelect.addEventListener('change', function() {
        const selectedIcon = this.value;
        const previewIcon = document.querySelector('.preview-icon');
        if (previewIcon && selectedIcon) {
            previewIcon.className = 'fas ' + selectedIcon + ' fa-2x preview-icon';
        }
    });
}

// Search functionality
const searchInput = document.getElementById('serviceSearch');
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#servicesTable tbody tr');
        
        tableRows.forEach(row => {
            if (row.cells && row.cells.length > 0) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
}

// Form validation
const serviceForm = document.getElementById('serviceForm');
if (serviceForm) {
    serviceForm.addEventListener('submit', function(e) {
        const serviceName = this.querySelector('[name="service_name"]')?.value.trim();
        const category = this.querySelector('[name="category"]')?.value;
        const shortDesc = this.querySelector('[name="short_description"]')?.value.trim();
        const priceRange = this.querySelector('[name="price_range"]')?.value.trim();
        const iconClass = this.querySelector('[name="icon_class"]')?.value;
        
        if (!serviceName) {
            e.preventDefault();
            alert('Please enter a service name');
            return false;
        }
        if (!category) {
            e.preventDefault();
            alert('Please select a category');
            return false;
        }
        if (!shortDesc) {
            e.preventDefault();
            alert('Please enter a short description');
            return false;
        }
        if (!priceRange) {
            e.preventDefault();
            alert('Please enter a price range');
            return false;
        }
        if (!iconClass) {
            e.preventDefault();
            alert('Please select an icon');
            return false;
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>