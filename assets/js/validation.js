/**
 * JED BINARY TECH SOLUTIONS
 * Form Validation and Client-side Utilities
 */

// Global validation object
const Validator = {
    
    // Email validation
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    },
    
    // Phone number validation (10 digits)
    isValidPhone: function(phone) {
        const re = /^[0-9]{10}$/;
        return re.test(phone);
    },
    
    // Mobile number with country code
    isValidMobile: function(mobile) {
        const re = /^\+?[0-9]{10,13}$/;
        return re.test(mobile);
    },
    
    // Password strength validation
    checkPasswordStrength: function(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!%*?]+/)) strength++;
        
        return strength;
    },
    
    // Get password strength text and color
    getPasswordStrengthInfo: function(strength) {
        const strengths = {
            0: { text: 'Very Weak', class: 'bg-danger', width: '20%' },
            1: { text: 'Weak', class: 'bg-danger', width: '40%' },
            2: { text: 'Fair', class: 'bg-warning', width: '60%' },
            3: { text: 'Good', class: 'bg-info', width: '80%' },
            4: { text: 'Strong', class: 'bg-success', width: '90%' },
            5: { text: 'Very Strong', class: 'bg-success', width: '100%' }
        };
        return strengths[strength] || strengths[0];
    },
    
    // Pincode validation (6 digits)
    isValidPincode: function(pincode) {
        const re = /^[0-9]{6}$/;
        return re.test(pincode);
    },
    
    // URL validation
    isValidUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    // Date validation
    isValidDate: function(date) {
        const d = new Date(date);
        return d instanceof Date && !isNaN(d);
    },
    
    // Age calculation
    calculateAge: function(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    },
    
    // File validation
    validateFile: function(file, options = {}) {
        const errors = [];
        const defaults = {
            maxSize: 5 * 1024 * 1024, // 5MB default
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
            minWidth: null,
            minHeight: null
        };
        
        const config = { ...defaults, ...options };
        
        // Check file size
        if (file.size > config.maxSize) {
            errors.push(`File size should not exceed ${config.maxSize / (1024 * 1024)}MB`);
        }
        
        // Check file type
        if (!config.allowedTypes.includes(file.type)) {
            errors.push('File type not allowed');
        }
        
        // Check image dimensions if required
        if (config.minWidth || config.minHeight) {
            if (file.type.startsWith('image/')) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = function() {
                        if (config.minWidth && this.width < config.minWidth) {
                            errors.push(`Image width should be at least ${config.minWidth}px`);
                        }
                        if (config.minHeight && this.height < config.minHeight) {
                            errors.push(`Image height should be at least ${config.minHeight}px`);
                        }
                        resolve(errors);
                    };
                    img.src = URL.createObjectURL(file);
                });
            }
        }
        
        return Promise.resolve(errors);
    },
    
    // Form field validation
    validateField: function(field, rules = {}) {
        const value = field.value.trim();
        const errors = [];
        
        // Required rule
        if (rules.required && !value) {
            errors.push('This field is required');
        }
        
        // Min length rule
        if (rules.minLength && value.length < rules.minLength) {
            errors.push(`Minimum ${rules.minLength} characters required`);
        }
        
        // Max length rule
        if (rules.maxLength && value.length > rules.maxLength) {
            errors.push(`Maximum ${rules.maxLength} characters allowed`);
        }
        
        // Pattern rule
        if (rules.pattern && !new RegExp(rules.pattern).test(value)) {
            errors.push(rules.message || 'Invalid format');
        }
        
        // Email rule
        if (rules.email && !this.isValidEmail(value)) {
            errors.push('Please enter a valid email address');
        }
        
        // Phone rule
        if (rules.phone && !this.isValidPhone(value)) {
            errors.push('Please enter a valid 10-digit phone number');
        }
        
        // Mobile rule
        if (rules.mobile && !this.isValidMobile(value)) {
            errors.push('Please enter a valid mobile number');
        }
        
        // Pincode rule
        if (rules.pincode && !this.isValidPincode(value)) {
            errors.push('Please enter a valid 6-digit pincode');
        }
        
        // URL rule
        if (rules.url && value && !this.isValidUrl(value)) {
            errors.push('Please enter a valid URL');
        }
        
        // Date rule
        if (rules.date && value && !this.isValidDate(value)) {
            errors.push('Please enter a valid date');
        }
        
        // Age rule
        if (rules.minAge && value) {
            const age = this.calculateAge(value);
            if (age < rules.minAge) {
                errors.push(`You must be at least ${rules.minAge} years old`);
            }
        }
        
        // Custom validation
        if (rules.custom && typeof rules.custom === 'function') {
            const customError = rules.custom(value);
            if (customError) {
                errors.push(customError);
            }
        }
        
        return errors;
    },
    
    // Validate entire form
    validateForm: function(form, rules) {
        const errors = {};
        let isValid = true;
        
        for (const fieldName in rules) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const fieldErrors = this.validateField(field, rules[fieldName]);
                if (fieldErrors.length > 0) {
                    errors[fieldName] = fieldErrors;
                    isValid = false;
                    this.showFieldError(field, fieldErrors[0]);
                } else {
                    this.clearFieldError(field);
                }
            }
        }
        
        return { isValid, errors };
    },
    
    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    },
    
    // Clear field error
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    },
    
    // Clear all form errors
    clearFormErrors: function(form) {
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(error => {
            error.remove();
        });
    }
};

// Form validation initialization
document.addEventListener('DOMContentLoaded', function() {
    
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                email: { required: true, email: true },
                password: { required: true, minLength: 6 }
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        // Password strength indicator
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                const strength = Validator.checkPasswordStrength(this.value);
                const info = Validator.getPasswordStrengthInfo(strength);
                
                let indicator = document.getElementById('password-strength');
                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.id = 'password-strength';
                    indicator.className = 'progress mt-2';
                    indicator.style.height = '5px';
                    this.parentNode.appendChild(indicator);
                }
                
                indicator.innerHTML = `<div class="progress-bar ${info.class}" style="width: ${info.width}"></div>`;
                
                let textIndicator = document.getElementById('password-strength-text');
                if (!textIndicator) {
                    textIndicator = document.createElement('small');
                    textIndicator.id = 'password-strength-text';
                    textIndicator.className = 'form-text';
                    this.parentNode.appendChild(textIndicator);
                }
                textIndicator.textContent = `Password strength: ${info.text}`;
            });
        }
        
        registerForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                full_name: { required: true, minLength: 3, maxLength: 100 },
                email: { required: true, email: true },
                password: { required: true, minLength: 8 },
                phone: { phone: true },
                terms: { required: true, custom: (value) => !value && 'You must agree to the terms' }
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            
            // Additional password match validation
            if (passwordField && confirmPasswordField) {
                if (passwordField.value !== confirmPasswordField.value) {
                    Validator.showFieldError(confirmPasswordField, 'Passwords do not match');
                    e.preventDefault();
                    return;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Contact form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                name: { required: true, minLength: 3 },
                email: { required: true, email: true },
                phone: { phone: true },
                message: { required: true, minLength: 10 }
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Service request form validation
    const requestForm = document.getElementById('requestServiceForm');
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                details: { required: true, minLength: 20 },
                budget: { required: true, pattern: '^[0-9]+$', message: 'Please enter a valid budget' },
                preferred_date: { required: true, custom: (value) => {
                    const selectedDate = new Date(value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate < today) {
                        return 'Preferred date cannot be in the past';
                    }
                    return null;
                }}
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Passport application form validation
    const passportForm = document.getElementById('passportForm');
    if (passportForm) {
        // Date of birth validation
        const dobField = document.getElementById('date_of_birth');
        if (dobField) {
            dobField.addEventListener('change', function() {
                const age = Validator.calculateAge(this.value);
                if (age < 18) {
                    Validator.showFieldError(this, 'You must be at least 18 years old');
                } else {
                    Validator.clearFieldError(this);
                }
            });
        }
        
        // Mobile validation
        const mobileField = document.getElementById('mobile');
        if (mobileField) {
            mobileField.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });
        }
        
        // Pincode validation
        const pincodeField = document.getElementById('pincode');
        if (pincodeField) {
            pincodeField.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
        
        passportForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                full_name: { required: true, minLength: 3 },
                date_of_birth: { required: true, minAge: 18 },
                place_of_birth: { required: true },
                gender: { required: true },
                mobile: { required: true, phone: true },
                address_line1: { required: true },
                city: { required: true },
                state: { required: true },
                pincode: { required: true, pincode: true },
                passport_type: { required: true },
                terms: { required: true }
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            
            // Additional validation for renewal
            const passportType = document.getElementById('passport_type');
            const currentPassport = document.getElementById('current_passport_number');
            
            if (passportType && passportType.value === 'renewal' && currentPassport) {
                if (!currentPassport.value.trim()) {
                    Validator.showFieldError(currentPassport, 'Current passport number is required for renewal');
                    e.preventDefault();
                    return;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Profile form validation
    const profileForm = document.querySelector('form[action="api/update-profile.php"]');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            Validator.clearFormErrors(this);
            
            const rules = {
                full_name: { required: true, minLength: 3 },
                phone: { phone: true }
            };
            
            const { isValid } = Validator.validateForm(this, rules);
            
            // Password change validation
            const currentPassword = document.querySelector('[name="current_password"]');
            const newPassword = document.querySelector('[name="new_password"]');
            const confirmPassword = document.querySelector('[name="confirm_password"]');
            
            if (newPassword && newPassword.value) {
                if (!currentPassword || !currentPassword.value) {
                    Validator.showFieldError(currentPassword, 'Current password is required to change password');
                    e.preventDefault();
                    return;
                }
                
                if (newPassword.value.length < 8) {
                    Validator.showFieldError(newPassword, 'New password must be at least 8 characters');
                    e.preventDefault();
                    return;
                }
                
                if (newPassword.value !== confirmPassword.value) {
                    Validator.showFieldError(confirmPassword, 'Passwords do not match');
                    e.preventDefault();
                    return;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});

// File upload preview functionality
const FileUploader = {
    previewImage: function(input, previewElement) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (typeof previewElement === 'string') {
                    document.getElementById(previewElement).src = e.target.result;
                } else if (previewElement instanceof Element) {
                    previewElement.src = e.target.result;
                }
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    },
    
    validateAndPreview: async function(input, previewElement, options = {}) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const errors = await Validator.validateFile(file, options);
            
            if (errors.length > 0) {
                alert(errors.join('\n'));
                input.value = '';
                return false;
            }
            
            this.previewImage(input, previewElement);
            return true;
        }
        return false;
    }
};

// Real-time input formatting
const InputFormatter = {
    formatPhone: function(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 10) value = value.slice(0, 10);
        
        if (value.length > 5) {
            value = value.slice(0, 5) + '-' + value.slice(5);
        }
        
        input.value = value;
    },
    
    formatPincode: function(input) {
        input.value = input.value.replace(/\D/g, '').slice(0, 6);
    },
    
    formatCurrency: function(input) {
        let value = input.value.replace(/\D/g, '');
        if (value) {
            value = parseInt(value, 10).toLocaleString('en-IN');
        }
        input.value = value;
    },
    
    formatAlphanumeric: function(input) {
        input.value = input.value.replace(/[^a-zA-Z0-9]/g, '');
    },
    
    formatName: function(input) {
        input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
    }
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Validator, FileUploader, InputFormatter };
}