-- Create database
CREATE DATABASE IF NOT EXISTS jedbinary_tech;
USE jedbinary_tech;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    user_type ENUM('client', 'admin') DEFAULT 'client',
    profile_image VARCHAR(255),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Services table
CREATE TABLE services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    short_description VARCHAR(200),
    full_description TEXT,
    price_range VARCHAR(50),
    icon_class VARCHAR(50),
    image_url VARCHAR(255),
    features TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service requests table
CREATE TABLE service_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    service_id INT,
    request_details TEXT,
    preferred_date DATE,
    preferred_time VARCHAR(20),
    budget DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    admin_notes TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE SET NULL
);

-- Passport assistance table
CREATE TABLE passport_assistance (
    passport_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(100),
    passport_type ENUM('new', 'renewal') NOT NULL,
    current_passport_number VARCHAR(20),
    application_number VARCHAR(50),
    documents_status ENUM('pending', 'submitted', 'verified', 'rejected') DEFAULT 'pending',
    appointment_date DATE,
    appointment_time VARCHAR(20),
    status VARCHAR(50) DEFAULT 'application_initiated',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE contact_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    service_interest VARCHAR(100),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    replied BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Portfolio table
CREATE TABLE portfolio (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(200) NOT NULL,
    category VARCHAR(50),
    client_name VARCHAR(100),
    description TEXT,
    technologies VARCHAR(255),
    image_url VARCHAR(255),
    project_url VARCHAR(255),
    completion_date DATE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Testimonials table
CREATE TABLE testimonials (
    testimonial_id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
    client_position VARCHAR(100),
    company VARCHAR(100),
    testimonial_text TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    image_url VARCHAR(255),
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blog/News table
CREATE TABLE blog_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    excerpt TEXT,
    content TEXT NOT NULL,
    author VARCHAR(100),
    image_url VARCHAR(255),
    category VARCHAR(50),
    views INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    published_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO services (service_name, category, short_description, full_description, price_range, icon_class, features, is_featured) VALUES
('Website Development', 'Development', 'Custom responsive websites built with modern technologies', 'We create stunning, responsive websites that help your business grow online. Our development process includes requirement analysis, UI/UX design, frontend development, backend integration, and thorough testing.', '$500-$5000', 'fa-code', '["Responsive Design", "SEO Optimized", "Fast Loading", "Secure Development", "Mobile Friendly", "CMS Integration"]', TRUE),
('Website Hosting', 'Infrastructure', 'Reliable hosting solutions with 99.9% uptime', 'Get fast, secure, and reliable hosting for your websites. We offer shared hosting, VPS, and dedicated servers with 24/7 monitoring and support.', '$50-$200/year', 'fa-server', '["99.9% Uptime", "24/7 Support", "Free SSL", "Daily Backups", "cPanel Access", "1-Click Install"]', TRUE),
('Mobile App Development', 'Development', 'iOS and Android app development', 'Transform your ideas into powerful mobile applications. We develop native and cross-platform apps that deliver exceptional user experiences.', '$1000-$10000', 'fa-mobile-alt', '["Native & Cross-platform", "UI/UX Design", "API Integration", "Push Notifications", "App Store Submission", "Maintenance Support"]', TRUE);

INSERT INTO portfolio (project_name, category, client_name, description, technologies, image_url, completion_date, is_featured) VALUES
('E-Commerce Platform', 'Web Development', 'Fashion Store Inc.', 'A full-featured e-commerce platform with product management, cart, and payment integration.', 'PHP, MySQL, JavaScript, Stripe', 'assets/images/portfolio/ecommerce.jpg', '2024-01-15', TRUE),
('Hospital Management System', 'System Development', 'City Hospital', 'Complete hospital management solution with patient records, appointments, and billing.', 'Python, Django, PostgreSQL', 'assets/images/portfolio/hospital.jpg', '2023-11-20', TRUE),
('Food Delivery App', 'Mobile App', 'QuickBites', 'Food delivery application with real-time tracking and payment gateway.', 'React Native, Node.js, MongoDB', 'assets/images/portfolio/foodapp.jpg', '2024-02-10', TRUE);

INSERT INTO testimonials (client_name, client_position, company, testimonial_text, rating, is_approved) VALUES
('John Smith', 'CEO', 'TechStart Solutions', 'JED Binary Tech delivered an exceptional website for our company. Their professionalism and technical expertise are outstanding.', 5, TRUE),
('Sarah Johnson', 'Marketing Director', 'Global Retail', 'The team at JED Binary Tech helped us with our passport registration process. They made a complex process simple and stress-free.', 5, TRUE),
('Mike Wilson', 'Founder', 'Digital Agency', 'Excellent service for software installations. Quick response and professional work. Highly recommended!', 4, TRUE);

-- Create admin user (password: admin123)
INSERT INTO users (full_name, email, password_hash, phone, user_type) VALUES
('Admin User', 'admin@jedbinary.com', '$2y$10$YourHashedPasswordHere', '9876543210', 'admin');