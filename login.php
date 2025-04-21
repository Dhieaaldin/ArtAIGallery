<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate form data
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    // Attempt login if no validation errors
    if (empty($errors)) {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Redirect based on the intended URL or default to dashboard
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $errors['login'] = $result['message'];
        }
    }
}

$pageTitle = "Login - AI Artistry";
?>

<?php include 'includes/header.php'; ?>

<main class="auth-page">
  <div class="container">
    <div class="auth-form-container">
      <h1>Log In to Your Account</h1>
      
      <?php if (!empty($errors['login'])): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($errors['login']); ?>
        </div>
      <?php endif; ?>
      
      <form action="login.php" method="post" class="auth-form">
        <div class="form-group <?php echo !empty($errors['email']) ? 'has-error' : ''; ?>">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
          <?php if (!empty($errors['email'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-group <?php echo !empty($errors['password']) ? 'has-error' : ''; ?>">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <?php if (!empty($errors['password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Log In</button>
        </div>
      </form>
      
      <div class="auth-links">
        <p>Don't have an account? <a href="register.php">Register</a></p>
      </div>
    </div>
  </div>
</main>

<script src="js/auth.js"></script>

<?php include 'includes/footer.php'; ?>
