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
$formData = [
    'name' => '',
    'email' => '',
];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate form data
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    }

    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'Email address is already registered';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Register user if no validation errors
    if (empty($errors)) {
        $result = registerUser($formData['name'], $formData['email'], $password);
        
        if ($result['success']) {
            // Automatically log in the user
            loginUser($formData['email'], $password);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $errors['register'] = $result['message'];
        }
    }
}

$pageTitle = "Register - AI Artistry";
?>

<?php include 'includes/header.php'; ?>

<main class="auth-page">
  <div class="container">
    <div class="auth-form-container">
      <h1>Create Your Account</h1>
      
      <?php if (!empty($errors['register'])): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($errors['register']); ?>
        </div>
      <?php endif; ?>
      
      <form action="register.php" method="post" class="auth-form">
        <div class="form-group <?php echo !empty($errors['name']) ? 'has-error' : ''; ?>">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
          <?php if (!empty($errors['name'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-group <?php echo !empty($errors['email']) ? 'has-error' : ''; ?>">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
          <?php if (!empty($errors['email'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-group <?php echo !empty($errors['password']) ? 'has-error' : ''; ?>">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <div class="password-requirements">
            Password must be at least 8 characters long
          </div>
          <?php if (!empty($errors['password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-group <?php echo !empty($errors['confirm_password']) ? 'has-error' : ''; ?>">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
          <?php if (!empty($errors['confirm_password'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <div class="terms-acceptance">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a></label>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
      </form>
      
      <div class="auth-links">
        <p>Already have an account? <a href="login.php">Log In</a></p>
      </div>
    </div>
  </div>
</main>

<script src="js/auth.js"></script>

<?php include 'includes/footer.php'; ?>
