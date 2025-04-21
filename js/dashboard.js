/**
 * Dashboard page functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  initAccountForm();
  initPasswordForm();
  initCancelSubscription();
  loadFavorites();
  loadDownloads();
});

/**
 * Initialize account form functionality
 */
function initAccountForm() {
  const accountForm = document.getElementById('account-form');
  
  if (accountForm) {
    accountForm.addEventListener('submit', function(event) {
      event.preventDefault();
      
      const nameInput = this.querySelector('#name');
      const emailInput = this.querySelector('#email');
      
      if (!nameInput || !emailInput) return;
      
      const name = nameInput.value.trim();
      const email = emailInput.value.trim();
      
      // Validate inputs
      if (name === '') {
        showInputError(nameInput, 'Name is required');
        return;
      }
      
      if (email === '' || !isValidEmail(email)) {
        showInputError(emailInput, 'Valid email is required');
        return;
      }
      
      // Show loading state
      const submitButton = accountForm.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;
      submitButton.disabled = true;
      submitButton.textContent = 'Saving...';
      
      // Send update request
      fetch('api/user.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update_account',
          name: name,
          email: email
        })
      })
      .then(response => response.json())
      .then(data => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        
        if (data.success) {
          showSuccess(data.message, accountForm);
        } else {
          showError(data.message, accountForm);
        }
      })
      .catch(error => {
        console.error('Error updating account:', error);
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        showError('An error occurred. Please try again.', accountForm);
      });
    });
  }
}

/**
 * Initialize password form functionality
 */
function initPasswordForm() {
  const passwordForm = document.getElementById('password-form');
  
  if (passwordForm) {
    passwordForm.addEventListener('submit', function(event) {
      event.preventDefault();
      
      const currentPasswordInput = this.querySelector('#current_password');
      const newPasswordInput = this.querySelector('#new_password');
      const confirmNewPasswordInput = this.querySelector('#confirm_new_password');
      
      if (!currentPasswordInput || !newPasswordInput || !confirmNewPasswordInput) return;
      
      const currentPassword = currentPasswordInput.value;
      const newPassword = newPasswordInput.value;
      const confirmNewPassword = confirmNewPasswordInput.value;
      
      // Validate inputs
      if (currentPassword === '') {
        showInputError(currentPasswordInput, 'Current password is required');
        return;
      }
      
      if (newPassword.length < 8) {
        showInputError(newPasswordInput, 'New password must be at least 8 characters long');
        return;
      }
      
      if (newPassword !== confirmNewPassword) {
        showInputError(confirmNewPasswordInput, 'Passwords do not match');
        return;
      }
      
      // Show loading state
      const submitButton = passwordForm.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;
      submitButton.disabled = true;
      submitButton.textContent = 'Updating...';
      
      // Send update request
      fetch('api/user.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'update_password',
          current_password: currentPassword,
          new_password: newPassword
        })
      })
      .then(response => response.json())
      .then(data => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        
        if (data.success) {
          // Clear form on success
          passwordForm.reset();
          showSuccess(data.message, passwordForm);
        } else {
          showError(data.message, passwordForm);
        }
      })
      .catch(error => {
        console.error('Error updating password:', error);
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        showError('An error occurred. Please try again.', passwordForm);
      });
    });
  }
}

/**
 * Initialize subscription cancellation functionality
 */
function initCancelSubscription() {
  const cancelSubscriptionBtn = document.getElementById('cancel-subscription');
  
  if (cancelSubscriptionBtn) {
    cancelSubscriptionBtn.addEventListener('click', function(event) {
      event.preventDefault();
      
      const subscriptionId = this.getAttribute('data-subscription-id');
      
      if (!subscriptionId) return;
      
      // Confirm cancellation
      if (!confirm('Are you sure you want to cancel your subscription? You will lose access to premium features at the end of your current billing period.')) {
        return;
      }
      
      // Show loading state
      const originalButtonText = this.textContent;
      this.disabled = true;
      this.textContent = 'Cancelling...';
      
      // Send cancellation request
      fetch('api/payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'cancel_subscription',
          subscription_id: subscriptionId
        })
      })
      .then(response => response.json())
      .then(data => {
        // Reset button state
        this.disabled = false;
        this.textContent = originalButtonText;
        
        if (data.success) {
          alert(data.message);
          // Reload page to reflect changes
          window.location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error cancelling subscription:', error);
        this.disabled = false;
        this.textContent = originalButtonText;
        alert('An error occurred. Please try again.');
      });
    });
  }
}

/**
 * Load user's favorite artwork
 */
function loadFavorites() {
  const favoritesGallery = document.getElementById('favorites-gallery');
  
  if (favoritesGallery && favoritesGallery.closest('#favorites-tab.active')) {
    // Show loading state
    favoritesGallery.innerHTML = '';
    favoritesGallery.appendChild(createLoadingSpinner());
    
    // Fetch favorites from API
    fetch('api/user.php?action=get_favorites')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        favoritesGallery.innerHTML = '';
        
        if (!data.success) {
          favoritesGallery.innerHTML = `
            <div class="alert alert-error">
              ${data.message}
            </div>
          `;
          return;
        }
        
        if (data.favorites.length === 0) {
          favoritesGallery.innerHTML = `
            <div class="no-artwork">
              <p>You haven't saved any favorites yet.</p>
              <p><a href="gallery.php" class="btn btn-primary">Browse Gallery</a></p>
            </div>
          `;
          return;
        }
        
        // Create artwork grid
        const artworkGrid = document.createElement('div');
        artworkGrid.className = 'artwork-grid';
        
        // Add artwork cards
        data.favorites.forEach(artwork => {
          const card = createArtworkCard(artwork);
          
          // Add remove from favorites button
          const artworkInfo = card.querySelector('.artwork-info');
          const removeBtn = document.createElement('button');
          removeBtn.className = 'btn-favorite favorited';
          removeBtn.textContent = 'Remove from Favorites';
          removeBtn.setAttribute('data-artwork-id', artwork.id);
          
          removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            removeFavorite(artwork.id, card);
          });
          
          artworkInfo.appendChild(removeBtn);
          artworkGrid.appendChild(card);
        });
        
        favoritesGallery.appendChild(artworkGrid);
      })
      .catch(error => {
        console.error('Error fetching favorites:', error);
        favoritesGallery.innerHTML = `
          <div class="alert alert-error">
            Error loading favorites. Please try again later.
          </div>
        `;
      });
  }
}

/**
 * Remove an artwork from favorites
 * @param {number} artworkId - The ID of the artwork to remove
 * @param {HTMLElement} cardElement - The card element to remove from the DOM
 */
function removeFavorite(artworkId, cardElement) {
  fetch('api/user.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'toggle_favorite',
      artwork_id: artworkId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Remove card with animation
      cardElement.style.opacity = '0';
      setTimeout(() => {
        cardElement.remove();
        
        // Check if any cards left
        const remainingCards = document.querySelectorAll('#favorites-gallery .artwork-card');
        if (remainingCards.length === 0) {
          document.getElementById('favorites-gallery').innerHTML = `
            <div class="no-artwork">
              <p>You haven't saved any favorites yet.</p>
              <p><a href="gallery.php" class="btn btn-primary">Browse Gallery</a></p>
            </div>
          `;
        }
      }, 300);
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error removing favorite:', error);
    alert('An error occurred. Please try again.');
  });
}

/**
 * Load user's download history
 */
function loadDownloads() {
  const downloadsHistory = document.getElementById('downloads-history');
  
  if (downloadsHistory && downloadsHistory.closest('#downloads-tab.active')) {
    // Show loading state
    downloadsHistory.innerHTML = '';
    downloadsHistory.appendChild(createLoadingSpinner());
    
    // Fetch download history from API
    fetch('api/user.php?action=get_downloads')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        downloadsHistory.innerHTML = '';
        
        if (!data.success) {
          downloadsHistory.innerHTML = `
            <div class="alert alert-error">
              ${data.message}
            </div>
          `;
          return;
        }
        
        if (data.downloads.length === 0) {
          downloadsHistory.innerHTML = `
            <div class="no-artwork">
              <p>You haven't downloaded any artwork yet.</p>
              <p><a href="gallery.php" class="btn btn-primary">Browse Gallery</a></p>
            </div>
          `;
          return;
        }
        
        // Create download history list
        data.downloads.forEach(download => {
          const downloadItem = document.createElement('div');
          downloadItem.className = 'download-item';
          
          const downloadThumbnail = document.createElement('div');
          downloadThumbnail.className = 'download-thumbnail';
          
          const thumbnail = document.createElement('img');
          thumbnail.src = download.artwork.image_url;
          thumbnail.alt = download.artwork.title;
          downloadThumbnail.appendChild(thumbnail);
          
          const downloadInfo = document.createElement('div');
          downloadInfo.className = 'download-info';
          
          const title = document.createElement('h3');
          title.textContent = download.artwork.title;
          
          const metadata = document.createElement('div');
          metadata.className = 'download-metadata';
          
          const date = document.createElement('span');
          date.className = 'download-date';
          date.textContent = formatDate(download.download_date);
          
          const quality = document.createElement('span');
          quality.className = `download-quality ${download.quality}`;
          quality.textContent = download.quality === 'high' ? 'High Resolution' : 'Low Resolution';
          
          metadata.appendChild(date);
          metadata.appendChild(quality);
          
          const viewLink = document.createElement('a');
          viewLink.href = `gallery.php?artwork=${download.artwork.id}`;
          viewLink.className = 'btn btn-outline';
          viewLink.textContent = 'View Artwork';
          
          const downloadLink = document.createElement('a');
          downloadLink.href = `download.php?artwork=${download.artwork.id}&quality=${download.quality}`;
          downloadLink.className = 'btn btn-primary';
          downloadLink.textContent = 'Download Again';
          
          downloadInfo.appendChild(title);
          downloadInfo.appendChild(metadata);
          downloadInfo.appendChild(document.createElement('br'));
          downloadInfo.appendChild(viewLink);
          downloadInfo.appendChild(document.createTextNode(' '));
          downloadInfo.appendChild(downloadLink);
          
          downloadItem.appendChild(downloadThumbnail);
          downloadItem.appendChild(downloadInfo);
          
          downloadsHistory.appendChild(downloadItem);
        });
      })
      .catch(error => {
        console.error('Error fetching download history:', error);
        downloadsHistory.innerHTML = `
          <div class="alert alert-error">
            Error loading download history. Please try again later.
          </div>
        `;
      });
  }
}
