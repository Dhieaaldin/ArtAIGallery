<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Get featured artwork
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.image_url, a.style, a.category, a.created_at 
    FROM artwork a 
    WHERE a.featured = TRUE 
    ORDER BY a.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$featuredArtwork = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "AI Artistry - Discover AI-Generated Art";
?>

<?php include 'includes/header.php'; ?>

<main>
  <section class="hero">
    <div class="hero-content">
      <h1>Discover AI-Generated Art</h1>
      <p>Explore our collection of unique and stunning AI-generated artwork</p>
      <a href="gallery.php" class="btn btn-primary">Browse Gallery</a>
      <?php if (!isLoggedIn()): ?>
        <a href="register.php" class="btn btn-secondary">Join Now</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="featured">
    <div class="container">
      <h2>Featured Artwork</h2>
      <div class="artwork-grid">
        <?php if (count($featuredArtwork) > 0): ?>
          <?php foreach ($featuredArtwork as $art): ?>
            <div class="artwork-card">
              <div class="artwork-image">
                <img src="<?php echo htmlspecialchars($art['image_url']); ?>" alt="<?php echo htmlspecialchars($art['title']); ?>">
              </div>
              <div class="artwork-info">
                <h3><?php echo htmlspecialchars($art['title']); ?></h3>
                <p class="artwork-style"><?php echo htmlspecialchars($art['style']); ?></p>
                <p class="artwork-category"><?php echo htmlspecialchars($art['category']); ?></p>
                <a href="gallery.php?artwork=<?php echo $art['id']; ?>" class="btn btn-outline">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-artwork">
            <p>No featured artwork available at the moment.</p>
          </div>
        <?php endif; ?>
      </div>
      <div class="view-all">
        <a href="gallery.php" class="btn btn-primary">View All Artwork</a>
      </div>
    </div>
  </section>

  <section class="subscription-plans">
    <div class="container">
      <h2>Subscription Plans</h2>
      <div class="plans-grid">
        <div class="plan-card">
          <h3>Free</h3>
          <p class="price">$0<span>/month</span></p>
          <ul class="features">
            <li>Browse all artwork</li>
            <li>Save favorites</li>
            <li>Low-resolution downloads</li>
          </ul>
          <a href="register.php" class="btn btn-outline">Sign Up</a>
        </div>
        <div class="plan-card premium">
          <h3>Premium</h3>
          <p class="price">$9.99<span>/month</span></p>
          <ul class="features">
            <li>All Free features</li>
            <li>High-resolution downloads</li>
            <li>Early access to new artwork</li>
            <li>Personalized recommendations</li>
          </ul>
          <a href="subscribe.php" class="btn btn-primary">Go Premium</a>
        </div>
      </div>
    </div>
  </section>

  <section class="about">
    <div class="container">
      <h2>About AI Artistry</h2>
      <div class="about-content">
        <div class="about-text">
          <p>AI Artistry is a platform dedicated to showcasing the beauty and creativity of AI-generated art. Our collection features unique pieces created by advanced artificial intelligence algorithms, trained on diverse artistic styles and techniques.</p>
          <p>Explore our gallery to discover stunning artworks, and subscribe to our premium plan to access high-resolution downloads and exclusive features.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
