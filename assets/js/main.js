// Global variables
let currentUser = null;
let toastContainer = null;

// Document ready
$(document).ready(function() {
    // Initialize components
    initToasts();
    initFormValidation();
    initAjaxForms();
    initSmoothScroll();
    initCounter();
    
    // Load user data if logged in
    checkAuth();
});

// Toast notification system
function initToasts() {
    if (!$('.toast-container').length) {
        $('body').append('<div class="toast-container"></div>');
    }
    toastContainer = $('.toast-container');
}

function showToast(message, type = 'info', duration = 5000) {
    const toast = $(`
        <div class="toast ${type}">
            <i class="fas ${getToastIcon(type)} me-2"></i>
            ${message}
        </div>
    `);
    
    toastContainer.append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, duration);
}

function getToastIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

// Form validation
function initFormValidation() {
    // Contact form validation
    $('#contactForm').on('submit', function(e) {
        if (!validateContactForm()) {
            e.preventDefault();
        }
    });
    
    // Registration form validation
    $('#registerForm').on('submit', function(e) {
        if (!validateRegistrationForm()) {
            e.preventDefault();
        }
    });
    
    // Service request form validation
    $('#requestServiceForm').on('submit', function(e) {
        if (!validateServiceRequest()) {
            e.preventDefault();
        }
    });
}

function validateContactForm() {
    let isValid = true;
    const name = $('#name').val().trim();
    const email = $('#email').val().trim();
    const message = $('#message').val().trim();
    
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    if (!name) {
        showFieldError('name', 'Name is required');
        isValid = false;
    }
    
    if (!email) {
        showFieldError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showFieldError('email', 'Please enter a valid email');
        isValid = false;
    }
    
    if (!message) {
        showFieldError('message', 'Message is required');
        isValid = false;
    }
    
    if (!isValid) {
        showToast('Please fill all required fields correctly', 'error');
    }
    
    return isValid;
}

function validateRegistrationForm() {
    let isValid = true;
    const password = $('#password').val();
    const confirm = $('#confirm_password').val();
    
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    if (password !== confirm) {
        showFieldError('confirm_password', 'Passwords do not match');
        isValid = false;
    }
    
    if (password.length < 8) {
        showFieldError('password', 'Password must be at least 8 characters');
        isValid = false;
    }
    
    return isValid;
}

function validateServiceRequest() {
    let isValid = true;
    const budget = $('#budget').val();
    const date = $('#preferred_date').val();
    
    if (budget <= 0) {
        showFieldError('budget', 'Please enter a valid budget');
        isValid = false;
    }
    
    if (!date) {
        showFieldError('preferred_date', 'Please select a date');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(fieldId, message) {
    $(`#${fieldId}`).addClass('is-invalid');
    $(`#${fieldId}`).after(`<div class="invalid-feedback">${message}</div>`);
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// AJAX form submissions
function initAjaxForms() {
    // Newsletter subscription
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        const email = $('#newsletter-email').val();
        
        $.ajax({
            url: 'api/newsletter.php',
            method: 'POST',
            data: { email: email },
            success: function(response) {
                showToast('Successfully subscribed to newsletter!', 'success');
                $('#newsletter-email').val('');
            },
            error: function() {
                showToast('Subscription failed. Please try again.', 'error');
            }
        });
    });
}

// Smooth scroll
function initSmoothScroll() {
    $('a[href*="#"]').not('[href="#"]').click(function(e) {
        if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') 
            && location.hostname === this.hostname) {
            const target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 76
                }, 1000);
            }
        }
    });
}

// Counter animation
function initCounter() {
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = $this.attr('data-count');
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 2000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
}

// Check authentication status
function checkAuth() {
    $.ajax({
        url: 'api/check-auth.php',
        method: 'GET',
        success: function(response) {
            if (response.logged_in) {
                currentUser = response.user;
                updateUIForLoggedInUser();
            }
        }
    });
}

function updateUIForLoggedInUser() {
    $('.auth-links').hide();
    $('.user-menu').show();
    $('.user-name').text(currentUser.full_name);
}

// Search functionality
function initSearch() {
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.searchable-item').each(function() {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
}

// Modal handlers
function openServiceRequestModal(serviceId) {
    $('#service_id').val(serviceId);
    $('#serviceRequestModal').modal('show');
}

function openLoginModal(redirectUrl) {
    $('#loginRedirect').val(redirectUrl);
    $('#loginModal').modal('show');
}

// File upload preview
function handleFileUpload(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            $(previewId).attr('src', e.target.result);
            $(previewId).show();
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Date picker initialization
function initDatePicker() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        startDate: new Date(),
        autoclose: true,
        todayHighlight: true
    });
}

// Service filter
function filterServices(category) {
    $('.service-card').each(function() {
        if (category === 'all' || $(this).data('category') === category) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    $('.filter-btn').removeClass('active');
    $(`.filter-btn[data-category="${category}"]`).addClass('active');
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

// Load more functionality
let currentPage = 1;
function loadMore(url, container, loadingId) {
    currentPage++;
    
    $(loadingId).show();
    
    $.ajax({
        url: url,
        method: 'GET',
        data: { page: currentPage },
        success: function(response) {
            if (response.html) {
                $(container).append(response.html);
            }
            
            if (!response.hasMore) {
                $('.load-more-btn').hide();
            }
        },
        complete: function() {
            $(loadingId).hide();
        }
    });
}

// Countdown timer
function startCountdown(element, endDate) {
    const end = new Date(endDate).getTime();
    
    const timer = setInterval(function() {
        const now = new Date().getTime();
        const distance = end - now;
        
        if (distance < 0) {
            clearInterval(timer);
            $(element).html('Expired');
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        $(element).html(`${days}d ${hours}h ${minutes}m ${seconds}s`);
    }, 1000);
}

// Export functionality
function exportData(type, data) {
    let content, filename, link;
    
    switch(type) {
        case 'csv':
            content = convertToCSV(data);
            filename = 'export.csv';
            break;
        case 'pdf':
            // PDF generation logic
            break;
        default:
            content = JSON.stringify(data);
            filename = 'export.json';
    }
    
    if (content) {
        const blob = new Blob([content], { type: 'text/plain' });
        link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
}

function convertToCSV(data) {
    if (!data.length) return '';
    
    const headers = Object.keys(data[0]);
    const rows = data.map(obj => headers.map(header => obj[header]).join(','));
    
    return [headers.join(','), ...rows].join('\n');
}

// Initialize tooltips and popovers
function initBootstrapComponents() {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
}

// Scroll to top
function scrollToTop() {
    $('html, body').animate({ scrollTop: 0 }, 500);
}

// Show/hide scroll to top button
$(window).scroll(function() {
    if ($(this).scrollTop() > 300) {
        $('.scroll-to-top').fadeIn();
    } else {
        $('.scroll-to-top').fadeOut();
    }
});

// Window load
$(window).on('load', function() {
    // Hide preloader
    $('.preloader').fadeOut('slow');
    
    // Initialize AOS again after images load
    AOS.refresh();
});