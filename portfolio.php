<!-- portfolio.php placeholder -->
 <?php
require_once 'includes/config.php';
$page_title = 'Portfolio';
include 'includes/header.php';

// Get all portfolio items
$portfolio_items = getPortfolio();

// Get unique categories for filter
$categories_sql = "SELECT DISTINCT category FROM portfolio ORDER BY category";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// Get portfolio statistics
$stats_sql = "SELECT 
                COUNT(*) as total_projects,
                COUNT(DISTINCT category) as total_categories,
                MAX(YEAR(completion_date)) as latest_year
              FROM portfolio";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4">Our Portfolio</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white">Home</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Portfolio</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-md-end">
                <p class="lead mb-0"><?php echo $stats['total_projects']; ?>+ Projects Completed</p>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Stats -->
<section class="stats-section py-4 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-3" data-aos="fade-up">
                <div class="stat-item">
                    <h3 class="counter display-4 text-primary" data-count="<?php echo $stats['total_projects']; ?>">0</h3>
                    <p class="mb-0">Total Projects</p>
                </div>
            </div>
            <div class="col-md-4 mb-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-item">
                    <h3 class="counter display-4 text-primary" data-count="<?php echo $stats['total_categories']; ?>">0</h3>
                    <p class="mb-0">Service Categories</p>
                </div>
            </div>
            <div class="col-md-4 mb-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-item">
                    <h3 class="display-4 text-primary"><?php echo $stats['latest_year']; ?></h3>
                    <p class="mb-0">Latest Project</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Filter -->
<section class="portfolio-filter py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="filter-buttons text-center">
                    <button class="btn btn-primary filter-btn active" data-filter="all">All Projects</button>
                    <?php foreach ($categories as $category): ?>
                    <button class="btn btn-outline-primary filter-btn" data-filter="<?php echo strtolower($category['category']); ?>">
                        <?php echo $category['category']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Portfolio Grid -->
<section class="portfolio-grid py-5">
    <div class="container">
        <div class="row" id="portfolio-container">
            <?php if (empty($portfolio_items)): ?>
                <div class="col-12 text-center">
                    <p class="lead">No portfolio items found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($portfolio_items as $index => $item): ?>
                <div class="col-lg-4 col-md-6 mb-4 portfolio-item" 
                     data-category="<?php echo strtolower($item['category']); ?>"
                     data-aos="fade-up" 
                     data-aos-delay="<?php echo ($index % 3) * 100; ?>">
                    <div class="portfolio-card">
                        <img src="<?php echo $item['image_url'] ?? 'assets/images/portfolio/default.jpg'; ?>" 
                             alt="<?php echo $item['project_name']; ?>" 
                             class="img-fluid">
                        <div class="portfolio-overlay">
                            <span class="badge bg-primary mb-2"><?php echo $item['category']; ?></span>
                            <h5><?php echo $item['project_name']; ?></h5>
                            <p class="small"><?php echo substr($item['description'], 0, 100); ?>...</p>
                            <div class="portfolio-tech mb-2">
                                <small><i class="fas fa-code me-1"></i> <?php echo $item['technologies']; ?></small>
                            </div>
                            <button class="btn btn-light btn-sm" onclick="showProjectDetails(<?php echo $item['project_id']; ?>)">
                                <i class="fas fa-eye me-1"></i> View Details
                            </button>
                            <?php if (!empty($item['project_url'])): ?>
                            <a href="<?php echo $item['project_url']; ?>" target="_blank" class="btn btn-outline-light btn-sm ms-2">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Load More Button -->
        <?php if (count($portfolio_items) > 9): ?>
        <div class="text-center mt-4">
            <button id="loadMore" class="btn btn-primary" onclick="loadMoreProjects()">
                <i class="fas fa-spinner me-2"></i>Load More Projects
            </button>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Testimonials from Portfolio Clients -->
<section class="portfolio-testimonials py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">What Our Clients Say</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Client satisfaction is our priority</p>
        </div>
        
        <div class="row">
            <?php
            // Get client testimonials related to portfolio
            $testimonials_sql = "SELECT t.*, p.project_name 
                                FROM testimonials t 
                                LEFT JOIN portfolio p ON t.company LIKE CONCAT('%', p.client_name, '%')
                                WHERE t.is_approved = 1 
                                ORDER BY t.testimonial_id DESC LIMIT 3";
            $testimonials_result = mysqli_query($conn, $testimonials_sql);
            $testimonials = mysqli_fetch_all($testimonials_result, MYSQLI_ASSOC);
            ?>
            
            <?php foreach ($testimonials as $index => $testimonial): ?>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="testimonial-card h-100">
                    <div class="testimonial-content p-4">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="testimonial-text mb-3">"<?php echo $testimonial['testimonial_text']; ?>"</p>
                        <?php if (!empty($testimonial['project_name'])): ?>
                        <p class="small text-primary mb-2">Project: <?php echo $testimonial['project_name']; ?></p>
                        <?php endif; ?>
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

<!-- Project Details Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="projectModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<section class="cta-section py-5 text-white">
    <div class="container text-center">
        <h2 class="mb-4" data-aos="fade-up">Have a Project in Mind?</h2>
        <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Let's bring your ideas to life with our expertise</p>
        <a href="contact.php" class="btn btn-light btn-lg" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-paper-plane me-2"></i>Start Your Project
        </a>
    </div>
</section>

<script>
// Portfolio filtering
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        const filterValue = this.getAttribute('data-filter');
        const items = document.querySelectorAll('.portfolio-item');
        
        items.forEach(item => {
            if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                item.style.display = 'block';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                }, 50);
            } else {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    item.style.display = 'none';
                }, 300);
            }
        });
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('filter', filterValue);
        window.history.pushState({}, '', url);
    });
});

// Check for filter in URL on page load
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const filter = urlParams.get('filter');
    if (filter) {
        const button = document.querySelector(`.filter-btn[data-filter="${filter}"]`);
        if (button) {
            button.click();
        }
    }
});

// Show project details in modal
function showProjectDetails(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('projectModal'));
    const modalTitle = document.getElementById('projectModalTitle');
    const modalBody = document.getElementById('projectModalBody');
    
    modalTitle.textContent = 'Loading Project...';
    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    modal.show();
    
    // Fetch project details via AJAX
    fetch(`api/get-project.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalTitle.textContent = data.project.project_name;
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <img src="${data.project.image_url}" alt="${data.project.project_name}" class="img-fluid rounded mb-3">
                        </div>
                        <div class="col-md-6">
                            <p><strong>Client:</strong> ${data.project.client_name}</p>
                            <p><strong>Category:</strong> ${data.project.category}</p>
                            <p><strong>Technologies:</strong> ${data.project.technologies}</p>
                            <p><strong>Completed:</strong> ${new Date(data.project.completion_date).toLocaleDateString()}</p>
                            ${data.project.project_url ? `<a href="${data.project.project_url}" target="_blank" class="btn btn-primary">View Live Project</a>` : ''}
                        </div>
                        <div class="col-12 mt-3">
                            <h6>Project Description:</h6>
                            <p>${data.project.description}</p>
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load project details.</div>';
            }
        })
        .catch(error => {
            modalBody.innerHTML = '<div class="alert alert-danger">Error loading project details.</div>';
        });
}

// Load more projects (pagination)
let currentPage = 1;
function loadMoreProjects() {
    const button = document.getElementById('loadMore');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
    button.disabled = true;
    
    currentPage++;
    
    fetch(`api/get-projects.php?page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.projects.length > 0) {
                const container = document.getElementById('portfolio-container');
                
                data.projects.forEach((project, index) => {
                    const delay = (index % 3) * 100;
                    const projectHtml = `
                        <div class="col-lg-4 col-md-6 mb-4 portfolio-item" 
                             data-category="${project.category.toLowerCase()}"
                             data-aos="fade-up" 
                             data-aos-delay="${delay}">
                            <div class="portfolio-card">
                                <img src="${project.image_url}" alt="${project.project_name}" class="img-fluid">
                                <div class="portfolio-overlay">
                                    <span class="badge bg-primary mb-2">${project.category}</span>
                                    <h5>${project.project_name}</h5>
                                    <p>${project.description.substring(0, 100)}...</p>
                                    <div class="portfolio-tech mb-2">
                                        <small><i class="fas fa-code me-1"></i> ${project.technologies}</small>
                                    </div>
                                    <button class="btn btn-light btn-sm" onclick="showProjectDetails(${project.project_id})">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.insertAdjacentHTML('beforeend', projectHtml);
                });
                
                // Reinitialize AOS for new elements
                AOS.refresh();
                
                if (!data.hasMore) {
                    button.style.display = 'none';
                }
            } else {
                button.style.display = 'none';
            }
            
            button.innerHTML = originalText;
            button.disabled = false;
        })
        .catch(error => {
            button.innerHTML = originalText;
            button.disabled = false;
            alert('Failed to load more projects. Please try again.');
        });
}
</script>

<?php include 'includes/footer.php'; ?>