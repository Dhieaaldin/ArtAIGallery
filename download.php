<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'gallery.php';
    header('Location: login.php');
    exit;
}

// Check if artwork ID is provided
if (!isset($_GET['artwork']) || !is_numeric($_GET['artwork'])) {
    header('Location: gallery.php');
    exit;
}

$artworkId = (int)$_GET['artwork'];
$quality = isset($_GET['quality']) && $_GET['quality'] === 'high' ? 'high' : 'low';

// Get user subscription status
$stmt = $pdo->prepare("SELECT subscription_status FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isPremium = ($user && $user['subscription_status'] === 'premium');

// If requesting high quality and not premium, redirect to subscription page
if ($quality === 'high' && !$isPremium) {
    header('Location: subscribe.php');
    exit;
}

// Get artwork information
$stmt = $pdo->prepare("SELECT * FROM artwork WHERE id = ?");
$stmt->execute([$artworkId]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artwork) {
    header('Location: gallery.php');
    exit;
}

// Determine the file path based on quality
$filePath = $quality === 'high' ? $artwork['high_res_url'] : $artwork['image_url'];

// Record the download
$stmt = $pdo->prepare("
    INSERT INTO downloads (user_id, artwork_id, quality, download_date) 
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$_SESSION['user_id'], $artworkId, $quality]);

// Set headers for download
$filename = preg_replace('/[^a-z0-9\-_\.]/i', '_', $artwork['title']) . '.jpg';
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));

// Output the file
readfile($filePath);
exit;
