<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'AI Artistry'; ?></title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <header class="header">
    <div class="container header-container">
      <a href="index.php" class="logo">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 5L30 10L20 15L10 10L20 5Z" fill="#6D28D9"/>
          <path d="M30 10V20L20 25V15L30 10Z" fill="#8B5CF6"/>
          <path d="M10 10V20L20 25V15L10 10Z" fill="#A78BFA"/>
          <path d="M20 25L30 20L30 30L20 35L10 30V20L20 25Z" fill="#C4B5FD"/>
        </svg>
        AI Artistry
      </a>
      
      <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="nav-menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      
      <nav class="nav-menu" id="nav-menu">
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a>
        <a href="gallery.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'active' : ''; ?>">Gallery</a>
        <?php if (isLoggedIn()): ?>
          <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <?php endif; ?>
      </nav>
      
      <div class="user-menu">
        <?php if (isLoggedIn()): ?>
          <?php
          // Get subscription status
          $stmt = $pdo->prepare("SELECT subscription_status FROM users WHERE id = ?");
          $stmt->execute([$_SESSION['user_id']]);
          $user = $stmt->fetch();
          $isPremium = ($user && $user['subscription_status'] === 'premium');
          ?>
          
          <?php if (!$isPremium): ?>
            <a href="subscribe.php" class="btn btn-primary">Go Premium</a>
          <?php endif; ?>
          <a href="logout.php" class="btn btn-outline">Log Out</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline">Log In</a>
          <a href="register.php" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
