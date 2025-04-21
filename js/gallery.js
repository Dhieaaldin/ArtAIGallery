/**
 * Gallery page functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  const galleryResults = document.getElementById('gallery-results');
  const paginationContainer = document.getElementById('pagination');
  const categoryFilter = document.getElementById('category-filter');
  const styleFilter = document.getElementById('style-filter');
  const sortFilter = document.getElementById('sort-filter');
  const applyFiltersBtn = document.getElementById('apply-filters');
  const resetFiltersBtn = document.getElementById('reset-filters');
  
  // Initialize state
  let currentPage = 1;
  let filters = {
    category: '',
    style: '',
    sort: 'newest'
  };
  
  // Load initial artwork
  loadArtwork();
  
  // Set up event listeners
  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener('click', function() {
      filters.category = categoryFilter.value;
      filters.style = styleFilter.value;
      filters.sort = sortFilter.value;
      currentPage = 1;
      loadArtwork();
    });
  }
  
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', function() {
      categoryFilter.value = '';
      styleFilter.value = '';
      sortFilter.value = 'newest';
      filters = {
        category: '',
        style: '',
        sort: 'newest'
      };
      currentPage = 1;
      loadArtwork();
    });
  }
  
  /**
   * Load artwork based on current filters and pagination
   */
  function loadArtwork() {
    if (!galleryResults) return;
    
    // Show loading state
    galleryResults.innerHTML = '';
    galleryResults.appendChild(createLoadingSpinner());
    
    // Prepare query parameters
    const params = new URLSearchParams({
      page: currentPage,
      per_page: 12,
      category: filters.category,
      style: filters.style,
      sort: filters.sort
    });
    
    // Fetch artwork from API
    fetch(`api/artwork.php?${params.toString()}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        displayArtwork(data);
      })
      .catch(error => {
        console.error('Error fetching artwork:', error);
        galleryResults.innerHTML = `
          <div class="alert alert-error">
            Error loading artwork. Please try again later.
          </div>
        `;
      });
  }
  
  /**
   * Display artwork and pagination
   * @param {Object} data - The artwork data from the API
   */
  function displayArtwork(data) {
    if (!galleryResults) return;
    
    galleryResults.innerHTML = '';
    
    if (data.artwork.length === 0) {
      galleryResults.innerHTML = `
        <div class="no-artwork">
          <p>No artwork found matching your criteria.</p>
        </div>
      `;
      paginationContainer.innerHTML = '';
      return;
    }
    
    // Create artwork grid
    const artworkGrid = document.createElement('div');
    artworkGrid.className = 'artwork-grid';
    
    // Add artwork cards
    data.artwork.forEach(artwork => {
      const card = createArtworkCard(artwork);
      artworkGrid.appendChild(card);
    });
    
    galleryResults.appendChild(artworkGrid);
    
    // Create pagination
    if (paginationContainer) {
      paginationContainer.innerHTML = '';
      
      if (data.total_pages > 1) {
        const pagination = createPagination(
          data.current_page,
          data.total_pages,
          (page) => {
            currentPage = page;
            loadArtwork();
            // Scroll to top of results
            galleryResults.scrollIntoView({ behavior: 'smooth' });
          }
        );
        
        paginationContainer.appendChild(pagination);
      }
    }
  }
});
