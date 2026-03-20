<!-- footer.php placeholder -->
     </main>
    <!-- Main Content End -->
    
    <!-- Footer -->
    <footer class="footer bg-dark text-light pt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>About JED BINARY TECH</h5>
                    <p>We are a leading technology solutions provider offering comprehensive IT services including web development, mobile app development, software installations, and consultancy services.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/index.php" class="text-light">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">Services</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/portfolio.php" class="text-light">Portfolio</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php" class="text-light">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Our Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">Website Development</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">Mobile App Development</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">Software Installation</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">Passport Assistance</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services.php" class="text-light">System Development</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Tech Park, Bangalore - 560001</li>
                        <li><i class="fas fa-phone me-2"></i> +91 9876543210</li>
                        <li><i class="fas fa-envelope me-2"></i> info@jedbinary.com</li>
                        <li><i class="fas fa-clock me-2"></i> Mon-Sat: 9:00 AM - 7:00 PM</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> JED BINARY TECH SOLUTIONS AND CONSULTANCY. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>