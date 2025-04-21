  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-column">
          <h3>AI Artistry</h3>
          <ul class="footer-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <?php if (isLoggedIn()): ?>
              <li><a href="dashboard.php">Dashboard</a></li>
            <?php else: ?>
              <li><a href="login.php">Log In</a></li>
              <li><a href="register.php">Sign Up</a></li>
            <?php endif; ?>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Subscription</h3>
          <ul class="footer-links">
            <li><a href="subscribe.php">Pricing</a></li>
            <li><a href="#">Features</a></li>
            <?php if (isLoggedIn()): ?>
              <li><a href="dashboard.php?tab=subscription">Manage Subscription</a></li>
            <?php endif; ?>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Support</h3>
          <ul class="footer-links">
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> AI Artistry. All rights reserved.</p>
      </div>
    </div>
  </footer>
  
  <script src="js/main.js"></script>
</body>
</html>
