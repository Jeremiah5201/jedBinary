<!-- passport-assistance.php placeholder -->
 <?php
require_once 'includes/config.php';
$page_title = 'Passport Registration Assistance';
include 'includes/header.php';

// Check if user is logged in for application form
$isLoggedIn = isLoggedIn();
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Get user's existing applications if logged in
$user_applications = [];
if ($isLoggedIn) {
    $sql = "SELECT * FROM passport_assistance WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    $user_applications = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4">Passport Registration Assistance</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                        <li class="breadcrumb-item"><a href="services.php" class="text-white">Services</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Passport Assistance</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-md-end">
                <p class="lead mb-0">End-to-end passport application support</p>
            </div>
        </div>
    </div>
</section>

<!-- Service Overview -->
<section class="service-overview py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <img src="assets/images/services/passport-service.jpg" alt="Passport Assistance" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <h2 class="section-title"> hassle-free Passport Assistance</h2>
                <p class="lead">We make passport application simple, fast, and stress-free</p>
                <p>Navigating the passport application process can be complex and time-consuming. Our expert team provides end-to-end assistance, ensuring your application is completed correctly and submitted on time. From document preparation to appointment scheduling and tracking, we handle everything so you can focus on your travel plans.</p>
                
                <div class="row mt-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">100% Success Rate</h6>
                                <small class="text-muted">Guaranteed approval</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-clock text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Fast Processing</h6>
                                <small class="text-muted">Expedited service available</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-alt text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Document Verification</h6>
                                <small class="text-muted">Expert document check</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-calendar-check text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Appointment Booking</h6>
                                <small class="text-muted">Priority scheduling</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Packages -->
<section class="packages-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Assistance Packages</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Choose the plan that suits your needs</p>
        </div>
        
        <div class="row">
            <!-- Basic Package -->
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                <div class="package-card card h-100">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h4 class="mb-0">Basic Assistance</h4>
                        <p class="lead mb-0">₹1,499</p>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Document checklist</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Form filling assistance</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Document verification</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Online application submission</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Email support</li>
                            <li class="mb-3 text-muted"><i class="fas fa-times text-danger me-2"></i> Appointment booking</li>
                            <li class="mb-3 text-muted"><i class="fas fa-times text-danger me-2"></i> Tatkal application</li>
                            <li class="mb-3 text-muted"><i class="fas fa-times text-danger me-2"></i> Document collection</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center border-0 pb-4">
                        <?php if ($isLoggedIn): ?>
                        <button class="btn btn-outline-primary" onclick="selectPackage('basic')">Select Package</button>
                        <?php else: ?>
                        <a href="login.php?redirect=passport-assistance.php" class="btn btn-outline-primary">Login to Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Standard Package (Most Popular) -->
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="package-card card h-100 popular">
                    <div class="card-header bg-success text-white text-center py-4 position-relative">
                        <span class="popular-badge">Most Popular</span>
                        <h4 class="mb-0">Standard Assistance</h4>
                        <p class="lead mb-0">₹2,999</p>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Everything in Basic</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Appointment booking</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Document photocopying</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Application tracking</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Phone support</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Police verification follow-up</li>
                            <li class="mb-3 text-muted"><i class="fas fa-times text-danger me-2"></i> Tatkal application</li>
                            <li class="mb-3 text-muted"><i class="fas fa-times text-danger me-2"></i> Document collection</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center border-0 pb-4">
                        <?php if ($isLoggedIn): ?>
                        <button class="btn btn-success" onclick="selectPackage('standard')">Select Package</button>
                        <?php else: ?>
                        <a href="login.php?redirect=passport-assistance.php" class="btn btn-success">Login to Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Premium Package -->
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="package-card card h-100">
                    <div class="card-header bg-warning text-dark text-center py-4">
                        <h4 class="mb-0">Premium Assistance</h4>
                        <p class="lead mb-0">₹4,999</p>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Everything in Standard</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Tatkal application support</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Document collection from home</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Priority appointment</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Passport delivery tracking</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> 24/7 dedicated support</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Emergency assistance</li>
                            <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Money-back guarantee</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center border-0 pb-4">
                        <?php if ($isLoggedIn): ?>
                        <button class="btn btn-warning" onclick="selectPackage('premium')">Select Package</button>
                        <?php else: ?>
                        <a href="login.php?redirect=passport-assistance.php" class="btn btn-warning">Login to Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Application Form (Visible only when logged in) -->
<?php if ($isLoggedIn): ?>
<section class="application-section py-5" id="applicationForm">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Passport Assistance Application Form</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['application_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['application_success'];
                                unset($_SESSION['application_success']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['application_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['application_error'];
                                unset($_SESSION['application_error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="passportForm" method="POST" action="api/submit-passport-application.php" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="package" id="selectedPackage" value="standard">
                            
                            <!-- Personal Information -->
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name (as on documents) *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="place_of_birth" class="form-label">Place of Birth *</label>
                                    <input type="text" class="form-control" id="place_of_birth" name="place_of_birth" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender *</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <h5 class="mb-3 mt-4">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mobile" class="form-label">Mobile Number *</label>
                                    <input type="tel" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['user_email']; ?>" readonly>
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <h5 class="mb-3 mt-4">Address Information</h5>
                            <div class="mb-3">
                                <label for="address_line1" class="form-label">Address Line 1 *</label>
                                <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address_line2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" id="address_line2" name="address_line2">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State *</label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="">Select State</option>
                                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                                        <option value="Karnataka">Karnataka</option>
                                        <option value="Kerala">Kerala</option>
                                        <option value="Tamil Nadu">Tamil Nadu</option>
                                        <option value="Telangana">Telangana</option>
                                        <option value="Maharashtra">Maharashtra</option>
                                        <option value="Delhi">Delhi</option>
                                        <!-- Add more states as needed -->
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="pincode" class="form-label">Pincode *</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode" pattern="[0-9]{6}" required>
                                </div>
                            </div>
                            
                            <!-- Passport Details -->
                            <h5 class="mb-3 mt-4">Passport Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="passport_type" class="form-label">Application Type *</label>
                                    <select class="form-select" id="passport_type" name="passport_type" required onchange="toggleCurrentPassport()">
                                        <option value="new">New Passport</option>
                                        <option value="renewal">Passport Renewal</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3" id="current_passport_div" style="display: none;">
                                    <label for="current_passport_number" class="form-label">Current Passport Number</label>
                                    <input type="text" class="form-control" id="current_passport_number" name="current_passport_number">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="application_type" class="form-label">Application Mode *</label>
                                    <select class="form-select" id="application_type" name="application_type" required>
                                        <option value="normal">Normal</option>
                                        <option value="tatkal">Tatkal</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="preferred_appointment_date" class="form-label">Preferred Appointment Date</label>
                                    <input type="date" class="form-control" id="preferred_appointment_date" name="preferred_appointment_date" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                                </div>
                            </div>
                            
                            <!-- Document Upload -->
                            <h5 class="mb-3 mt-4">Document Upload</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="proof_of_address" class="form-label">Proof of Address *</label>
                                    <input type="file" class="form-control" id="proof_of_address" name="proof_of_address" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Allowed: PDF, JPG, PNG (Max 2MB)</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="proof_of_identity" class="form-label">Proof of Identity *</label>
                                    <input type="file" class="form-control" id="proof_of_identity" name="proof_of_identity" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Allowed: PDF, JPG, PNG (Max 2MB)</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth_proof" class="form-label">Date of Birth Proof *</label>
                                    <input type="file" class="form-control" id="date_of_birth_proof" name="date_of_birth_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Birth Certificate / 10th Marksheet</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="passport_photo" class="form-label">Passport Size Photo *</label>
                                    <input type="file" class="form-control" id="passport_photo" name="passport_photo" accept=".jpg,.jpeg,.png" required>
                                    <small class="text-muted">Recent color photo (white background)</small>
                                </div>
                            </div>
                            
                            <!-- Additional Information -->
                            <h5 class="mb-3 mt-4">Additional Information</h5>
                            <div class="mb-3">
                                <label for="special_requirements" class="form-label">Special Requirements (if any)</label>
                                <textarea class="form-control" id="special_requirements" name="special_requirements" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="how_heard" class="form-label">How did you hear about us?</label>
                                <select class="form-select" id="how_heard" name="how_heard">
                                    <option value="">Select</option>
                                    <option value="google">Google Search</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="friend">Friend/Family</option>
                                    <option value="advertisement">Advertisement</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I confirm that the information provided is true and correct. I agree to the 
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>.
                                </label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                                <label class="form-check-label" for="consent">
                                    I consent to JED BINARY TECH processing my personal information for passport assistance.
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Track Existing Applications -->
<?php if ($isLoggedIn && !empty($user_applications)): ?>
<section class="tracking-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Your Applications</h2>
        <div class="row">
            <?php foreach ($user_applications as $application): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-<?php 
                        echo $application['status'] == 'completed' ? 'success' : 
                            ($application['status'] == 'rejected' ? 'danger' : 
                            ($application['status'] == 'in_progress' ? 'info' : 'warning')); 
                    ?> text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Application #<?php echo $application['application_number'] ?? 'Pending'; ?></span>
                            <span class="badge bg-light text-dark"><?php echo ucfirst($application['passport_type']); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Name:</strong> <?php echo $application['full_name']; ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $application['status'] == 'completed' ? 'success' : 
                                            ($application['status'] == 'rejected' ? 'danger' : 
                                            ($application['status'] == 'in_progress' ? 'info' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-6">
                                <p><strong>Documents:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $application['documents_status'] == 'verified' ? 'success' : 
                                            ($application['documents_status'] == 'rejected' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($application['documents_status']); ?>
                                    </span>
                                </p>
                                <?php if ($application['appointment_date']): ?>
                                <p><strong>Appointment:</strong> <?php echo date('d M Y', strtotime($application['appointment_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="progress mt-3" style="height: 30px;">
                            <?php
                            $progress = 0;
                            switch($application['status']) {
                                case 'application_initiated': $progress = 20; break;
                                case 'documents_verified': $progress = 40; break;
                                case 'appointment_scheduled': $progress = 60; break;
                                case 'in_progress': $progress = 80; break;
                                case 'completed': $progress = 100; break;
                                default: $progress = 10;
                            }
                            ?>
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: <?php echo $progress; ?>%;"
                                 aria-valuenow="<?php echo $progress; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $progress; ?>% Complete
                            </div>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewApplicationDetails(<?php echo $application['passport_id']; ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Process Timeline -->
<section class="process-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">How It Works</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Simple 5-step process for hassle-free passport application</p>
        </div>
        
        <div class="row">
            <div class="col-lg-2 col-md-4 mb-4" data-aos="fade-up">
                <div class="process-step text-center">
                    <div class="step-number">1</div>
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <h5>Submit Application</h5>
                    <p class="small">Fill our online form with your details</p>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="process-step text-center">
                    <div class="step-number">2</div>
                    <i class="fas fa-check-double fa-3x text-primary mb-3"></i>
                    <h5>Document Verification</h5>
                    <p class="small">We verify all your documents</p>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="process-step text-center">
                    <div class="step-number">3</div>
                    <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                    <h5>Appointment Booking</h5>
                    <p class="small">We schedule your passport office visit</p>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="process-step text-center">
                    <div class="step-number">4</div>
                    <i class="fas fa-edit fa-3x text-primary mb-3"></i>
                    <h5>Application Submission</h5>
                    <p class="small">Complete submission at passport office</p>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4" data-aos="fade-up" data-aos-delay="400">
                <div class="process-step text-center">
                    <div class="step-number">5</div>
                    <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                    <h5>Passport Delivery</h5>
                    <p class="small">Get your passport delivered home</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Frequently Asked Questions</h2>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                What documents are required for passport application?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>Proof of Date of Birth (Birth Certificate, 10th Marksheet, or Passport of parents)</li>
                                    <li>Proof of Address (Aadhaar Card, Voter ID, Utility Bills, or Bank Statement)</li>
                                    <li>Proof of Identity (Aadhaar Card, Voter ID, Driving License, or PAN Card)</li>
                                    <li>Recent passport size photographs (white background)</li>
                                    <li>For renewal: Old passport and self-attested copies of first and last pages</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How long does the passport application process take?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Normal applications typically take 30-45 days from the date of application. Tatkal applications are processed within 7-14 days. The timeline may vary based on police verification and document verification at the regional passport office.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What is Tatkal passport and how is it different?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Tatkal is an expedited passport service for urgent travel needs. It processes applications faster (7-14 days) but requires additional fees and stricter document verification. Tatkal applicants must provide proof of urgent travel and undergo immediate police verification.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Can I apply for passport renewal before expiry?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, you can apply for passport renewal up to 1 year before the expiry date. It's recommended to renew at least 6 months before expiry to avoid last-minute issues, especially if you have international travel planned.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                What is police verification and how does it work?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Police verification is a mandatory process where local police verify your address and identity. For new passports, it's done after application submission. For renewals with same address, it may be done post-issuance. We help coordinate and track this process.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Required Documents Section -->
<section class="documents-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Required Documents Checklist</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Download and prepare these documents before applying</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-right">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-id-card text-primary me-2"></i> Identity Proof</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Aadhaar Card</li>
                            <li><i class="fas fa-check text-success me-2"></i> Voter ID</li>
                            <li><i class="fas fa-check text-success me-2"></i> Driving License</li>
                            <li><i class="fas fa-check text-success me-2"></i> PAN Card</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-up">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-map-marker-alt text-primary me-2"></i> Address Proof</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Aadhaar Card</li>
                            <li><i class="fas fa-check text-success me-2"></i> Utility Bills (Electricity/Water)</li>
                            <li><i class="fas fa-check text-success me-2"></i> Bank Statement</li>
                            <li><i class="fas fa-check text-success me-2"></i> Rent Agreement</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-left">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-alt text-primary me-2"></i> Date of Birth Proof</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Birth Certificate</li>
                            <li><i class="fas fa-check text-success me-2"></i> 10th Marksheet</li>
                            <li><i class="fas fa-check text-success me-2"></i> School Leaving Certificate</li>
                            <li><i class="fas fa-check text-success me-2"></i> Passport (for renewal)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="assets/downloads/passport-checklist.pdf" class="btn btn-primary" download>
                <i class="fas fa-download me-2"></i>Download Complete Checklist
            </a>
        </div>
    </div>
</section>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions - Passport Assistance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Service Agreement</h6>
                <p>By using our passport assistance service, you agree to provide accurate and complete information. JED BINARY TECH acts as an intermediary to facilitate your passport application process.</p>
                
                <h6>2. Document Verification</h6>
                <p>While we verify documents for completeness, final approval rests with the passport authorities. We are not responsible for rejections due to government policies or incomplete information provided by you.</p>
                
                <h6>3. Fees and Payment</h6>
                <p>Our service fees are separate from government fees. All payments are non-refundable once the application process has started.</p>
                
                <h6>4. Timeline</h6>
                <p>Processing times are estimates and may vary based on government processing and police verification. We are not liable for delays caused by external factors.</p>
                
                <h6>5. Privacy</h6>
                <p>Your personal information will be used solely for passport application purposes and will not be shared with third parties except as required for the application process.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Package Cards */
.package-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    position: relative;
}

.package-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.package-card.popular {
    border: 2px solid var(--success-color);
    transform: scale(1.05);
    z-index: 1;
}

.package-card.popular:hover {
    transform: scale(1.05) translateY(-10px);
}

.popular-badge {
    position: absolute;
    top: 20px;
    right: -30px;
    background: var(--success-color);
    color: white;
    padding: 5px 40px;
    transform: rotate(45deg);
    font-size: 0.8rem;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.package-card .card-header {
    border-bottom: none;
    padding: 2rem 1rem;
}

.package-card .card-header h4 {
    font-weight: 700;
}

.package-card .card-header .lead {
    font-size: 2rem;
    font-weight: 700;
}

.package-card .card-body {
    padding: 2rem 1.5rem;
}

.package-card .card-body li {
    padding: 0.5rem 0;
    border-bottom: 1px dashed #eee;
}

.package-card .card-body li:last-child {
    border-bottom: none;
}

/* Process Steps */
.process-step {
    position: relative;
    padding: 20px;
}

.step-number {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 30px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.process-step i {
    margin-top: 10px;
}

/* Form Styles */
#applicationForm .card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

#applicationForm .card-header {
    padding: 1.5rem;
}

#applicationForm .form-control,
#applicationForm .form-select {
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid #ddd;
}

#applicationForm .form-control:focus,
#applicationForm .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

#applicationForm h5 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
}

/* Tracking Cards */
.tracking-section .card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.tracking-section .card-header {
    padding: 1rem;
}

.progress {
    border-radius: 15px;
    background-color: #f0f0f0;
}

.progress-bar {
    border-radius: 15px;
    transition: width 1s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .package-card.popular {
        transform: scale(1);
    }
    
    .package-card.popular:hover {
        transform: translateY(-10px);
    }
    
    .popular-badge {
        top: 15px;
        right: -35px;
        padding: 3px 35px;
        font-size: 0.7rem;
    }
}

@media (max-width: 576px) {
    .package-card .card-header .lead {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Toggle current passport field based on application type
function toggleCurrentPassport() {
    const passportType = document.getElementById('passport_type').value;
    const currentPassportDiv = document.getElementById('current_passport_div');
    const currentPassportInput = document.getElementById('current_passport_number');
    
    if (passportType === 'renewal') {
        currentPassportDiv.style.display = 'block';
        currentPassportInput.required = true;
    } else {
        currentPassportDiv.style.display = 'none';
        currentPassportInput.required = false;
        currentPassportInput.value = '';
    }
}

// Select package
function selectPackage(package) {
    document.getElementById('selectedPackage').value = package;
    document.getElementById('applicationForm').scrollIntoView({ behavior: 'smooth' });
    
    // Highlight selected package
    document.querySelectorAll('.package-card').forEach(card => {
        card.classList.remove('border-primary', 'border-3');
    });
    
    if (package === 'basic') {
        event.target.closest('.col-lg-4').querySelector('.package-card').classList.add('border-primary', 'border-3');
    } else if (package === 'standard') {
        event.target.closest('.col-lg-4').querySelector('.package-card').classList.add('border-primary', 'border-3');
    } else if (package === 'premium') {
        event.target.closest('.col-lg-4').querySelector('.package-card').classList.add('border-primary', 'border-3');
    }
}

// View application details
function viewApplicationDetails(applicationId) {
    // You can implement modal or redirect to details page
    window.location.href = `passport-details.php?id=${applicationId}`;
}

// File upload validation
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            // Check file size (2MB limit)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size should not exceed 2MB');
                this.value = '';
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, and PDF files are allowed');
                this.value = '';
                return;
            }
        }
    });
});

// Form validation
document.getElementById('passportForm')?.addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = this.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Mobile number validation
    const mobile = document.getElementById('mobile');
    if (mobile.value && !/^[0-9]{10}$/.test(mobile.value)) {
        mobile.classList.add('is-invalid');
        alert('Please enter a valid 10-digit mobile number');
        isValid = false;
    }
    
    // Pincode validation
    const pincode = document.getElementById('pincode');
    if (pincode.value && !/^[0-9]{6}$/.test(pincode.value)) {
        pincode.classList.add('is-invalid');
        alert('Please enter a valid 6-digit pincode');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill all required fields correctly');
    }
});

// Preview uploaded images
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewId).style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for appointment (7 days from now)
    const appointmentDate = document.getElementById('preferred_appointment_date');
    if (appointmentDate) {
        const minDate = new Date();
        minDate.setDate(minDate.getDate() + 7);
        appointmentDate.min = minDate.toISOString().split('T')[0];
    }
    
    // Check for package parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const package = urlParams.get('package');
    if (package && ['basic', 'standard', 'premium'].includes(package)) {
        selectPackage(package);
    }
});
</script>

<?php include 'includes/footer.php'; ?>