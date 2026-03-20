<!-- service-details.php placeholder -->
 <?php
require_once 'includes/config.php';

$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$service = getService($service_id);

if (!$service) {
    header("Location: services.php");
    exit();
}

$page_title = $service['service_name'];
include 'includes/header.php';

// Get related services
$related_sql = "SELECT * FROM services WHERE category = '{$service['category']}' 
                AND service_id != $service_id AND is_active = 1 LIMIT 3";
$related_result = mysqli_query($conn, $related_sql);
$related_services = mysqli_fetch_all($related_result, MYSQLI_ASSOC);
?>

<!-- Service Details -->
<section class="service-details py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8" data-aos="fade-right">
                <div class="service-content">
                    <div class="service-header mb-4">
                        <h1 class="display-5"><?php echo $service['service_name']; ?></h1>
                        <div class="service-meta">
                            <span class="badge bg-primary"><?php echo $service['category']; ?></span>
                            <span class="badge bg-info"><?php echo $service['price_range']; ?></span>
                        </div>
                    </div>
                    
                    <div class="service-description mb-4">
                        <h4>Service Overview</h4>
                        <p><?php echo nl2br($service['full_description']); ?></p>
                    </div>
                    
                    <?php if ($service['features']): ?>
                    <div class="service-features mb-4">
                        <h4>Key Features</h4>
                        <div class="row">
                            <?php foreach ($service['features'] as $feature): ?>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo $feature; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="service-process mb-4">
                        <h4>Our Process</h4>
                        <div class="process-steps">
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <div class="step-circle">1</div>
                                    <p>Requirement Analysis</p>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="step-circle">2</div>
                                    <p>Planning & Design</p>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="step-circle">3</div>
                                    <p>Development</p>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="step-circle">4</div>
                                    <p>Testing & Delivery</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-left">
                <div class="service-sidebar">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Request This Service</h5>
                            <form id="requestServiceForm" method="POST" action="api/service-request.php">
                                <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                                <div class="mb-3">
                                    <label for="details" class="form-label">Project Details</label>
                                    <textarea class="form-control" id="details" name="details" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="budget" class="form-label">Estimated Budget (USD)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" required>
                                </div>
                                <div class="mb-3">
                                    <label for="preferred_date" class="form-label">Preferred Start Date</label>
                                    <input type="date" class="form-control" id="preferred_date" name="preferred_date" required>
                                </div>
                                <?php if (isLoggedIn()): ?>
                                    <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                                <?php else: ?>
                                    <p class="text-muted">Please <a href="login.php">login</a> to request this service</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (!empty($related_services)): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Related Services</h5>
                            <ul class="list-unstyled">
                                <?php foreach ($related_services as $related): ?>
                                <li class="mb-2">
                                    <a href="service-details.php?id=<?php echo $related['service_id']; ?>" class="text-decoration-none">
                                        <i class="fas fa-arrow-right text-primary me-2"></i>
                                        <?php echo $related['service_name']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Frequently Asked Questions</h2>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How long does it take to complete a project?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Project duration depends on the complexity and requirements. Typically, a standard website takes 2-4 weeks, while complex applications may take 2-3 months.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Do you provide maintenance after project completion?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we offer various maintenance packages to keep your website or application running smoothly after launch.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept bank transfers, credit/debit cards, PayPal, and various other online payment methods.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>