<?php
/**
 * User Management System
 * This module handles user operations including:
 * - User authentication
 * - User registration
 * - Profile management
 * - Security functions
 */

/**
 * Database connection function
 * @return PDO Database connection
 */
function getDbConnection() {
    $host = 'localhost';
    $db = 'user_management';
    $user = 'app_user';
    $pass = 'secure_password';
    $charset = 'utf8mb4';
    
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Log error and return null
        logError('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Log error messages
 * @param string $message Error message
 * @param string $logfile Log file path (optional)
 * @return bool Success status
 */
function logError($message, $logfile = 'error.log') {
    $timestamp = date('Y-m-d H:i:s');
    return error_log("[$timestamp] $message\n", 3, $logfile);
}

/**
 * Get user by ID
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    $pdo = getDbConnection();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logError('Error getting user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all users
 * @param int $limit Maximum number of users to return
 * @param int $offset Pagination offset
 * @return array Array of users
 */
function getAllUsers($limit = 20, $offset = 0) {
    $pdo = getDbConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare(
            "SELECT id, name, email, role, created_at FROM users 
             ORDER BY name ASC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError('Error getting all users: ' . $e->getMessage());
        return [];
    }
}

/**
 * Search users by name or email
 * @param string $searchTerm Search term
 * @return array Matching users
 */
function searchUsers($searchTerm) {
    $pdo = getDbConnection();
    if (!$pdo) return [];
    
    $searchTerm = "%$searchTerm%";
    
    try {
        $stmt = $pdo->prepare(
            "SELECT id, name, email, role, created_at FROM users 
             WHERE name LIKE ? OR email LIKE ?
             ORDER BY name ASC LIMIT 50"
        );
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError('Error searching users: ' . $e->getMessage());
        return [];
    }
}

/**
 * Create a new user
 * @param array $userData User data (name, email, password)
 * @return int|bool New user ID or false on failure
 */
function createUser($userData) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    // Validate required fields
    if (!isset($userData['name']) || !isset($userData['email']) || !isset($userData['password'])) {
        return false;
    }
    
    // Make sure email is not already in use
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        if ($stmt->fetch()) {
            // Email already exists
            return false;
        }
    } catch (PDOException $e) {
        logError('Error checking email: ' . $e->getMessage());
        return false;
    }
    
    // Hash password
    $hashedPassword = hashPassword($userData['password']);
    
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role, created_at) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        $role = $userData['role'] ?? 'user';
        $stmt->execute([$userData['name'], $userData['email'], $hashedPassword, $role]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        logError('Error creating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update user data
 * @param int $userId User ID
 * @param array $userData Updated user data
 * @return bool Success status
 */
function updateUser($userId, $userData) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    // Prepare update fields
    $updates = [];
    $params = [];
    
    // Process each field
    if (isset($userData['name'])) {
        $updates[] = "name = ?";
        $params[] = $userData['name'];
    }
    
    if (isset($userData['email'])) {
        // Check if email is already in use by another user
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$userData['email'], $userId]);
            if ($stmt->fetch()) {
                // Email already exists for another user
                return false;
            }
        } catch (PDOException $e) {
            logError('Error checking email: ' . $e->getMessage());
            return false;
        }
        
        $updates[] = "email = ?";
        $params[] = $userData['email'];
    }
    
    if (isset($userData['password'])) {
        $updates[] = "password = ?";
        $params[] = hashPassword($userData['password']);
    }
    
    if (isset($userData['role'])) {
        $updates[] = "role = ?";
        $params[] = $userData['role'];
    }
    
    // If no fields to update
    if (empty($updates)) {
        return false;
    }
    
    // Add user ID to params
    $params[] = $userId;
    
    // Perform update
    try {
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        logError('Error updating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a user
 * @param int $userId User ID
 * @return bool Success status
 */
function deleteUser($userId) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        logError('Error deleting user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Hash a password
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Validate a password against its hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool Valid or not
 */
function validatePassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Authenticate user with email and password
 * @param string $email User email
 * @param string $password Plain text password
 * @return array|bool User data or false if authentication fails
 */
function authenticateUser($email, $password) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && validatePassword($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        logError('Authentication error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create user session after successful login
 * @param array $user User data
 */
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
}

/**
 * Format date to human-readable format
 * @param string $dateString Database date string
 * @return string Formatted date
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y');
}

/**
 * Sanitize user input
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get paginated results from array
 * @param array $items Array of items
 * @param int $page Page number (1-based)
 * @param int $perPage Items per page
 * @return array Paginated subset of items
 */
function getPaginatedResults($items, $page = 1, $perPage = 10) {
    $page = max(1, $page); // Ensure page is at least 1
    $perPage = max(1, $perPage); // Ensure items per page is at least 1
    $offset = ($page - 1) * $perPage;
    
    return array_slice($items, $offset, $perPage);
}

// Create a User class for OOP implementation
class User {
    private $id;
    private $name;
    private $email;
    private $role;
    private $loggedIn = false;
    
    public function __construct($userData = null) {
        if (is_array($userData)) {
            $this->id = $userData['id'] ?? null;
            $this->name = $userData['name'] ?? '';
            $this->email = $userData['email'] ?? '';
            $this->role = $userData['role'] ?? 'user';
        }
    }
    
    public function login($email, $password) {
        $userData = authenticateUser($email, $password);
        if ($userData) {
            $this->id = $userData['id'];
            $this->name = $userData['name'];
            $this->email = $userData['email'];
            $this->role = $userData['role'];
            $this->loggedIn = true;
            createUserSession($userData);
            return true;
        }
        return false;
    }
    
    public function logout() {
        $this->loggedIn = false;
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return $this->loggedIn;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRole() {
        return $this->role;
    }
}
