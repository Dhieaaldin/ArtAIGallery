<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Process logout
logoutUser();

// Redirect to home
header('Location: index.php');
exit;
