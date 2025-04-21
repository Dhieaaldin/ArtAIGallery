<?php
/**
 * API endpoint for artwork operations
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Process request based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Handle GET requests (fetch artwork)
    handleGetRequest();
} elseif ($method === 'POST') {
    // Handle POST requests (for admin functions - not implemented in this demo)
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

/**
 * Handle GET requests to fetch artwork
 */
function handleGetRequest() {
    global $pdo;
    
    // Get query parameters
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? (int)$_GET['per_page'] : DEFAULT_PAGE_SIZE;
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $style = isset($_GET['style']) ? trim($_GET['style']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
    
    // Validate per_page to prevent excessive requests
    $perPage = min($perPage, 50);
    
    try {
        // Build the SQL query
        $sql = "SELECT a.* ";
        
        // Add favorite status if user is logged in
        if (isLoggedIn()) {
            $sql .= ", (SELECT COUNT(*) FROM user_favorites WHERE artwork_id = a.id AND user_id = :user_id) AS is_favorite ";
        }
        
        $sql .= "FROM artwork a WHERE 1=1 ";
        
        // Apply filters
        $params = [];
        
        if (!empty($category)) {
            $sql .= "AND a.category = :category ";
            $params['category'] = $category;
        }
        
        if (!empty($style)) {
            $sql .= "AND a.style = :style ";
            $params['style'] = $style;
        }
        
        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $sql .= "ORDER BY a.created_at ASC ";
                break;
            case 'name_asc':
                $sql .= "ORDER BY a.title ASC ";
                break;
            case 'name_desc':
                $sql .= "ORDER BY a.title DESC ";
                break;
            case 'newest':
            default:
                $sql .= "ORDER BY a.created_at DESC ";
                break;
        }
        
        // Add user_id parameter if logged in
        if (isLoggedIn()) {
            $params['user_id'] = $_SESSION['user_id'];
        }
        
        // Fetch paginated results
        $paginationResult = paginateQuery($sql, $params, $page, $perPage);
        
        // Return the response
        echo json_encode([
            'success' => true,
            'artwork' => $paginationResult['data'],
            'total' => $paginationResult['total'],
            'total_pages' => $paginationResult['total_pages'],
            'current_page' => $paginationResult['current_page'],
            'per_page' => $paginationResult['per_page']
        ]);
    } catch (Exception $e) {
        error_log("API error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while fetching artwork'
        ]);
    }
}
