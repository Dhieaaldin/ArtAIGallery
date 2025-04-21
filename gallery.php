<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Get available categories and styles for filters
$stmt = $pdo->prepare("SELECT DISTINCT category FROM artwork ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT DISTINCT style FROM artwork ORDER BY style");
$stmt->execute();
$styles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle single artwork view
$singleArtwork = null;
if (isset($_GET['artwork'])) {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM user_favorites WHERE artwork_id = a.id AND user_id = ?) AS is_favorite 
        FROM artwork a 
        WHERE a.id = ?
    ");
    $userId = isLoggedIn() ? $_SESSION['user_id'] : 0;
    $stmt->execute([$userId, $_GET['artwork']]);
    $singleArtwork = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Page title
$pageTitle = $singleArtwork ? htmlspecialchars($singleArtwork['title']) . " - AI Artistry" : "Gallery - AI Artistry";
?>

<?php include 'includes/header.php'; ?>

<main class="gallery-page">
  <div class="container">
    <?php if ($singleArtwork): ?>
      <!-- Single artwork view -->
      <div class="artwork-detail">
        <div class="back-link">
          <a href="gallery.php">← Back to Gallery</a>
        </div>
        <div class="artwork-detail-content">
          <div class="artwork-detail-image">
            <img src="<?php echo htmlspecialchars($singleArtwork['image_url']); ?>" alt="<?php echo htmlspecialchars($singleArtwork['title']); ?>">
          </div>
          <div class="artwork-detail-info">
            <h1><?php echo htmlspecialchars($singleArtwork['title']); ?></h1>
            <p class="artwork-metadata">
              <span class="artwork-style"><?php echo htmlspecialchars($singleArtwork['style']); ?></span>
              <span class="artwork-category"><?php echo htmlspecialchars($singleArtwork['category']); ?></span>
              <span class="artwork-date">Created: <?php echo date('F j, Y', strtotime($singleArtwork['created_at'])); ?></span>
            </p>
            <p class="artwork-description"><?php echo htmlspecialchars($singleArtwork['description']); ?></p>
            
            <?php if (isLoggedIn()): ?>
              <div class="artwork-actions">
                <button 
                  class="btn-favorite <?php echo $singleArtwork['is_favorite'] ? 'favorited' : ''; ?>" 
                  data-artwork-id="<?php echo $singleArtwork['id']; ?>">
                  <?php echo $singleArtwork['is_favorite'] ? 'Remove from Favorites' : 'Add to Favorites'; ?>
                </button>
                
                <?php 
                $isPremium = false;
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("SELECT subscription_status FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    $isPremium = ($user && $user['subscription_status'] === 'premium');
                }
                ?>
                
                <?php if ($isPremium): ?>
                  <a href="download.php?artwork=<?php echo $singleArtwork['id']; ?>&quality=high" class="btn btn-primary">Download High-Res</a>
                <?php else: ?>
                  <a href="download.php?artwork=<?php echo $singleArtwork['id']; ?>&quality=low" class="btn btn-outline">Download Low-Res</a>
                  <a href="subscribe.php" class="btn btn-secondary">Go Premium for High-Res</a>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="artwork-actions">
                <a href="login.php" class="btn btn-outline">Log in to Download</a>
                <a href="subscribe.php" class="btn btn-primary">Subscribe for High-Res Access</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Gallery view -->
      <header class="gallery-header">
        <h1>Browse AI-Generated Artwork</h1>
        <div class="gallery-filters">
          <div class="filter-group">
            <label for="category-filter">Category:</label>
            <select id="category-filter">
              <option value="">All Categories</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="style-filter">Style:</label>
            <select id="style-filter">
              <option value="">All Styles</option>
              <?php foreach ($styles as $style): ?>
                <option value="<?php echo htmlspecialchars($style); ?>"><?php echo htmlspecialchars($style); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="sort-filter">Sort By:</label>
            <select id="sort-filter">
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
              <option value="name_asc">Name (A-Z)</option>
              <option value="name_desc">Name (Z-A)</option>
            </select>
          </div>
          
          <button id="apply-filters" class="btn btn-primary">Apply Filters</button>
          <button id="reset-filters" class="btn btn-outline">Reset</button>
        </div>
      </header>
      
      <div id="gallery-results">
        <div class="loading">Loading artworks...</div>
      </div>
      
      <div class="pagination-container">
        <div id="pagination"></div>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php if (!$singleArtwork): ?>
  <script src="js/gallery.js"></script>
<?php else: ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const favoriteBtn = document.querySelector('.btn-favorite');
      if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
          const artworkId = this.getAttribute('data-artwork-id');
          
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
              this.classList.toggle('favorited');
              this.textContent = this.classList.contains('favorited') ? 'Remove from Favorites' : 'Add to Favorites';
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
          });
        });
      }
    });
  </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
