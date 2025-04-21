<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/subscription.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'subscribe.php';
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if already subscribed
$isSubscribed = ($user['subscription_status'] === 'premium');

// Handle return from payment processor
$paymentSuccess = false;
$paymentError = '';

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $paymentSuccess = true;
} elseif (isset($_GET['status']) && $_GET['status'] === 'error') {
    $paymentError = 'Your payment could not be processed. Please try again.';
}

$pageTitle = "Subscribe to Premium - AI Artistry";
?>

<?php include 'includes/header.php'; ?>

<main class="subscribe-page">
  <div class="container">
    <h1>Subscribe to Premium</h1>
    
    <?php if ($paymentSuccess): ?>
      <div class="payment-success">
        <div class="alert alert-success">
          <h2>Thank You for Subscribing!</h2>
          <p>Your premium subscription has been activated successfully.</p>
          <div class="success-actions">
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="gallery.php" class="btn btn-outline">Browse Artwork</a>
          </div>
        </div>
      </div>
    <?php elseif ($isSubscribed): ?>
      <div class="already-subscribed">
        <div class="alert alert-info">
          <h2>You're Already a Premium Member!</h2>
          <p>You already have an active premium subscription. You can manage your subscription from your dashboard.</p>
          <div class="subscription-actions">
            <a href="dashboard.php?tab=subscription" class="btn btn-primary">Manage Subscription</a>
            <a href="gallery.php" class="btn btn-outline">Browse Artwork</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <?php if ($paymentError): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($paymentError); ?>
        </div>
      <?php endif; ?>
      
      <div class="subscription-container">
        <div class="subscription-content">
          <div class="subscription-details">
            <h2>Premium Membership</h2>
            <div class="subscription-price">
              <span class="price">$9.99</span>
              <span class="period">per month</span>
            </div>
            <ul class="subscription-features">
              <li>High-resolution artwork downloads</li>
              <li>Early access to new artwork</li>
              <li>Personalized recommendations</li>
              <li>No advertisements</li>
              <li>Cancel anytime</li>
            </ul>
          </div>
          
          <div class="payment-options">
            <h3>Choose Payment Method</h3>
            
            <div class="payment-tabs">
              <button class="payment-tab active" data-payment="credit-card">Credit Card</button>
              <button class="payment-tab" data-payment="paypal">PayPal</button>
            </div>
            
            <div class="payment-forms">
              <form id="credit-card-form" class="payment-form active">
                <div class="form-group">
                  <label for="card-name">Name on Card</label>
                  <input type="text" id="card-name" name="card-name" required>
                </div>
                
                <div class="form-group">
                  <label for="card-number">Card Number</label>
                  <input type="text" id="card-number" name="card-number" placeholder="1234 5678 9012 3456" required>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label for="card-expiry">Expiration Date</label>
                    <input type="text" id="card-expiry" name="card-expiry" placeholder="MM/YY" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="card-cvc">CVC</label>
                    <input type="text" id="card-cvc" name="card-cvc" placeholder="123" required>
                  </div>
                </div>
                
                <div class="form-actions">
                  <button type="button" id="process-card-payment" class="btn btn-primary">Subscribe Now</button>
                </div>
              </form>
              
              <div id="paypal-form" class="payment-form">
                <p>You will be redirected to PayPal to complete your purchase.</p>
                <button type="button" id="process-paypal-payment" class="btn btn-primary">Pay with PayPal</button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="subscription-sidebar">
          <div class="order-summary">
            <h3>Order Summary</h3>
            <div class="order-line">
              <span>Premium Membership (Monthly)</span>
              <span>$9.99</span>
            </div>
            <div class="order-total">
              <span>Total</span>
              <span>$9.99</span>
            </div>
          </div>
          
          <div class="subscription-info">
            <p>By subscribing, you agree to our <a href="#" target="_blank">Terms of Service</a> and authorize us to charge your payment method on a recurring monthly basis. You can cancel anytime.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<script src="js/payment.js"></script>

<?php include 'includes/footer.php'; ?>
