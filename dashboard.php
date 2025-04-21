<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/subscription.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'dashboard.php';
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found in database
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get subscription status
$subscriptionStatus = $user['subscription_status'] ?? 'free';
$isPremium = ($subscriptionStatus === 'premium');

// Get current tab
$currentTab = $_GET['tab'] ?? 'favorites';
$validTabs = ['favorites', 'downloads', 'account', 'subscription'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'favorites';
}

$pageTitle = "Dashboard - AI Artistry";
?>

<?php include 'includes/header.php'; ?>

<main class="dashboard-page">
  <div class="container">
    <div class="dashboard-header">
      <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
      <div class="subscription-badge <?php echo $isPremium ? 'premium' : 'free'; ?>">
        <?php echo $isPremium ? 'Premium Member' : 'Free User'; ?>
      </div>
    </div>

    <div class="dashboard-tabs">
      <nav class="tab-navigation">
        <a href="?tab=favorites" class="tab-link <?php echo $currentTab === 'favorites' ? 'active' : ''; ?>">Favorites</a>
        <a href="?tab=downloads" class="tab-link <?php echo $currentTab === 'downloads' ? 'active' : ''; ?>">Downloads</a>
        <a href="?tab=subscription" class="tab-link <?php echo $currentTab === 'subscription' ? 'active' : ''; ?>">Subscription</a>
        <a href="?tab=account" class="tab-link <?php echo $currentTab === 'account' ? 'active' : ''; ?>">Account Settings</a>
      </nav>

      <div class="tab-content">
        <?php if ($currentTab === 'favorites'): ?>
          <div id="favorites-tab" class="tab-pane active">
            <h2>Your Favorite Artwork</h2>
            <div id="favorites-gallery" class="user-gallery">
              <div class="loading">Loading your favorites...</div>
            </div>
          </div>
        
        <?php elseif ($currentTab === 'downloads'): ?>
          <div id="downloads-tab" class="tab-pane active">
            <h2>Download History</h2>
            <div id="downloads-history" class="download-history">
              <div class="loading">Loading your download history...</div>
            </div>
          </div>
        
        <?php elseif ($currentTab === 'subscription'): ?>
          <div id="subscription-tab" class="tab-pane active">
            <h2>Subscription Management</h2>
            
            <div class="current-plan">
              <h3>Current Plan</h3>
              <div class="plan-details <?php echo $isPremium ? 'premium' : 'free'; ?>">
                <div class="plan-name"><?php echo $isPremium ? 'Premium' : 'Free'; ?></div>
                <div class="plan-price">
                  <?php if ($isPremium): ?>
                    $9.99<span>/month</span>
                  <?php else: ?>
                    $0<span>/month</span>
                  <?php endif; ?>
                </div>
              </div>
              
              <?php if ($isPremium): ?>
                <?php
                // Get subscription details
                $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$_SESSION['user_id']]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <?php if ($subscription): ?>
                  <div class="subscription-details">
                    <p><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($subscription['created_at'])); ?></p>
                    <?php if (!empty($subscription['next_billing_date'])): ?>
                      <p><strong>Next Billing Date:</strong> <?php echo date('F j, Y', strtotime($subscription['next_billing_date'])); ?></p>
                    <?php endif; ?>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($subscription['payment_method']); ?></p>
                  </div>
                  
                  <div class="subscription-actions">
                    <a href="#" id="cancel-subscription" class="btn btn-outline" data-subscription-id="<?php echo $subscription['id']; ?>">Cancel Subscription</a>
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <div class="upgrade-prompt">
                  <h3>Upgrade to Premium</h3>
                  <p>Enjoy high-resolution downloads, early access to new artwork, and more!</p>
                  <a href="subscribe.php" class="btn btn-primary">Upgrade Now</a>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="subscription-comparison">
              <h3>Plan Comparison</h3>
              <div class="plans-table">
                <table>
                  <thead>
                    <tr>
                      <th>Feature</th>
                      <th>Free</th>
                      <th>Premium</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Browse all artwork</td>
                      <td><span class="check">✓</span></td>
                      <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                      <td>Save favorites</td>
                      <td><span class="check">✓</span></td>
                      <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                      <td>Download artwork</td>
                      <td>Low-res only</td>
                      <td><span class="check">✓</span> High-res</td>
                    </tr>
                    <tr>
                      <td>Early access to new artwork</td>
                      <td><span class="cross">✕</span></td>
                      <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                      <td>Personalized recommendations</td>
                      <td><span class="cross">✕</span></td>
                      <td><span class="check">✓</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        
        <?php elseif ($currentTab === 'account'): ?>
          <div id="account-tab" class="tab-pane active">
            <h2>Account Settings</h2>
            
            <form id="account-form" class="account-form">
              <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
              </div>
              
              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
              
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
            
            <div class="account-separator"></div>
            
            <h3>Change Password</h3>
            <form id="password-form" class="password-form">
              <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
              </div>
              
              <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="password-requirements">
                  Password must be at least 8 characters long
                </div>
              </div>
              
              <div class="form-group">
                <label for="confirm_new_password">Confirm New Password</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>
              </div>
              
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Password</button>
              </div>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<script src="js/dashboard.js"></script>

<?php include 'includes/footer.php'; ?>
