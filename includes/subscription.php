<?php
/**
 * Subscription management functions
 */

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

/**
 * Process a new subscription
 * 
 * @param int $userId - The user ID
 * @param string $paymentMethod - The payment method (credit_card, paypal, etc.)
 * @param string $plan - The subscription plan
 * @param float $amount - The subscription amount
 * @return array - ['success' => bool, 'message' => string, 'subscription_id' => int|null]
 */
function createSubscription($userId, $paymentMethod, $plan, $amount) {
    global $pdo;
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // Update user subscription status
        $stmt = $pdo->prepare("UPDATE users SET subscription_status = ? WHERE id = ?");
        $stmt->execute([$plan, $userId]);
        
        // Calculate next billing date (30 days from now)
        $nextBillingDate = date('Y-m-d', strtotime('+30 days'));
        
        // Create subscription record
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (user_id, payment_method, amount, subscription_status, next_billing_date)
            VALUES (?, ?, ?, 'active', ?)
        ");
        $stmt->execute([$userId, $paymentMethod, $amount, $nextBillingDate]);
        $subscriptionId = $pdo->lastInsertId();
        
        // Commit the transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Subscription created successfully',
            'subscription_id' => $subscriptionId
        ];
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        error_log("Create subscription error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred while processing your subscription',
            'subscription_id' => null
        ];
    }
}

/**
 * Get a user's active subscription
 * 
 * @param int $userId - The user ID
 * @return array|null - Subscription data or null if no active subscription
 */
function getActiveSubscription($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM subscriptions
            WHERE user_id = ? AND subscription_status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get subscription error: " . $e->getMessage());
        return null;
    }
}

/**
 * Cancel a subscription
 * 
 * @param int $subscriptionId - The subscription ID
 * @param int $userId - The user ID (for verification)
 * @return array - ['success' => bool, 'message' => string]
 */
function cancelSubscription($subscriptionId, $userId) {
    global $pdo;
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // Verify the subscription belongs to the user
        $stmt = $pdo->prepare("
            SELECT id, subscription_status
            FROM subscriptions
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$subscriptionId, $userId]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'Subscription not found'
            ];
        }
        
        if ($subscription['subscription_status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Subscription is not active'
            ];
        }
        
        // Update subscription status
        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET subscription_status = 'cancelled', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subscriptionId]);
        
        // Update user subscription status to free when the current period ends
        // In a real system, this would be done by a cron job when the subscription actually expires
        // For this demo, we'll do it immediately
        $stmt = $pdo->prepare("UPDATE users SET subscription_status = 'free' WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Commit the transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Your subscription has been cancelled successfully. You will have access to premium features until the end of your current billing period.'
        ];
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        error_log("Cancel subscription error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred while cancelling your subscription'
        ];
    }
}

/**
 * Check if a user has access to premium content
 * 
 * @param int $userId - The user ID
 * @return bool - True if user has premium access, false otherwise
 */
function hasPremiumAccess($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT subscription_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return ($user && $user['subscription_status'] === 'premium');
    } catch (PDOException $e) {
        error_log("Check premium access error: " . $e->getMessage());
        return false;
    }
}

/**
 * Process a payment for a subscription
 * 
 * @param int $userId - The user ID
 * @param string $paymentMethod - The payment method
 * @param string $plan - The subscription plan
 * @return array - ['success' => bool, 'message' => string]
 */
function processPayment($userId, $paymentMethod, $plan) {
    // Check if plan is valid
    if ($plan !== PLAN_PREMIUM) {
        return [
            'success' => false,
            'message' => 'Invalid subscription plan'
        ];
    }
    
    // In a real application, this would integrate with a payment gateway
    // For this demo, we'll just simulate a successful payment
    
    // Create subscription
    $result = createSubscription($userId, $paymentMethod, $plan, PREMIUM_PRICE);
    
    if (!$result['success']) {
        return [
            'success' => false,
            'message' => $result['message']
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Payment processed successfully'
    ];
}
