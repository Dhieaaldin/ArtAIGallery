<?php
/**
 * Database connection and utility functions
 */

require_once 'config.php';

try {
    // Create a PDO instance
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . getenv('PGPORT') . ";dbname=" . DB_NAME;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error and display friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

/**
 * Get a single record from the database
 * 
 * @param string $table - The table name
 * @param int $id - The record ID
 * @param string $idColumn - The ID column name (default: 'id')
 * @return array|false - The record data or false if not found
 */
function getRecord($table, $id, $idColumn = 'id') {
    global $pdo;
    
    $sql = "SELECT * FROM $table WHERE $idColumn = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    return $stmt->fetch();
}

/**
 * Insert a record into the database
 * 
 * @param string $table - The table name
 * @param array $data - Associative array of column names and values
 * @return int|false - The inserted ID or false on failure
 */
function insertRecord($table, array $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($data)) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Update a record in the database
 * 
 * @param string $table - The table name
 * @param int $id - The record ID
 * @param array $data - Associative array of column names and values to update
 * @param string $idColumn - The ID column name (default: 'id')
 * @return bool - True on success, false on failure
 */
function updateRecord($table, $id, array $data, $idColumn = 'id') {
    global $pdo;
    
    $set = [];
    foreach ($data as $column => $value) {
        $set[] = "$column = :$column";
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $idColumn = :id";
    $stmt = $pdo->prepare($sql);
    
    $data['id'] = $id;
    return $stmt->execute($data);
}

/**
 * Delete a record from the database
 * 
 * @param string $table - The table name
 * @param int $id - The record ID
 * @param string $idColumn - The ID column name (default: 'id')
 * @return bool - True on success, false on failure
 */
function deleteRecord($table, $id, $idColumn = 'id') {
    global $pdo;
    
    $sql = "DELETE FROM $table WHERE $idColumn = :id";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute(['id' => $id]);
}

/**
 * Run a SELECT query with pagination
 *
 * @param string $sql - SQL query with placeholders
 * @param array $params - Parameters for the query
 * @param int $page - The page number (starting from 1)
 * @param int $perPage - Items per page
 * @return array - ['data' => results, 'total' => total count, 'total_pages' => total pages]
 */
function paginateQuery($sql, array $params = [], $page = 1, $perPage = DEFAULT_PAGE_SIZE) {
    global $pdo;
    
    // Get total count first
    $countSql = preg_replace('/SELECT\s+.+?\s+FROM\s+/is', 'SELECT COUNT(*) FROM ', $sql);
    $countSql = preg_replace('/ORDER\s+BY\s+.+$/is', '', $countSql);
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    
    // Calculate pagination
    $page = max(1, $page);
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Get paginated data
    $paginatedSql = $sql . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($paginatedSql);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $perPage
    ];
}

/**
 * Check if a table exists in the database
 * 
 * @param string $tableName - The table name to check
 * @return bool - True if table exists, false otherwise
 */
function tableExists($tableName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = :table_name
            )
        ");
        $stmt->execute(['table_name' => $tableName]);
        return (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Initialize the database with required tables if they don't exist
 * This is a simplified version just for demonstration purposes
 */
function initializeDatabase() {
    global $pdo;
    
    // Users table
    if (!tableExists('users')) {
        $pdo->exec("CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            subscription_status VARCHAR(10) DEFAULT 'free' CHECK (subscription_status IN ('free', 'premium')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create a trigger to update the updated_at timestamp
        $pdo->exec("
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ language 'plpgsql';
        ");
        
        $pdo->exec("
            CREATE TRIGGER update_users_updated_at
            BEFORE UPDATE ON users
            FOR EACH ROW
            EXECUTE FUNCTION update_updated_at_column();
        ");
    }
    
    // Artwork table
    if (!tableExists('artwork')) {
        $pdo->exec("CREATE TABLE artwork (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) NOT NULL,
            high_res_url VARCHAR(255),
            style VARCHAR(100),
            category VARCHAR(100),
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // User favorites table
    if (!tableExists('user_favorites')) {
        $pdo->exec("CREATE TABLE user_favorites (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            artwork_id INTEGER NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, artwork_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (artwork_id) REFERENCES artwork(id) ON DELETE CASCADE
        )");
    }
    
    // Downloads table
    if (!tableExists('downloads')) {
        $pdo->exec("CREATE TABLE downloads (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            artwork_id INTEGER NOT NULL,
            quality VARCHAR(10) DEFAULT 'low' CHECK (quality IN ('low', 'high')),
            download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (artwork_id) REFERENCES artwork(id) ON DELETE CASCADE
        )");
    }
    
    // Subscriptions table
    if (!tableExists('subscriptions')) {
        $pdo->exec("CREATE TABLE subscriptions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            subscription_status VARCHAR(10) DEFAULT 'active' CHECK (subscription_status IN ('active', 'cancelled', 'expired')),
            next_billing_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        $pdo->exec("
            CREATE TRIGGER update_subscriptions_updated_at
            BEFORE UPDATE ON subscriptions
            FOR EACH ROW
            EXECUTE FUNCTION update_updated_at_column();
        ");
    }
    
    // Insert some sample artwork if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM artwork");
    $artworkCount = $stmt->fetchColumn();
    
    if ($artworkCount == 0) {
        // Sample artwork data
        $artwork = [
            [
                'title' => 'Abstract Dream',
                'description' => 'A dreamlike abstract composition with vibrant colors and flowing forms.',
                'image_url' => 'assets/artwork/abstract_dream.jpg',
                'high_res_url' => 'assets/artwork/high_res/abstract_dream.jpg',
                'style' => 'Abstract',
                'category' => 'Digital',
                'featured' => TRUE
            ],
            [
                'title' => 'Cosmic Journey',
                'description' => 'An exploration of space and cosmic energy through digital art.',
                'image_url' => 'assets/artwork/cosmic_journey.jpg',
                'high_res_url' => 'assets/artwork/high_res/cosmic_journey.jpg',
                'style' => 'Sci-Fi',
                'category' => 'Digital',
                'featured' => TRUE
            ],
            [
                'title' => 'Urban Landscape',
                'description' => 'A futuristic interpretation of urban architecture and city life.',
                'image_url' => 'assets/artwork/urban_landscape.jpg',
                'high_res_url' => 'assets/artwork/high_res/urban_landscape.jpg',
                'style' => 'Futuristic',
                'category' => 'Cityscape',
                'featured' => TRUE
            ],
            [
                'title' => 'Natural Harmony',
                'description' => 'A serene natural landscape showing the harmony of elements in nature.',
                'image_url' => 'assets/artwork/natural_harmony.jpg',
                'high_res_url' => 'assets/artwork/high_res/natural_harmony.jpg',
                'style' => 'Realistic',
                'category' => 'Landscape',
                'featured' => TRUE
            ],
            [
                'title' => 'Digital Portrait',
                'description' => 'A digitally created portrait with a unique artistic style.',
                'image_url' => 'assets/artwork/digital_portrait.jpg',
                'high_res_url' => 'assets/artwork/high_res/digital_portrait.jpg',
                'style' => 'Portrait',
                'category' => 'Digital',
                'featured' => FALSE
            ],
            [
                'title' => 'Geometric Patterns',
                'description' => 'Complex geometric patterns forming a mesmerizing visual composition.',
                'image_url' => 'assets/artwork/geometric_patterns.jpg',
                'high_res_url' => 'assets/artwork/high_res/geometric_patterns.jpg',
                'style' => 'Geometric',
                'category' => 'Abstract',
                'featured' => FALSE
            ]
        ];
        
        // Insert sample artwork
        $stmt = $pdo->prepare("
            INSERT INTO artwork (title, description, image_url, high_res_url, style, category, featured)
            VALUES (:title, :description, :image_url, :high_res_url, :style, :category, :featured)
        ");
        
        foreach ($artwork as $item) {
            $stmt->execute($item);
        }
    }
}

// Initialize database on first run
initializeDatabase();
