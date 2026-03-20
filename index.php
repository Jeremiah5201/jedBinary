<!-- index.php placeholder -->
 <?php
require_once 'includes/config.php';
$page_title = 'Home';
include 'includes/header.php';

// Get featured services
$featured_services = getServices(6);

// Get portfolio items
$portfolio_items = getPortfolio(4);

// Get testimonials
$testimonials = getTestimonials(3);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="assets/images/hero-bg1.jpg" class="d-block w-100" alt="Hero Image">
                <div class="carousel-caption">
                    <h1 class="display-4" data-aos="fade-up">JED BINARY TECH SOLUTIONS</h1>
                    <p class="lead" data-aos="fade-up" data-aos-delay="200">Your Trusted Technology Partner</p>
                    <a href="services.php" class="btn btn-primary btn-lg" data-aos="fade-up" data-aos-delay="400">Explore Services</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Services</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Comprehensive IT Solutions for Your Business</p>
        </div>
        <div class="row">
            <?php foreach ($featured_services as $service): ?>
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $loop * 100; ?>">
                <div class="service-card h-100">
                    <div class="card-body text-center">
                        <div class="service-icon">
                            <i class="fas <?php echo $service['icon_class']; ?> fa-3x"></i>
                        </div>
                        <h5 class="card-title"><?php echo $service['service_name']; ?></h5>
                        <p class="card-text"><?php echo $service['short_description']; ?></p>
                        <a href="service-details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-outline-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="services.php" class="btn btn-primary">View All Services</a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <img src="assets/images/about-us.jpg" alt="About Us" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <h2 class="section-title">Why Choose Us?</h2>
                <p class="lead">We deliver excellence through innovation and expertise</p>
                <div class="features-list">
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>10+ Years Experience</strong> in IT industry
                    </div>
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>500+ Projects</strong> completed successfully
                    </div>
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>24/7 Support</strong> for all clients
                    </div>
                    <div class="feature-item mb-3">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>Expert Team</strong> of certified professionals
                    </div>
                </div>
                <a href="about.php" class="btn btn-primary mt-3">Read More</a>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Section -->
<section class="portfolio-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Recent Work</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Check out some of our successful projects</p>
        </div>
        <div class="row">
            <?php foreach ($portfolio_items as $index => $item): ?>
            <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="portfolio-card">
                    <img src="<?php echo $item['image_url']; ?>" class="img-fluid" alt="<?php echo $item['project_name']; ?>">
                    <div class="portfolio-overlay">
                        <h5><?php echo $item['project_name']; ?></h5>
                        <p><?php echo $item['category']; ?></p>
                        <a href="portfolio-details.php?id=<?php echo $item['project_id']; ?>" class="btn btn-light btn-sm">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">What Our Clients Say</h2>
        </div>
        <div class="row">
            <?php foreach ($testimonials as $index => $testimonial): ?>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="testimonial-card h-100">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="testimonial-text">"<?php echo $testimonial['testimonial_text']; ?>"</p>
                        <div class="rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="client-info">
                            <h6 class="mb-0"><?php echo $testimonial['client_name']; ?></h6>
                            <small><?php echo $testimonial['client_position']; ?>, <?php echo $testimonial['company']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Contact CTA Section -->
<section class="cta-section py-5 text-white">
    <div class="container text-center">
        <h2 class="mb-4" data-aos="fade-up">Ready to Start Your Project?</h2>
        <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Get in touch with us today for a free consultation</p>
        <a href="contact.php" class="btn btn-light btn-lg" data-aos="fade-up" data-aos-delay="200">Contact Us Now</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>