<!-- contact.php placeholder -->
 <?php
require_once 'includes/config.php';
$page_title = 'Contact Us';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <h1 class="display-4">Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Contact</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4" data-aos="fade-right">
                <div class="contact-info">
                    <h3 class="mb-4">Get In Touch</h3>
                    
                    <div class="info-item d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Visit Us</h5>
                            <p>123 Tech Park,<br>Electronic City, Bangalore<br>Karnataka - 560001</p>
                        </div>
                    </div>
                    
                    <div class="info-item d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-phone fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Call Us</h5>
                            <p>+91 9876543210<br>+91 9876543211</p>
                        </div>
                    </div>
                    
                    <div class="info-item d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-envelope fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Email Us</h5>
                            <p>info@jedbinary.com<br>support@jedbinary.com</p>
                        </div>
                    </div>
                    
                    <div class="info-item d-flex mb-4">
                        <div class="icon-box me-3">
                            <i class="fas fa-clock fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Working Hours</h5>
                            <p>Monday - Saturday: 9:00 AM - 7:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>
                    
                    <div class="social-media mt-4">
                        <h5>Follow Us</h5>
                        <a href="#" class="btn btn-outline-primary btn-social me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-social me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-social me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-social me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8" data-aos="fade-left">
                <div class="contact-form">
                    <h3 class="mb-4">Send Us a Message</h3>
                    
                    <?php if (isset($_SESSION['contact_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['contact_success'];
                            unset($_SESSION['contact_success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['contact_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['contact_error'];
                            unset($_SESSION['contact_error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form id="contactForm" method="POST" action="api/submit-contact.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="service_interest" class="form-label">Service Interested In</label>
                                <select class="form-select" id="service_interest" name="service_interest">
                                    <option value="">Select a service</option>
                                    <option value="Website Development">Website Development</option>
                                    <option value="Mobile App Development">Mobile App Development</option>
                                    <option value="Software Installation">Software Installation</option>
                                    <option value="Passport Assistance">Passport Assistance</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="your-recaptcha-site-key"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3888.123456789!2d77.123456789!3d12.123456789!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTLCsDA3JzI2LjgiTiA3N8KwMDcnMTUuOSJF!5e0!3m2!1sen!2sin!4v1234567890"
        width="100%" 
        height="400" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy">
    </iframe>
</section>

<?php include 'includes/footer.php'; ?>