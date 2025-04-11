<?php
/**
 * System statistics functions for admin dashboard
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Get comprehensive system statistics
 * 
 * @return array Array of system statistics
 */
function getSystemStatistics() {
    $pdo = getDbConnection();
    if (!$pdo) {
        // Return default values if database connection fails
        return [
            'total_users' => 0,
            'total_admins' => 0,
            'total_teachers' => 0,
            'total_students' => 0,
            'total_classes' => 0,
            'active_classes' => 0,
            'total_modules' => 0,
            'total_activities' => 0,
            'total_submissions' => 0
        ];
    }
    
    try {
        // Get user statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_users,
                COUNT(CASE WHEN user_type = 'admin' THEN 1 END) AS total_admins,
                COUNT(CASE WHEN user_type = 'teacher' THEN 1 END) AS total_teachers,
                COUNT(CASE WHEN user_type = 'student' THEN 1 END) AS total_students
            FROM users
        ");
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get class statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_classes,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) AS active_classes
            FROM classes
        ");
        $classStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get module statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_modules,
                COUNT(CASE WHEN is_published = 1 THEN 1 END) AS published_modules
            FROM modules
        ");
        $moduleStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get activity statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_activities,
                COUNT(CASE WHEN is_published = 1 THEN 1 END) AS published_activities
            FROM activities
        ");
        $activityStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get submission statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total_submissions,
                COUNT(CASE WHEN graded = 1 THEN 1 END) AS graded_submissions
            FROM activity_submissions
        ");
        $submissionStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combine all statistics
        return [
            'total_users' => (int)$userStats['total_users'],
            'total_admins' => (int)$userStats['total_admins'],
            'total_teachers' => (int)$userStats['total_teachers'],
            'total_students' => (int)$userStats['total_students'],
            'total_classes' => (int)$classStats['total_classes'],
            'active_classes' => (int)$classStats['active_classes'],
            'total_modules' => (int)$moduleStats['total_modules'],
            'published_modules' => (int)$moduleStats['published_modules'],
            'total_activities' => (int)$activityStats['total_activities'],
            'published_activities' => (int)$activityStats['published_activities'],
            'total_submissions' => (int)$submissionStats['total_submissions'],
            'graded_submissions' => (int)$submissionStats['graded_submissions']
        ];
    } catch (PDOException $e) {
        error_log('Error getting system statistics: ' . $e->getMessage());
        
        // Return default values if query fails
        return [
            'total_users' => 0,
            'total_admins' => 0,
            'total_teachers' => 0,
            'total_students' => 0,
            'total_classes' => 0,
            'active_classes' => 0,
            'total_modules' => 0,
            'total_activities' => 0,
            'total_submissions' => 0
        ];
    }
}

/**
 * Get recently added users
 * 
 * @param int $limit Maximum number of users to return
 * @return array Array of recent users
 */
function getRecentUsers($limit = 10) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, first_name, last_name, user_type, created_at 
            FROM users 
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching recent users: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get system activity logs
 * 
 * @param int $limit Maximum number of logs to return
 * @param string $type Optional log type filter
 * @return array Array of system logs
 */
function getSystemLogs($limit = 50, $type = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $query = "
            SELECT * FROM system_logs
        ";
        
        $params = [];
        
        if ($type !== null) {
            $query .= " WHERE log_type = ?";
            $params[] = $type;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching system logs: ' . $e->getMessage());
        return [];
    }
}
