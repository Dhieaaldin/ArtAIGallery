<?php
/**
 * API endpoint for user operations
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in for protected endpoints
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Process request based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET requests
    handleGetRequest();
} elseif ($method === 'POST') {
    // Handle POST requests
    handlePostRequest();
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

/**
 * Handle GET requests
 */
function handleGetRequest() {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'get_favorites':
            getFavorites();
            break;
        
        case 'get_downloads':
            getDownloads();
            break;
        
        case 'get_account':
            getAccount();
            break;
        
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest() {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        return;
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'update_account':
            updateAccount($input);
            break;
        
        case 'update_password':
            updatePassword($input);
            break;
        
        case 'toggle_favorite':
            toggleFavoriteArtwork($input);
            break;
        
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
}

/**
 * Get user favorites
 */
function getFavorites() {
    $userId = $_SESSION['user_id'];
    $result = getUserFavorites($userId);
    
    echo json_encode($result);
}

/**
 * Get user downloads
 */
function getDownloads() {
    $userId = $_SESSION['user_id'];
    $result = getUserDownloads($userId);
    
    echo json_encode($result);
}

/**
 * Get user account information
 */
function getAccount() {
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    if ($user) {
        // Remove sensitive data
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }
}

/**
 * Update user account information
 * 
 * @param array $input - Request data
 */
function updateAccount($input) {
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($input['name']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name and email are required'
        ]);
        return;
    }
    
    $name = trim($input['name']);
    $email = trim($input['email']);
    
    // Basic validation
    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name is required'
        ]);
        return;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid email is required'
        ]);
        return;
    }
    
    // Update the account
    $result = updateUserAccount($userId, [
        'name' => $name,
        'email' => $email
    ]);
    
    echo json_encode($result);
}

/**
 * Update user password
 * 
 * @param array $input - Request data
 */
function updatePassword($input) {
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($input['current_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Current password and new password are required'
        ]);
        return;
    }
    
    $currentPassword = $input['current_password'];
    $newPassword = $input['new_password'];
    
    // Basic validation
    if (empty($currentPassword)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Current password is required'
        ]);
        return;
    }
    
    if (empty($newPassword)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'New password is required'
        ]);
        return;
    }
    
    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'New password must be at least 8 characters long'
        ]);
        return;
    }
    
    // Update the password
    $result = updateUserPassword($userId, $currentPassword, $newPassword);
    
    echo json_encode($result);
}

/**
 * Toggle favorite status for an artwork
 * 
 * @param array $input - Request data
 */
function toggleFavoriteArtwork($input) {
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($input['artwork_id']) || !is_numeric($input['artwork_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid artwork ID is required'
        ]);
        return;
    }
    
    $artworkId = (int)$input['artwork_id'];
    
    // Toggle favorite status
    $result = toggleFavorite($userId, $artworkId);
    
    echo json_encode($result);
}
