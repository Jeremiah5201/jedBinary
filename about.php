<!-- about.php placeholder -->
 <?php
require_once 'includes/config.php';
$page_title = 'About Us';
include 'includes/header.php';

// Get company statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM users WHERE user_type = 'client') as total_clients,
                (SELECT COUNT(*) FROM portfolio) as total_projects,
                (SELECT COUNT(*) FROM service_requests WHERE status = 'completed') as completed_projects,
                (SELECT COUNT(*) FROM testimonials WHERE is_approved = 1) as total_testimonials
              FROM dual";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get team members (you can create a team table or use static data)
$team_members = [
    [
        'name' => 'John Doe',
        'position' => 'Founder & CEO',
        'image' => 'assets/images/team/ceo.jpg',
        'bio' => '15+ years of experience in IT consulting and software development.',
        'social' => ['linkedin' => '#', 'twitter' => '#']
    ],
    [
        'name' => 'Jane Smith',
        'position' => 'Technical Director',
        'image' => 'assets/images/team/tech-director.jpg',
        'bio' => 'Expert in full-stack development and cloud architecture.',
        'social' => ['linkedin' => '#', 'twitter' => '#']
    ],
    [
        'name' => 'Mike Johnson',
        'position' => 'Lead Developer',
        'image' => 'assets/images/team/lead-dev.jpg',
        'bio' => 'Specializes in mobile app development and system architecture.',
        'social' => ['linkedin' => '#', 'github' => '#']
    ],
    [
        'name' => 'Sarah Williams',
        'position' => 'Project Manager',
        'image' => 'assets/images/team/pm.jpg',
        'bio' => 'Ensures timely delivery and client satisfaction across all projects.',
        'social' => ['linkedin' => '#', 'twitter' => '#']
    ]
];
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <h1 class="display-4">About Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">About</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Company Introduction -->
<section class="company-intro py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4" data-aos="fade-right">
                <img src="assets/images/about/company.jpg" alt="JED BINARY TECH" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <h2 class="section-title">Welcome to JED BINARY TECH SOLUTIONS</h2>
                <p class="lead">Your Trusted Technology Partner Since 2015</p>
                <p>JED BINARY TECH SOLUTIONS AND CONSULTANCY is a premier technology solutions provider dedicated to delivering innovative and reliable IT services to businesses and individuals. We combine technical expertise with creative thinking to solve complex challenges and drive digital transformation.</p>
                <p>Our mission is to empower businesses with cutting-edge technology solutions that enhance productivity, streamline operations, and drive growth. We believe in building long-term partnerships with our clients, understanding their unique needs, and delivering customized solutions that exceed expectations.</p>
                
                <div class="row mt-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Expert Team</h6>
                                <small class="text-muted">Certified professionals</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-clock text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">24/7 Support</h6>
                                <small class="text-muted">Round the clock</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-shield-alt text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Quality Assured</h6>
                                <small class="text-muted">100% satisfaction</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-rocket text-success fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-0">Fast Delivery</h6>
                                <small class="text-muted">On-time completion</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Company Stats -->
<section class="stats-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6 mb-4" data-aos="fade-up">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3 class="counter mb-2" data-count="<?php echo $stats['total_clients'] ?: 500; ?>">0</h3>
                    <p class="mb-0">Happy Clients</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
                    <h3 class="counter mb-2" data-count="<?php echo $stats['total_projects'] ?: 200; ?>">0</h3>
                    <p class="mb-0">Projects Done</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                    <h3 class="counter mb-2" data-count="<?php echo $stats['completed_projects'] ?: 180; ?>">0</h3>
                    <p class="mb-0">Successful Projects</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card text-center p-4">
                    <i class="fas fa-award fa-3x text-primary mb-3"></i>
                    <h3 class="counter mb-2" data-count="15">0</h3>
                    <p class="mb-0">Years Experience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Story / Timeline -->
<section class="story-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Journey</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">From humble beginnings to industry leaders</p>
        </div>
        
        <div class="timeline">
            <div class="row">
                <div class="col-md-6 offset-md-6" data-aos="fade-left">
                    <div class="timeline-item mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">2024</span>
                                <h5>Expansion to International Markets</h5>
                                <p>Opened offices in UAE and Singapore, serving clients globally.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6" data-aos="fade-right">
                    <div class="timeline-item mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">2022</span>
                                <h5>Crossed 500+ Projects</h5>
                                <p>Successfully completed 500+ projects with 98% client satisfaction.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 offset-md-6" data-aos="fade-left">
                    <div class="timeline-item mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">2020</span>
                                <h5>Launched Mobile App Development</h5>
                                <p>Expanded services to include iOS and Android app development.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6" data-aos="fade-right">
                    <div class="timeline-item mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">2018</span>
                                <h5>Team Expansion</h5>
                                <p>Grew to 50+ employees with dedicated development teams.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 offset-md-6" data-aos="fade-left">
                    <div class="timeline-item mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">2015</span>
                                <h5>Company Founded</h5>
                                <p>JED BINARY TECH SOLUTIONS started with a vision to transform businesses through technology.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="mission-vision py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4" data-aos="fade-right">
                <div class="card h-100">
                    <div class="card-body text-center p-5">
                        <div class="icon-box mb-4">
                            <i class="fas fa-bullseye fa-4x text-primary"></i>
                        </div>
                        <h3 class="mb-3">Our Mission</h3>
                        <p class="lead mb-4">To empower businesses with innovative technology solutions that drive growth and success.</p>
                        <p>We strive to deliver exceptional value to our clients through cutting-edge technology, expert consulting, and unwavering commitment to quality. Our mission is to be the catalyst for digital transformation, helping businesses adapt, evolve, and thrive in an ever-changing technological landscape.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4" data-aos="fade-left">
                <div class="card h-100">
                    <div class="card-body text-center p-5">
                        <div class="icon-box mb-4">
                            <i class="fas fa-eye fa-4x text-primary"></i>
                        </div>
                        <h3 class="mb-3">Our Vision</h3>
                        <p class="lead mb-4">To be the most trusted and innovative technology solutions provider globally.</p>
                        <p>We envision a future where technology seamlessly integrates with business processes to create unprecedented value. Our goal is to lead this transformation by continuously innovating, adapting to emerging technologies, and maintaining the highest standards of service excellence.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values -->
<section class="values-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Core Values</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">The principles that guide everything we do</p>
        </div>
        
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in">
                <div class="value-card text-center p-4">
                    <div class="value-icon mb-3">
                        <i class="fas fa-heart fa-3x text-primary"></i>
                    </div>
                    <h5>Integrity</h5>
                    <p class="text-muted">We uphold the highest standards of honesty and transparency in all our dealings.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="100">
                <div class="value-card text-center p-4">
                    <div class="value-icon mb-3">
                        <i class="fas fa-lightbulb fa-3x text-primary"></i>
                    </div>
                    <h5>Innovation</h5>
                    <p class="text-muted">We constantly explore new ideas and technologies to deliver cutting-edge solutions.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="200">
                <div class="value-card text-center p-4">
                    <div class="value-icon mb-3">
                        <i class="fas fa-star fa-3x text-primary"></i>
                    </div>
                    <h5>Excellence</h5>
                    <p class="text-muted">We strive for excellence in every project, no matter how big or small.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="300">
                <div class="value-card text-center p-4">
                    <div class="value-icon mb-3">
                        <i class="fas fa-handshake fa-3x text-primary"></i>
                    </div>
                    <h5>Partnership</h5>
                    <p class="text-muted">We build lasting relationships with our clients based on trust and mutual success.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Meet Our Team</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">The experts behind your success</p>
        </div>
        
        <div class="row">
            <?php foreach ($team_members as $index => $member): ?>
            <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="team-card card h-100">
                    <img src="<?php echo $member['image']; ?>" class="card-img-top" alt="<?php echo $member['name']; ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1"><?php echo $member['name']; ?></h5>
                        <p class="text-primary mb-2"><?php echo $member['position']; ?></p>
                        <p class="card-text small"><?php echo $member['bio']; ?></p>
                        <div class="team-social">
                            <?php if (isset($member['social']['linkedin'])): ?>
                            <a href="<?php echo $member['social']['linkedin']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (isset($member['social']['twitter'])): ?>
                            <a href="<?php echo $member['social']['twitter']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (isset($member['social']['github'])): ?>
                            <a href="<?php echo $member['social']['github']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fab fa-github"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <p class="lead mb-3">Want to join our team?</p>
            <a href="careers.php" class="btn btn-primary">View Careers</a>
        </div>
    </div>
</section>

<!-- Clients / Partners -->
<section class="clients-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Our Trusted Partners</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Companies that trust us with their technology needs</p>
        </div>
        
        <div class="row align-items-center">
            <?php
            $clients = [
                'client1.png', 'client2.png', 'client3.png', 
                'client4.png', 'client5.png', 'client6.png'
            ];
            ?>
            
            <?php foreach ($clients as $index => $client): ?>
            <div class="col-lg-2 col-md-4 col-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                <div class="client-logo">
                    <img src="assets/images/clients/<?php echo $client; ?>" 
                         alt="Client" 
                         class="img-fluid grayscale">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us Detailed -->
<section class="why-choose-us py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Why Choose JED BINARY TECH?</h2>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-right">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>10+ Years Experience</h5>
                        <p>Over a decade of serving clients across various industries with excellence.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-up">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>Expert Team</h5>
                        <p>Certified professionals with deep expertise in latest technologies.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-left">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>Quality Assurance</h5>
                        <p>Rigorous testing and quality checks for all deliverables.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-right">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>24/7 Support</h5>
                        <p>Round-the-clock technical support for all your needs.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-up">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>Competitive Pricing</h5>
                        <p>Best value for your investment with flexible payment options.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-left">
                <div class="feature-box d-flex">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>Timely Delivery</h5>
                        <p>We respect your time and deliver projects on schedule.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">What People Say About Us</h2>
        </div>
        
        <div class="row">
            <?php
            $testimonials_sql = "SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY testimonial_id DESC LIMIT 3";
            $testimonials_result = mysqli_query($conn, $testimonials_sql);
            $testimonials = mysqli_fetch_all($testimonials_result, MYSQLI_ASSOC);
            
            foreach ($testimonials as $index => $testimonial):
            ?>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="testimonial-card h-100">
                    <div class="testimonial-content p-4">
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

<!-- Call to Action -->
<section class="cta-section py-5 text-white">
    <div class="container text-center">
        <h2 class="mb-4" data-aos="fade-up">Ready to Work With Us?</h2>
        <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Let's discuss how we can help your business grow</p>
        <a href="contact.php" class="btn btn-light btn-lg" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-handshake me-2"></i>Get in Touch
        </a>
    </div>
</section>

<style>
/* Timeline styling */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--primary-color);
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    width: 50%;
    padding: 0 40px 0 0;
}

.timeline-item:nth-child(even) {
    margin-left: 50%;
    padding: 0 0 0 40px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    right: -6px;
    top: 20px;
    width: 12px;
    height: 12px;
    background: var(--primary-color);
    border-radius: 50%;
}

.timeline-item:nth-child(even)::before {
    left: -6px;
    right: auto;
}

@media (max-width: 768px) {
    .timeline::before {
        left: 30px;
    }
    
    .timeline-item,
    .timeline-item:nth-child(even) {
        width: 100%;
        margin-left: 0;
        padding: 0 0 0 60px;
    }
    
    .timeline-item::before,
    .timeline-item:nth-child(even)::before {
        left: 24px;
        right: auto;
    }
}

/* Value cards */
.value-card {
    background: white;
    border-radius: 10px;
    transition: all 0.3s ease;
    height: 100%;
}

.value-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.value-icon {
    transition: all 0.3s ease;
}

.value-card:hover .value-icon {
    transform: scale(1.1);
}

/* Team cards */
.team-card {
    overflow: hidden;
    transition: all 0.3s ease;
}

.team-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.team-card img {
    height: 250px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.team-card:hover img {
    transform: scale(1.1);
}

.team-social {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.team-card:hover .team-social {
    opacity: 1;
    transform: translateY(0);
}

/* Client logos */
.client-logo {
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.client-logo:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.grayscale {
    filter: grayscale(100%);
    opacity: 0.7;
    transition: all 0.3s ease;
}

.client-logo:hover .grayscale {
    filter: grayscale(0);
    opacity: 1;
}

/* Feature boxes */
.feature-box {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
}

.feature-box:hover {
    transform: translateX(10px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.feature-icon {
    min-width: 50px;
}
</style>

<?php include 'includes/footer.php'; ?>