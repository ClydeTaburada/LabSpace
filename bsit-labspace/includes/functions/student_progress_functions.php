<?php
/**
 * Student progress functions for teachers
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Get student by ID with profile information
 * 
 * @param int $studentId Student ID
 * @return array|null Student data or null if not found
 */
function getStudentById($studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.first_name, u.last_name, u.email, u.created_at,
                sp.year_level, sp.section, sp.student_number
            FROM 
                users u
                JOIN student_profiles sp ON u.id = sp.student_id
            WHERE 
                u.id = ? AND u.user_type = 'student'
        ");
        $stmt->execute([$studentId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if student is enrolled in a class
 * 
 * @param int $studentId Student ID
 * @param int $classId Class ID
 * @return bool True if enrolled, false otherwise
 */
function isStudentEnrolledInClass($studentId, $classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM class_enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$studentId, $classId]);
        
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log('Error checking student enrollment: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get detailed student progress data for a specific class
 * 
 * @param int $studentId Student ID
 * @param int $classId Class ID
 * @return array Progress data including modules, activities and submissions
 */
function getStudentClassProgress($studentId, $classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            'total_activities' => 0,
            'completed_activities' => 0,
            'average_grade' => 0,
            'modules' => []
        ];
    }
    
    try {
        // Get enrollment date
        $stmt = $pdo->prepare("
            SELECT enrollment_date FROM class_enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$studentId, $classId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$enrollment) {
            return [
                'total_activities' => 0,
                'completed_activities' => 0,
                'average_grade' => 0,
                'modules' => []
            ];
        }
        
        // Get all modules from this class
        $stmt = $pdo->prepare("
            SELECT id, title, description, order_index
            FROM modules 
            WHERE class_id = ?
            ORDER BY order_index ASC
        ");
        $stmt->execute([$classId]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get overall class statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT a.id) AS total_activities,
                COUNT(DISTINCT sub.id) AS completed_activities,
                COUNT(CASE WHEN sub.graded = 1 THEN sub.id END) AS graded_count,
                AVG(CASE WHEN sub.graded = 1 THEN sub.grade ELSE NULL END) AS average_grade
            FROM 
                modules m
                JOIN activities a ON a.module_id = m.id
                LEFT JOIN activity_submissions sub ON sub.activity_id = a.id AND sub.student_id = ?
            WHERE 
                m.class_id = ?
        ");
        $stmt->execute([$studentId, $classId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = [
            'total_activities' => (int)($stats['total_activities'] ?? 0),
            'completed_activities' => (int)($stats['completed_activities'] ?? 0),
            'graded_count' => (int)($stats['graded_count'] ?? 0),
            'average_grade' => round(($stats['average_grade'] ?? 0), 1),
            'modules' => []
        ];
        
        // Process each module
        foreach ($modules as $module) {
            // Get activities for this module
            $stmt = $pdo->prepare("
                SELECT a.id, a.title, a.activity_type, a.due_date, a.max_score
                FROM activities a
                WHERE a.module_id = ?
                ORDER BY a.created_at
            ");
            $stmt->execute([$module['id']]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalModuleActivities = count($activities);
            $completedModuleActivities = 0;
            
            // Get submissions for each activity
            foreach ($activities as &$activity) {
                // Get submission for this activity if exists
                $stmt = $pdo->prepare("
                    SELECT s.*
                    FROM activity_submissions s
                    WHERE s.activity_id = ? AND s.student_id = ?
                ");
                $stmt->execute([$activity['id'], $studentId]);
                $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($submission) {
                    $activity['submission'] = $submission;
                    $completedModuleActivities++;
                }
            }
            
            // Calculate completion percentage
            $completionPercentage = $totalModuleActivities > 0 
                ? round(($completedModuleActivities / $totalModuleActivities) * 100) 
                : 0;
            
            $result['modules'][] = [
                'id' => $module['id'],
                'title' => $module['title'],
                'description' => $module['description'],
                'order_index' => $module['order_index'],
                'completion_percentage' => $completionPercentage,
                'total_activities' => $totalModuleActivities,
                'completed_activities' => $completedModuleActivities,
                'activities' => $activities
            ];
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Error getting student class progress: ' . $e->getMessage());
        return [
            'total_activities' => 0,
            'completed_activities' => 0,
            'average_grade' => 0,
            'modules' => []
        ];
    }
}

/**
 * Get all student submissions for activities in a class
 * 
 * @param int $studentId Student ID
 * @param int $classId Class ID
 * @return array Array of submissions
 */
function getStudentClassSubmissions($studentId, $classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sub.*, 
                a.title AS activity_title, a.activity_type,
                m.title AS module_title
            FROM 
                activity_submissions sub
                JOIN activities a ON sub.activity_id = a.id
                JOIN modules m ON a.module_id = m.id
            WHERE 
                sub.student_id = ? AND m.class_id = ?
            ORDER BY 
                sub.submission_date DESC
        ");
        $stmt->execute([$studentId, $classId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student submissions: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get student's progress data across all enrolled classes
 * 
 * @param int $studentId Student ID
 * @return array Progress data by class and module
 */
function getStudentAllClassesProgress($studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            'classes' => [],
            'modules' => []
        ];
    }
    
    try {
        // Get classes the student is enrolled in with more details
        $query = "
            SELECT 
                c.id AS class_id,
                c.class_code,
                c.section,
                c.year_level,
                s.code AS subject_code,
                s.name AS subject_name,
                CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
                COUNT(DISTINCT a.id) AS total_activities,
                COUNT(DISTINCT sub.id) AS completed_activities,
                COUNT(CASE WHEN sub.graded = 1 THEN sub.id END) AS graded_count,
                AVG(CASE WHEN sub.graded = 1 THEN sub.grade ELSE NULL END) AS average_grade
            FROM 
                class_enrollments ce
                JOIN classes c ON ce.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN users u ON c.teacher_id = u.id
                LEFT JOIN modules m ON m.class_id = c.id AND m.is_published = 1
                LEFT JOIN activities a ON a.module_id = m.id AND a.is_published = 1
                LEFT JOIN activity_submissions sub ON sub.activity_id = a.id AND sub.student_id = ?
            WHERE 
                ce.student_id = ?
            GROUP BY 
                c.id, c.class_code, c.section, c.year_level, s.code, s.name, u.first_name, u.last_name
            ORDER BY 
                ce.enrollment_date DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId, $studentId]);
        $classProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get module-level progress data
        $query = "
            SELECT 
                m.id AS module_id,
                m.title AS module_title,
                m.class_id,
                COUNT(DISTINCT a.id) AS total_activities,
                COUNT(DISTINCT sub.id) AS completed_activities
            FROM 
                class_enrollments ce
                JOIN classes c ON ce.class_id = c.id
                JOIN modules m ON m.class_id = c.id AND m.is_published = 1
                LEFT JOIN activities a ON a.module_id = m.id AND a.is_published = 1
                LEFT JOIN activity_submissions sub ON sub.activity_id = a.id AND sub.student_id = ?
            WHERE 
                ce.student_id = ?
            GROUP BY 
                m.id, m.title, m.class_id
            ORDER BY 
                m.order_index ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId, $studentId]);
        $moduleProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process data to calculate percentages and organize by class
        $result = [
            'classes' => [],
            'modules' => []
        ];
        
        foreach ($classProgress as &$class) {
            $class['completion_percentage'] = $class['total_activities'] > 0 
                ? round(($class['completed_activities'] / $class['total_activities']) * 100) 
                : 0;
            $result['classes'][$class['class_id']] = $class;
        }
        
        foreach ($moduleProgress as &$module) {
            $module['completion_percentage'] = $module['total_activities'] > 0 
                ? round(($module['completed_activities'] / $module['total_activities']) * 100) 
                : 0;
            
            if (!isset($result['modules'][$module['class_id']])) {
                $result['modules'][$module['class_id']] = [];
            }
            
            $result['modules'][$module['class_id']][] = $module;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Error getting student progress across all classes: ' . $e->getMessage());
        return [
            'classes' => [],
            'modules' => []
        ];
    }
}

/**
 * Get student's recent submissions across all classes
 * 
 * @param int $studentId Student ID
 * @param int $limit Maximum number of submissions to return
 * @return array Recent submissions
 */
function getStudentRecentSubmissions($studentId, $limit = 15) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sub.*, 
                a.title AS activity_title, 
                a.activity_type,
                m.title AS module_title,
                m.id AS module_id,
                c.id AS class_id,
                s.code AS subject_code,
                s.name AS subject_name
            FROM 
                activity_submissions sub
                JOIN activities a ON sub.activity_id = a.id
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
            WHERE 
                sub.student_id = ?
            ORDER BY
                sub.submission_date DESC
            LIMIT ?
        ");
        $stmt->execute([$studentId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student recent submissions: ' . $e->getMessage());
        return [];
    }
}
