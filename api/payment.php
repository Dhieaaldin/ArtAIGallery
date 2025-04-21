<?php
/**
 * API endpoint for payment and subscription operations
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/subscription.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in for all payment endpoints
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

if ($method === 'POST') {
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
        case 'process_payment':
            processSubscriptionPayment($input);
            break;
        
        case 'cancel_subscription':
            cancelUserSubscription($input);
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
 * Process a subscription payment
 * 
 * @param array $input - Request data
 */
function processSubscriptionPayment($input) {
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($input['payment_method']) || !isset($input['plan'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Payment method and plan are required'
        ]);
        return;
    }
    
    $paymentMethod = $input['payment_method'];
    $plan = $input['plan'];
    
    // Basic validation
    if (!in_array($paymentMethod, ['credit_card', 'paypal'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid payment method'
        ]);
        return;
    }
    
    if ($plan !== PLAN_PREMIUM) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid subscription plan'
        ]);
        return;
    }
    
    // Check if user already has an active subscription
    $activeSubscription = getActiveSubscription($userId);
    if ($activeSubscription) {
        echo json_encode([
            'success' => false,
            'message' => 'You already have an active subscription'
        ]);
        return;
    }
    
    // Process the payment
    $result = processPayment($userId, $paymentMethod, $plan);
    
    echo json_encode($result);
}

/**
 * Cancel a user's subscription
 * 
 * @param array $input - Request data
 */
function cancelUserSubscription($input) {
    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($input['subscription_id']) || !is_numeric($input['subscription_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Valid subscription ID is required'
        ]);
        return;
    }
    
    $subscriptionId = (int)$input['subscription_id'];
    
    // Cancel the subscription
    $result = cancelSubscription($subscriptionId, $userId);
    
    echo json_encode($result);
}
