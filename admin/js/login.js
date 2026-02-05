// Login Page Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    window.togglePassword = function() {
        const passwordInput = document.querySelector('input[name="password"]');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput && toggleIcon) {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            
            // Add a subtle animation
            toggleIcon.style.transform = 'scale(0.8)';
            setTimeout(() => {
                toggleIcon.style.transform = 'scale(1)';
            }, 150);
        }
    };

    // Form validation and submission
    const loginForm = document.querySelector('.login-form');
    const loginBtn = document.querySelector('.login-btn');
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            
            // Clear previous errors
            clearErrors();
            
            let hasErrors = false;
            
            // Validate inputs
            if (!username) {
                showError(usernameInput, 'Username is required');
                hasErrors = true;
            }
            
            if (!password) {
                showError(passwordInput, 'Password is required');
                hasErrors = true;
            }
            
            // Only prevent submission if there are validation errors
            if (hasErrors) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state for valid submissions
            if (loginBtn) {
                loginBtn.classList.add('loading');
                const btnText = loginBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.textContent = 'Logging in...';
                }
            }
            
            // Allow form to submit naturally
            return true;
        });
    }

    // Real-time validation
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            clearFieldError(this);
        });
        
        usernameInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
                showError(this, 'Username is required');
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            clearFieldError(this);
        });
        
        passwordInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
                showError(this, 'Password is required');
            }
        });
    }

    // FIXED: Simplified button click handler - removed duplicate event listeners
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            // Simple click feedback without interfering with form submission
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    }

    // Auto-focus username field
    if (usernameInput) {
        setTimeout(() => {
            usernameInput.focus();
        }, 500);
    }

    // Entrance animations
    animateEntrance();
    
    // Background animations - FIXED: Added safety check
    if (document.querySelector('.floating-shape')) {
        initBackgroundAnimations();
    }
    
    // Error message auto-hide
    setTimeout(() => {
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => {
                existingError.remove();
            }, 300);
        }
    }, 5000);
});

function showError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#ef4444';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '5px';
    errorDiv.style.animation = 'fadeIn 0.3s ease';
    
    field.parentNode.appendChild(errorDiv);
    
    // Add shake animation to field
    field.style.animation = 'shake 0.5s ease';
    setTimeout(() => {
        field.style.animation = '';
    }, 500);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function clearErrors() {
    document.querySelectorAll('.field-error').forEach(error => error.remove());
    document.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
}

function animateEntrance() {
    const loginCard = document.querySelector('.login-card');
    const formElements = document.querySelectorAll('.form-group, .login-btn, .login-footer');
    
    if (loginCard) {
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(30px) scale(0.95)';
        
        setTimeout(() => {
            loginCard.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0) scale(1)';
        }, 100);
    }
    
    formElements.forEach((element, index) => {
        if (element) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.4s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 200 + (index * 100));
        }
    });
}

function initBackgroundAnimations() {
    const shapes = document.querySelectorAll('.floating-shape');
    
    shapes.forEach((shape, index) => {
        // Random initial position
        const randomX = Math.random() * 100;
        const randomY = Math.random() * 100;
        const randomSize = 60 + Math.random() * 80;
        const randomDelay = Math.random() * 2;
        
        shape.style.left = randomX + '%';
        shape.style.top = randomY + '%';
        shape.style.width = randomSize + 'px';
        shape.style.height = randomSize + 'px';
        shape.style.animationDelay = randomDelay + 's';
        shape.style.animationDuration = (6 + Math.random() * 4) + 's';
        
        // FIXED: Removed interactive hover effects that could interfere with clicking
        // The shapes now have pointer-events: none in CSS
    });
}

// FIXED: Removed duplicate DOMContentLoaded listener and simplified

// Add CSS animations if not already defined
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .form-input.error {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .field-error {
        animation: fadeIn 0.3s ease;
    }
`;

document.head.appendChild(style);