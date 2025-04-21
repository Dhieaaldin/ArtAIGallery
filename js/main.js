/**
 * Main JavaScript file for AI Artistry
 */

document.addEventListener('DOMContentLoaded', function() {
  initMobileMenu();
});

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const navMenu = document.querySelector('.nav-menu');
  
  if (mobileMenuToggle && navMenu) {
    mobileMenuToggle.addEventListener('click', function() {
      navMenu.classList.toggle('active');
      
      // Toggle aria-expanded attribute for accessibility
      const isExpanded = navMenu.classList.contains('active');
      mobileMenuToggle.setAttribute('aria-expanded', isExpanded);
    });
    
    // Close the menu when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.nav-menu') && !event.target.closest('.mobile-menu-toggle')) {
        navMenu.classList.remove('active');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }
}

/**
 * Display an error message
 * @param {string} message - The error message to display
 * @param {HTMLElement} container - The container to append the error to
 */
function showError(message, container) {
  const errorElement = document.createElement('div');
  errorElement.className = 'alert alert-error';
  errorElement.textContent = message;
  
  // Clear any existing errors
  const existingErrors = container.querySelectorAll('.alert.alert-error');
  existingErrors.forEach(error => error.remove());
  
  // Add the new error
  container.prepend(errorElement);
  
  // Scroll to the error
  errorElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Display a success message
 * @param {string} message - The success message to display
 * @param {HTMLElement} container - The container to append the success message to
 */
function showSuccess(message, container) {
  const successElement = document.createElement('div');
  successElement.className = 'alert alert-success';
  successElement.textContent = message;
  
  // Clear any existing messages
  const existingMessages = container.querySelectorAll('.alert');
  existingMessages.forEach(msg => msg.remove());
  
  // Add the new message
  container.prepend(successElement);
  
  // Scroll to the message
  successElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Format a date string in a readable format
 * @param {string} dateString - The date string to format
 * @returns {string} The formatted date
 */
function formatDate(dateString) {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
}

/**
 * Create a loading spinner element
 * @returns {HTMLElement} The loading spinner element
 */
function createLoadingSpinner() {
  const spinner = document.createElement('div');
  spinner.className = 'loading';
  spinner.textContent = 'Loading...';
  return spinner;
}

/**
 * Create an artwork card element
 * @param {Object} artwork - The artwork data
 * @returns {HTMLElement} The artwork card element
 */
function createArtworkCard(artwork) {
  const card = document.createElement('div');
  card.className = 'artwork-card';
  
  const imageContainer = document.createElement('div');
  imageContainer.className = 'artwork-image';
  
  const image = document.createElement('img');
  image.src = artwork.image_url;
  image.alt = artwork.title;
  imageContainer.appendChild(image);
  
  const info = document.createElement('div');
  info.className = 'artwork-info';
  
  const title = document.createElement('h3');
  title.textContent = artwork.title;
  
  const style = document.createElement('p');
  style.className = 'artwork-style';
  style.textContent = artwork.style;
  
  const category = document.createElement('p');
  category.className = 'artwork-category';
  category.textContent = artwork.category;
  
  const viewLink = document.createElement('a');
  viewLink.className = 'btn btn-outline';
  viewLink.textContent = 'View Details';
  viewLink.href = `gallery.php?artwork=${artwork.id}`;
  
  info.appendChild(title);
  info.appendChild(style);
  info.appendChild(category);
  info.appendChild(viewLink);
  
  card.appendChild(imageContainer);
  card.appendChild(info);
  
  return card;
}

/**
 * Create pagination elements
 * @param {number} currentPage - The current page number
 * @param {number} totalPages - The total number of pages
 * @param {Function} onPageChange - Callback function when page changes
 * @returns {HTMLElement} The pagination element
 */
function createPagination(currentPage, totalPages, onPageChange) {
  const paginationContainer = document.createElement('div');
  paginationContainer.className = 'pagination';
  
  // Previous button
  if (currentPage > 1) {
    const prevButton = document.createElement('a');
    prevButton.className = 'pagination-item';
    prevButton.textContent = '←';
    prevButton.href = '#';
    prevButton.addEventListener('click', (e) => {
      e.preventDefault();
      onPageChange(currentPage - 1);
    });
    paginationContainer.appendChild(prevButton);
  }
  
  // Page numbers
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(totalPages, startPage + 4);
  
  for (let i = startPage; i <= endPage; i++) {
    const pageItem = document.createElement('a');
    pageItem.className = 'pagination-item';
    if (i === currentPage) {
      pageItem.classList.add('active');
    }
    pageItem.textContent = i;
    pageItem.href = '#';
    pageItem.addEventListener('click', (e) => {
      e.preventDefault();
      if (i !== currentPage) {
        onPageChange(i);
      }
    });
    paginationContainer.appendChild(pageItem);
  }
  
  // Next button
  if (currentPage < totalPages) {
    const nextButton = document.createElement('a');
    nextButton.className = 'pagination-item';
    nextButton.textContent = '→';
    nextButton.href = '#';
    nextButton.addEventListener('click', (e) => {
      e.preventDefault();
      onPageChange(currentPage + 1);
    });
    paginationContainer.appendChild(nextButton);
  }
  
  return paginationContainer;
}
