/**
 * Authentication functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  initPasswordValidation();
  initLoginForm();
  initRegisterForm();
});

/**
 * Initialize password validation
 */
function initPasswordValidation() {
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');
  
  if (passwordInput && confirmPasswordInput) {
    // Check password strength
    passwordInput.addEventListener('input', function() {
      const password = this.value;
      const requirementEl = this.parentNode.querySelector('.password-requirements');
      
      if (requirementEl) {
        if (password.length < 8) {
          requirementEl.classList.add('error');
        } else {
          requirementEl.classList.remove('error');
        }
      }
    });
    
    // Check if passwords match
    confirmPasswordInput.addEventListener('input', function() {
      const password = passwordInput.value;
      const confirmPassword = this.value;
      
      if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
      } else {
        this.setCustomValidity('');
      }
    });
  }
}

/**
 * Initialize login form client-side validation
 */
function initLoginForm() {
  const loginForm = document.querySelector('form.auth-form');
  
  if (loginForm && loginForm.closest('.auth-page')) {
    loginForm.addEventListener('submit', function(event) {
      const emailInput = this.querySelector('#email');
      const passwordInput = this.querySelector('#password');
      let isValid = true;
      
      // Validate email
      if (emailInput && !isValidEmail(emailInput.value)) {
        event.preventDefault();
        showInputError(emailInput, 'Please enter a valid email address');
        isValid = false;
      }
      
      // Validate password
      if (passwordInput && passwordInput.value.trim() === '') {
        event.preventDefault();
        showInputError(passwordInput, 'Please enter your password');
        isValid = false;
      }
      
      return isValid;
    });
  }
}

/**
 * Initialize register form client-side validation
 */
function initRegisterForm() {
  const registerForm = document.querySelector('form.auth-form');
  
  if (registerForm && registerForm.closest('.auth-page') && registerForm.querySelector('#confirm_password')) {
    registerForm.addEventListener('submit', function(event) {
      const nameInput = this.querySelector('#name');
      const emailInput = this.querySelector('#email');
      const passwordInput = this.querySelector('#password');
      const confirmPasswordInput = this.querySelector('#confirm_password');
      const termsCheckbox = this.querySelector('#terms');
      let isValid = true;
      
      // Validate name
      if (nameInput && nameInput.value.trim() === '') {
        event.preventDefault();
        showInputError(nameInput, 'Please enter your name');
        isValid = false;
      }
      
      // Validate email
      if (emailInput && !isValidEmail(emailInput.value)) {
        event.preventDefault();
        showInputError(emailInput, 'Please enter a valid email address');
        isValid = false;
      }
      
      // Validate password
      if (passwordInput && passwordInput.value.length < 8) {
        event.preventDefault();
        showInputError(passwordInput, 'Password must be at least 8 characters long');
        isValid = false;
      }
      
      // Validate password confirmation
      if (confirmPasswordInput && passwordInput && confirmPasswordInput.value !== passwordInput.value) {
        event.preventDefault();
        showInputError(confirmPasswordInput, 'Passwords do not match');
        isValid = false;
      }
      
      // Validate terms acceptance
      if (termsCheckbox && !termsCheckbox.checked) {
        event.preventDefault();
        
        // Add error message near the checkbox
        const termsContainer = termsCheckbox.closest('.terms-acceptance');
        if (termsContainer && !termsContainer.querySelector('.error-message')) {
          const errorMessage = document.createElement('div');
          errorMessage.className = 'error-message';
          errorMessage.textContent = 'You must accept the terms to continue';
          termsContainer.appendChild(errorMessage);
        }
        
        isValid = false;
      }
      
      return isValid;
    });
  }
}

/**
 * Check if an email is valid
 * @param {string} email - The email to validate
 * @returns {boolean} Whether the email is valid
 */
function isValidEmail(email) {
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}

/**
 * Show an error message for an input field
 * @param {HTMLElement} inputElement - The input element
 * @param {string} message - The error message
 */
function showInputError(inputElement, message) {
  const formGroup = inputElement.closest('.form-group');
  
  if (formGroup) {
    formGroup.classList.add('has-error');
    
    // Remove any existing error message
    const existingError = formGroup.querySelector('.error-message');
    if (existingError) {
      existingError.remove();
    }
    
    // Add new error message
    const errorMessage = document.createElement('div');
    errorMessage.className = 'error-message';
    errorMessage.textContent = message;
    formGroup.appendChild(errorMessage);
    
    // Focus the input
    inputElement.focus();
  }
}
