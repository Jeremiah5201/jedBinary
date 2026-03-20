<?php
require_once 'includes/config.php';
$page_title = 'Services';
include 'includes/header.php';

$category = isset($_GET['category']) ? $_GET['category'] : null;
$services = getServices(null, $category);

// Get unique categories
$categories_sql = "SELECT DISTINCT category FROM services WHERE is_active = 1";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <h1 class="display-4">Our Services</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Services</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Services Filter -->
<section class="services-filter py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="filter-buttons text-center">
                    <a href="services.php" class="btn <?php echo !$category ? 'btn-primary' : 'btn-outline-primary'; ?> m-1">All Services</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="services.php?category=<?php echo urlencode($cat['category']); ?>" 
                       class="btn <?php echo $category == $cat['category'] ? 'btn-primary' : 'btn-outline-primary'; ?> m-1">
                        <?php echo $cat['category']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services List -->
<section class="services-list py-5">
    <div class="container">
        <div class="row">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center">
                    <p class="lead">No services found in this category.</p>
                </div>
            <?php else: ?>
                <?php foreach ($services as $index => $service): ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="service-card h-100">
                        <div class="card-body">
                            <div class="service-icon mb-3">
                                <i class="fas <?php echo $service['icon_class']; ?> fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title"><?php echo $service['service_name']; ?></h4>
                            <p class="card-text"><?php echo $service['short_description']; ?></p>
                            
                            <?php if ($service['features']): ?>
                            <div class="service-features mb-3">
                                <h6>Key Features:</h6>
                                <ul class="list-unstyled">
                                    <?php foreach (array_slice($service['features'], 0, 3) as $feature): ?>
                                    <li><i class="fas fa-check text-success me-2"></i> <?php echo $feature; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="service-price mb-3">
                                <span class="badge bg-info">Price Range: <?php echo $service['price_range']; ?></span>
                            </div>
                            
                            <a href="service-details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-primary">View Details</a>
                            <?php if (isLoggedIn()): ?>
                                <a href="request-service.php?id=<?php echo $service['service_id']; ?>" class="btn btn-outline-success">Request Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="why-choose-us py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why Choose Our Services</h2>
        </div>
        <div class="row">
            <div class="col-md-3 mb-4 text-center" data-aos="fade-up">
                <div class="feature-box">
                    <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                    <h5>Timely Delivery</h5>
                    <p>We respect your time and deliver projects on schedule</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-box">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h5>Quality Assured</h5>
                    <p>Rigorous testing and quality checks for all deliverables</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-box">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h5>24/7 Support</h5>
                    <p>Round-the-clock technical support for all clients</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-box">
                    <i class="fas fa-money-bill-wave fa-3x text-primary mb-3"></i>
                    <h5>Best Pricing</h5>
                    <p>Competitive prices with flexible payment options</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>