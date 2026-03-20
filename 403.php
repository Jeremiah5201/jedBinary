<?php
require_once 'includes/config.php';
$page_title = 'Access Denied - 403 Forbidden';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .error-template {
            padding: 40px 15px;
            text-align: center;
        }
        .error-actions {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .error-actions .btn {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="JED BINARY TECH" height="40">
                <span class="brand-text">JED BINARY TECH</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>/services.php">Services</a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="error-template">
                    <h1 class="display-1 text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 403
                    </h1>
                    <h2 class="mb-4">Access Forbidden</h2>
                    <div class="error-details mb-4">
                        <p class="lead">Sorry, you don't have permission to access this page.</p>
                        <p>This area is restricted to administrators only.</p>
                        <p>If you believe this is an error, please contact the system administrator.</p>
                    </div>
                    <div class="error-actions">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-info btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Footer -->
    <footer class="footer bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> JED BINARY TECH SOLUTIONS AND CONSULTANCY. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>