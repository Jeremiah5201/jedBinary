<!-- register.php placeholder -->
 <?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$page_title = 'Register';
include 'includes/header.php';
?>

<section class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-card card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/images/logo.png" alt="Logo" height="60">
                            <h3 class="mt-3">Create an Account</h3>
                            <p class="text-muted">Join us to access our services</p>
                        </div>
                        
                        <?php if (isset($_SESSION['register_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['register_error'];
                                unset($_SESSION['register_error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="registerForm" method="POST" action="api/register.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 8 characters with 1 number and 1 special character</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <div class="g-recaptcha" data-sitekey="your-recaptcha-site-key"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = checkPasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
});

function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    const indicator = document.getElementById('password-strength');
    if (!indicator) {
        const div = document.createElement('div');
        div.id = 'password-strength';
        div.className = 'progress mt-2';
        div.innerHTML = '<div class="progress-bar" role="progressbar" style="width: 0%"></div>';
        document.getElementById('password').parentElement.appendChild(div);
    }
    
    const width = (strength / 5) * 100;
    const bar = document.querySelector('#password-strength .progress-bar');
    bar.style.width = width + '%';
    
    if (strength <= 2) {
        bar.className = 'progress-bar bg-danger';
    } else if (strength <= 4) {
        bar.className = 'progress-bar bg-warning';
    } else {
        bar.className = 'progress-bar bg-success';
    }
}

// Confirm password validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>

<?php include 'includes/footer.php'; ?>