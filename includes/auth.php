<?php
/**
 * Authentication and user management functions
 */

require_once 'config.php';
require_once 'db.php';

/**
 * Check if a user is logged in
 * 
 * @return bool - True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Register a new user
 * 
 * @param string $name - User's full name
 * @param string $email - User's email address
 * @param string $password - User's password (plain text)
 * @return array - ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function registerUser($name, $email, $password) {
    global $pdo;
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Email address is already registered',
                'user_id' => null
            ];
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
        
        // Insert the new user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, subscription_status)
            VALUES (?, ?, ?, 'free')
        ");
        
        $stmt->execute([$name, $email, $hashedPassword]);
        $userId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId
        ];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.',
            'user_id' => null
        ];
    }
}

/**
 * Log in a user
 * 
 * @param string $email - User's email address
 * @param string $password - User's password (plain text)
 * @return array - ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function loginUser($email, $password) {
    global $pdo;
    
    try {
        // Get user by email
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password',
                'user_id' => null
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password',
                'user_id' => null
            ];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Update password hash if necessary (if using a newer algorithm)
        if (password_needs_rehash($user['password'], PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST])) {
            $newHash = password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
        }
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user_id' => $user['id']
        ];
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred during login. Please try again.',
            'user_id' => null
        ];
    }
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

/**
 * Get user data by ID
 * 
 * @param int $userId - The user ID
 * @param bool $includePassword - Whether to include the password hash in the result
 * @return array|false - User data or false if not found
 */
function getUserById($userId, $includePassword = false) {
    global $pdo;
    
    $columns = $includePassword 
        ? "id, name, email, password, subscription_status, created_at, updated_at" 
        : "id, name, email, subscription_status, created_at, updated_at";
    
    $stmt = $pdo->prepare("SELECT $columns FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    return $stmt->fetch();
}

/**
 * Update user account information
 * 
 * @param int $userId - The user ID
 * @param array $data - Associative array of fields to update
 * @return array - ['success' => bool, 'message' => string]
 */
function updateUserAccount($userId, array $data) {
    global $pdo;
    
    try {
        // Filter allowed fields
        $allowedFields = ['name', 'email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return [
                'success' => false,
                'message' => 'No valid fields to update'
            ];
        }
        
        // Check if email is being changed and if it's already taken
        if (isset($updateData['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$updateData['email'], $userId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Email address is already in use'
                ];
            }
        }
        
        // Update user data
        $updateResult = updateRecord('users', $userId, $updateData);
        
        if ($updateResult) {
            // Update session variables if name or email was changed
            if (isset($updateData['name'])) {
                $_SESSION['user_name'] = $updateData['name'];
            }
            
            if (isset($updateData['email'])) {
                $_SESSION['user_email'] = $updateData['email'];
            }
            
            return [
                'success' => true,
                'message' => 'Account information updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update account information'
            ];
        }
    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Update user password
 * 
 * @param int $userId - The user ID
 * @param string $currentPassword - Current password (plain text)
 * @param string $newPassword - New password (plain text)
 * @return array - ['success' => bool, 'message' => string]
 */
function updateUserPassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    try {
        // Get current user data with password
        $user = getUserById($userId, true);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
        
        // Update the password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $userId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }
    } catch (PDOException $e) {
        error_log("Update password error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Toggle favorite status for an artwork
 * 
 * @param int $userId - The user ID
 * @param int $artworkId - The artwork ID
 * @return array - ['success' => bool, 'message' => string, 'is_favorite' => bool]
 */
function toggleFavorite($userId, $artworkId) {
    global $pdo;
    
    try {
        // Check if the artwork exists
        $stmt = $pdo->prepare("SELECT id FROM artwork WHERE id = ?");
        $stmt->execute([$artworkId]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Artwork not found',
                'is_favorite' => false
            ];
        }
        
        // Check if it's already a favorite
        $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND artwork_id = ?");
        $stmt->execute([$userId, $artworkId]);
        $isFavorite = ($stmt->rowCount() > 0);
        
        if ($isFavorite) {
            // Remove from favorites
            $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND artwork_id = ?");
            $stmt->execute([$userId, $artworkId]);
            
            return [
                'success' => true,
                'message' => 'Artwork removed from favorites',
                'is_favorite' => false
            ];
        } else {
            // Add to favorites
            $stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, artwork_id) VALUES (?, ?)");
            $stmt->execute([$userId, $artworkId]);
            
            return [
                'success' => true,
                'message' => 'Artwork added to favorites',
                'is_favorite' => true
            ];
        }
    } catch (PDOException $e) {
        error_log("Toggle favorite error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.',
            'is_favorite' => false
        ];
    }
}

/**
 * Get user's favorite artwork
 * 
 * @param int $userId - The user ID
 * @return array - ['success' => bool, 'message' => string, 'favorites' => array]
 */
function getUserFavorites($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, 1 AS is_favorite
            FROM artwork a
            JOIN user_favorites uf ON a.id = uf.artwork_id
            WHERE uf.user_id = ?
            ORDER BY uf.created_at DESC
        ");
        $stmt->execute([$userId]);
        $favorites = $stmt->fetchAll();
        
        return [
            'success' => true,
            'message' => '',
            'favorites' => $favorites
        ];
    } catch (PDOException $e) {
        error_log("Get favorites error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred while fetching favorites',
            'favorites' => []
        ];
    }
}

/**
 * Get user's download history
 * 
 * @param int $userId - The user ID
 * @return array - ['success' => bool, 'message' => string, 'downloads' => array]
 */
function getUserDownloads($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT d.id, d.quality, d.download_date, a.id as artwork_id, a.title, a.image_url, a.style, a.category
            FROM downloads d
            JOIN artwork a ON d.artwork_id = a.id
            WHERE d.user_id = ?
            ORDER BY d.download_date DESC
        ");
        $stmt->execute([$userId]);
        
        $downloads = [];
        while ($row = $stmt->fetch()) {
            $downloads[] = [
                'id' => $row['id'],
                'quality' => $row['quality'],
                'download_date' => $row['download_date'],
                'artwork' => [
                    'id' => $row['artwork_id'],
                    'title' => $row['title'],
                    'image_url' => $row['image_url'],
                    'style' => $row['style'],
                    'category' => $row['category']
                ]
            ];
        }
        
        return [
            'success' => true,
            'message' => '',
            'downloads' => $downloads
        ];
    } catch (PDOException $e) {
        error_log("Get downloads error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'An error occurred while fetching download history',
            'downloads' => []
        ];
    }
}
