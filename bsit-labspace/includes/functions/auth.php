<?php
/**
 * Authentication related functions
 */

/**
 * Log in a user
 * @param string $email User email
 * @param string $password User password
 * @return array Result with success flag and message or user data
 */
function loginUser($email, $password) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, password, user_type, force_password_change FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Return user data for session
        return [
            'success' => true, 
            'user_id' => $user['id'],
            'user_name' => $user['first_name'] . ' ' . $user['last_name'],
            'user_type' => $user['user_type'],
            'force_password_change' => $user['force_password_change']
        ];
        
    } catch (PDOException $e) {
        error_log('Login Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed due to system error'];
    }
}

/**
 * Verify current session is valid
 * Checks if session exists and contains expected user data
 * @return bool True if session is valid
 */
function verifySession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Could add additional verification here like checking against database
    return true;
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * @param string|array $roles Single role or array of roles
 * @return bool True if user has one of the specified roles
 */
function hasRole($roles) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_type'], $roles);
    }
    
    return $_SESSION['user_type'] == $roles;
}

/**
 * Ensure user is logged in, redirect to login otherwise
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'index.php?error=Please log in to continue');
        exit;
    }
}

/**
 * Ensure user has a specific role, redirect otherwise
 * @param string|array $roles Required role(s)
 * @param string $redirect URL to redirect to if check fails
 */
function requireRole($roles, $redirect = null) {
    requireLogin();
    
    if (!hasRole($roles)) {
        if (!$redirect) {
            $redirect = getBaseUrl() . 'index.php?error=Access denied';
        }
        
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Get base URL for the application
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the application's base path
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $baseDir = '';
    
    // If the script is in a subdirectory, get that directory
    if (strpos($scriptName, 'bsit-labspace') !== false) {
        $pathParts = explode('/', $scriptName);
        $baseIndex = array_search('bsit-labspace', $pathParts);
        if ($baseIndex !== false) {
            $baseDir = '/';
            for ($i = 1; $i <= $baseIndex; $i++) {
                $baseDir .= $pathParts[$i] . '/';
            }
        }
    }
    
    return $protocol . $host . $baseDir;
}

/**
 * Update user password and reset force change flag
 * @param int $userId User ID
 * @param string $newPassword New password
 * @return bool True if password was updated successfully
 */
function updateUserPassword($userId, $newPassword) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password, force_password_change = 0 WHERE id = :id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Password Update Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if password needs to be changed
 * @param int $userId User ID
 * @return bool True if password needs to be changed
 */
function needsPasswordChange($userId) {
    if (!isset($_SESSION['force_password_change']) || !$_SESSION['force_password_change']) {
        return false;
    }
    
    return true;
}

/**
 * Check if a class code is valid
 * @param string $classCode The class code to validate
 * @return bool|array False if invalid, or array with class info if valid
 */
function validateClassCode($classCode) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.class_code, c.section, s.name as subject_name, 
                   CONCAT(u.first_name, ' ', u.last_name) as teacher_name
            FROM classes c
            JOIN subjects s ON c.subject_id = s.id
            JOIN users u ON c.teacher_id = u.id
            WHERE c.class_code = ? AND c.is_active = 1
        ");
        $stmt->execute([$classCode]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $class ?: false;
    } catch (PDOException $e) {
        error_log('Class Validation Error: ' . $e->getMessage());
        return false;
    }
}