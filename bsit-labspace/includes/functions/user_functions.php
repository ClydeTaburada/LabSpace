<?php
/**
 * User management functions
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Get all users from the database
 * 
 * @return array Array of users
 */
function getAllUsers() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("
            SELECT id, email, first_name, last_name, user_type, force_password_change, created_at, updated_at 
            FROM users 
            ORDER BY user_type, last_name, first_name
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching users: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get user by ID
 * 
 * @param int $id User ID
 * @return array|null User data or null if not found
 */
function getUserById($id) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name, user_type, force_password_change, created_at, updated_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new teacher account
 * 
 * @param string $firstName Teacher's first name
 * @param string $lastName Teacher's last name
 * @param string $email Teacher's email
 * @param string $password Initial password
 * @param string $employeeId Employee ID
 * @param string $department Department
 * @return array Result with success flag and message
 */
function createTeacher($firstName, $lastName, $email, $password, $employeeId, $department) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Check if employee ID already exists
        $stmt = $pdo->prepare("SELECT teacher_id FROM teacher_profiles WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Employee ID already registered'];
        }
        
        // Insert into users table
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, first_name, last_name, user_type, force_password_change)
            VALUES (?, ?, ?, ?, 'teacher', 1)
        ");
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
        
        // Get the new user ID
        $teacherId = $pdo->lastInsertId();
        
        // Insert into teacher_profiles table
        $stmt = $pdo->prepare("
            INSERT INTO teacher_profiles (teacher_id, department, employee_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$teacherId, $department, $employeeId]);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Teacher account created successfully',
            'user_id' => $teacherId,
            'default_password' => $password
        ];
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        error_log('Error creating teacher: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create teacher account: ' . $e->getMessage()];
    }
}

/**
 * Update user information
 * 
 * @param int $userId User ID
 * @param string $firstName First name
 * @param string $lastName Last name
 * @param string $email Email address
 * @param array $additionalFields Additional fields for specific user types
 * @param bool $resetPassword Whether to reset the password
 * @return array Result with success flag and message
 */
function updateUser($userId, $firstName, $lastName, $email, $additionalFields = [], $resetPassword = false) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if email already exists (excluding the current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered to another user'];
        }
        
        // Get the current user to check type
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Update user basic info
        $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?";
        $params = [$firstName, $lastName, $email];
        
        // Reset password if requested
        if ($resetPassword) {
            $newPassword = generateDefaultPassword();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery .= ", password = ?, force_password_change = 1";
            $params[] = $hashedPassword;
        }
        
        $updateQuery .= " WHERE id = ?";
        $params[] = $userId;
        
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);
        
        // Update profile-specific information
        if ($user['user_type'] === 'teacher' && !empty($additionalFields)) {
            $employeeId = $additionalFields['employee_id'] ?? '';
            $department = $additionalFields['department'] ?? '';
            
            // Check if employee ID already exists (excluding the current user)
            if (!empty($employeeId)) {
                $stmt = $pdo->prepare("SELECT teacher_id FROM teacher_profiles WHERE employee_id = ? AND teacher_id != ?");
                $stmt->execute([$employeeId, $userId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Employee ID already registered to another teacher'];
                }
                
                // Update teacher profile
                $stmt = $pdo->prepare("
                    UPDATE teacher_profiles SET department = ?, employee_id = ?
                    WHERE teacher_id = ?
                ");
                $stmt->execute([$department, $employeeId, $userId]);
            }
        } else if ($user['user_type'] === 'student' && !empty($additionalFields)) {
            $studentNumber = $additionalFields['student_number'] ?? '';
            $yearLevel = $additionalFields['year_level'] ?? '';
            $section = $additionalFields['section'] ?? '';
            
            // Check if student number already exists (excluding the current user)
            if (!empty($studentNumber)) {
                $stmt = $pdo->prepare("SELECT student_id FROM student_profiles WHERE student_number = ? AND student_id != ?");
                $stmt->execute([$studentNumber, $userId]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Student number already registered to another student'];
                }
                
                // Update student profile
                $stmt = $pdo->prepare("
                    UPDATE student_profiles SET year_level = ?, section = ?, student_number = ?
                    WHERE student_id = ?
                ");
                $stmt->execute([$yearLevel, $section, $studentNumber, $userId]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'User updated successfully',
            'new_password' => $resetPassword ? $newPassword : null
        ];
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        error_log('Error updating user: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()];
    }
}

/**
 * Delete a user
 * 
 * @param int $userId User ID
 * @return bool True if successful, false otherwise
 */
function deleteUser($userId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get user type to handle related records
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        // Delete the user (foreign key constraints will handle related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Commit transaction
        $pdo->commit();
        
        return true;
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        error_log('Error deleting user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get teacher profile data
 * 
 * @param int $teacherId Teacher's user ID
 * @return array|null Teacher profile data
 */
function getTeacherProfile($teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT department, employee_id 
            FROM teacher_profiles 
            WHERE teacher_id = ?
        ");
        $stmt->execute([$teacherId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting teacher profile: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get student profile data
 * 
 * @param int $studentId Student's user ID
 * @return array|null Student profile data
 */
function getStudentProfile($studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT year_level, section, student_number 
            FROM student_profiles 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student profile: ' . $e->getMessage());
        return null;
    }
}

/**
 * Generate a default password for new accounts
 * 
 * @return string Generated password
 */
function generateDefaultPassword() {
    // Generate a random 8-character password
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < 8; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Get the appropriate Bootstrap color class for a user type
 * 
 * @param string $userType User type
 * @return string CSS class name
 */
function getUserTypeClass($userType) {
    switch ($userType) {
        case 'admin':
            return 'danger';
        case 'teacher':
            return 'primary';
        case 'student':
            return 'success';
        default:
            return 'secondary';
    }
}
