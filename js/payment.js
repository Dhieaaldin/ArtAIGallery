/**
 * Payment processing functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  initPaymentTabs();
  initCreditCardForm();
  initPayPalPayment();
});

/**
 * Initialize payment method tabs
 */
function initPaymentTabs() {
  const paymentTabs = document.querySelectorAll('.payment-tab');
  const paymentForms = document.querySelectorAll('.payment-form');
  
  if (paymentTabs.length > 0 && paymentForms.length > 0) {
    paymentTabs.forEach(tab => {
      tab.addEventListener('click', function() {
        // Update active tab
        paymentTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Show corresponding form
        const paymentMethod = this.getAttribute('data-payment');
        paymentForms.forEach(form => {
          form.classList.remove('active');
          if (form.id === `${paymentMethod}-form`) {
            form.classList.add('active');
          }
        });
      });
    });
  }
}

/**
 * Initialize credit card form validation and submission
 */
function initCreditCardForm() {
  const processCardPaymentBtn = document.getElementById('process-card-payment');
  const cardForm = document.getElementById('credit-card-form');
  
  if (processCardPaymentBtn && cardForm) {
    // Simple input formatting
    const cardNumberInput = document.getElementById('card-number');
    const cardExpiryInput = document.getElementById('card-expiry');
    const cardCvcInput = document.getElementById('card-cvc');
    
    if (cardNumberInput) {
      cardNumberInput.addEventListener('input', function() {
        // Format card number with spaces
        let value = this.value.replace(/\D/g, '');
        if (value.length > 16) {
          value = value.slice(0, 16);
        }
        
        // Add spaces after every 4 digits
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
          if (i > 0 && i % 4 === 0) {
            formattedValue += ' ';
          }
          formattedValue += value[i];
        }
        
        this.value = formattedValue;
      });
    }
    
    if (cardExpiryInput) {
      cardExpiryInput.addEventListener('input', function() {
        // Format expiry date (MM/YY)
        let value = this.value.replace(/\D/g, '');
        if (value.length > 4) {
          value = value.slice(0, 4);
        }
        
        if (value.length > 2) {
          this.value = value.slice(0, 2) + '/' + value.slice(2);
        } else {
          this.value = value;
        }
      });
    }
    
    if (cardCvcInput) {
      cardCvcInput.addEventListener('input', function() {
        // Format CVC (3-4 digits)
        let value = this.value.replace(/\D/g, '');
        if (value.length > 4) {
          value = value.slice(0, 4);
        }
        this.value = value;
      });
    }
    
    // Form submission
    processCardPaymentBtn.addEventListener('click', function() {
      // Validate inputs
      const cardNameInput = document.getElementById('card-name');
      
      if (!cardNameInput || !cardNumberInput || !cardExpiryInput || !cardCvcInput) {
        return;
      }
      
      const cardName = cardNameInput.value.trim();
      const cardNumber = cardNumberInput.value.replace(/\s/g, '');
      const cardExpiry = cardExpiryInput.value;
      const cardCvc = cardCvcInput.value;
      
      // Simple validation
      let isValid = true;
      
      if (cardName === '') {
        showInputError(cardNameInput, 'Name on card is required');
        isValid = false;
      }
      
      if (cardNumber.length < 13 || cardNumber.length > 16) {
        showInputError(cardNumberInput, 'Invalid card number');
        isValid = false;
      }
      
      if (!cardExpiry.match(/^\d{2}\/\d{2}$/)) {
        showInputError(cardExpiryInput, 'Invalid expiry date (MM/YY)');
        isValid = false;
      }
      
      if (cardCvc.length < 3 || cardCvc.length > 4) {
        showInputError(cardCvcInput, 'Invalid CVC');
        isValid = false;
      }
      
      if (!isValid) {
        return;
      }
      
      // Show loading state
      const originalButtonText = this.textContent;
      this.disabled = true;
      this.textContent = 'Processing...';
      
      // In a real application, you would use a secure payment processor
      // For this demo, we'll just simulate a successful payment
      processPayment('credit_card')
        .then(result => {
          if (result.success) {
            // Redirect to success page
            window.location.href = 'subscribe.php?status=success';
          } else {
            // Show error message
            this.disabled = false;
            this.textContent = originalButtonText;
            
            const formContainer = cardForm.closest('.payment-options');
            showError(result.message, formContainer);
          }
        })
        .catch(error => {
          console.error('Payment error:', error);
          this.disabled = false;
          this.textContent = originalButtonText;
          
          const formContainer = cardForm.closest('.payment-options');
          showError('An error occurred while processing your payment. Please try again.', formContainer);
        });
    });
  }
}

/**
 * Initialize PayPal payment button
 */
function initPayPalPayment() {
  const processPayPalPaymentBtn = document.getElementById('process-paypal-payment');
  
  if (processPayPalPaymentBtn) {
    processPayPalPaymentBtn.addEventListener('click', function() {
      // Show loading state
      const originalButtonText = this.textContent;
      this.disabled = true;
      this.textContent = 'Redirecting to PayPal...';
      
      // In a real application, you would redirect to PayPal
      // For this demo, we'll just simulate a successful payment
      processPayment('paypal')
        .then(result => {
          if (result.success) {
            // Redirect to success page
            window.location.href = 'subscribe.php?status=success';
          } else {
            // Show error message
            this.disabled = false;
            this.textContent = originalButtonText;
            
            const formContainer = document.getElementById('paypal-form').closest('.payment-options');
            showError(result.message, formContainer);
          }
        })
        .catch(error => {
          console.error('Payment error:', error);
          this.disabled = false;
          this.textContent = originalButtonText;
          
          const formContainer = document.getElementById('paypal-form').closest('.payment-options');
          showError('An error occurred while processing your payment. Please try again.', formContainer);
        });
    });
  }
}

/**
 * Process the payment
 * @param {string} paymentMethod - The payment method ('credit_card' or 'paypal')
 * @returns {Promise} A promise that resolves with the payment result
 */
function processPayment(paymentMethod) {
  return fetch('api/payment.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'process_payment',
      payment_method: paymentMethod,
      plan: 'premium'
    })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  });
}
