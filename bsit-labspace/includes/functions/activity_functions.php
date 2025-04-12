<?php
/**
 * Activity management functions
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Get all activities for a module
 * 
 * @param int $moduleId Module ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @return array Array of activities
 */
function getModuleActivities($moduleId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Build the query
        $query = "
            SELECT a.*
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            WHERE a.module_id = ?
        ";
        
        $params = [$moduleId];
        
        // If teacher ID is provided, verify ownership
        if ($teacherId !== null) {
            $query .= " AND c.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting module activities: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all activities for a specific module
 * 
 * @param int $moduleId The module ID
 * @return array Array of activities
 */
function getActivitiesByModuleId($moduleId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM activities WHERE module_id = ? ORDER BY created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$moduleId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting activities by module ID: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get an activity by ID
 * 
 * @param int $activityId Activity ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @return array|null Activity data or null if not found
 */
function getActivityById($activityId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Build the query with all needed joins to verify ownership
        $query = "
            SELECT a.*, m.title as module_title, m.class_id, c.subject_id,
                s.code as subject_code, s.name as subject_name
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE a.id = ?
        ";
        
        $params = [$activityId];
        
        // If teacher ID is provided, verify ownership
        if ($teacherId !== null) {
            $query .= " AND c.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting activity by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get activity by ID with minimal permission checks
 * This is used for emergency access when normal navigation fails
 * 
 * @param int $activityId Activity ID
 * @return array|null Activity data or null if not found
 */
function getActivityByIdDirect($activityId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Simple query with minimal joins
        $query = "
            SELECT a.*, m.class_id, m.title as module_title 
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            WHERE a.id = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$activityId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting activity by ID (direct): ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new activity
 * 
 * @param int $moduleId Module ID
 * @param string $title Activity title
 * @param string $description Activity description
 * @param string $type Activity type (assignment, quiz, coding, lab)
 * @param string $instructions Activity instructions
 * @param int $maxScore Maximum score
 * @param string $starterCode Starter code for coding activities
 * @param string $testCases Test cases for coding activities
 * @param string $dueDate Due date (can be null)
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function createActivity($moduleId, $title, $description, $type, $instructions, $maxScore = 100, 
                        $starterCode = null, $testCases = null, $dueDate = null, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the module
        if ($teacherId !== null) {
            $stmt = $pdo->prepare("
                SELECT m.id 
                FROM modules m
                JOIN classes c ON m.class_id = c.id
                WHERE m.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$moduleId, $teacherId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'You do not have permission to add activities to this module'];
            }
        }
        
        // Insert the new activity
        $stmt = $pdo->prepare("
            INSERT INTO activities (
                module_id, title, description, activity_type, instructions, 
                max_score, coding_starter_code, test_cases, due_date, is_published
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        
        $stmt->execute([
            $moduleId, $title, $description, $type, $instructions,
            $maxScore, $starterCode, $testCases, $dueDate ?: null
        ]);
        
        $activityId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Activity created successfully',
            'activity_id' => $activityId
        ];
    } catch (PDOException $e) {
        error_log('Error creating activity: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create activity: ' . $e->getMessage()];
    }
}

/**
 * Update an activity
 * 
 * @param int $activityId Activity ID
 * @param string $title Activity title
 * @param string $description Activity description
 * @param string $type Activity type
 * @param string $instructions Activity instructions
 * @param int $maxScore Maximum score
 * @param string $starterCode Starter code for coding activities
 * @param string $testCases Test cases for coding activities
 * @param string $dueDate Due date (can be null)
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function updateActivity($activityId, $title, $description, $type, $instructions, $maxScore = 100,
                        $starterCode = null, $testCases = null, $dueDate = null, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the activity
        if ($teacherId !== null) {
            $stmt = $pdo->prepare("
                SELECT a.id 
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                WHERE a.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$activityId, $teacherId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'You do not have permission to edit this activity'];
            }
        }
        
        // Update the activity
        $stmt = $pdo->prepare("
            UPDATE activities 
            SET title = ?, description = ?, activity_type = ?, instructions = ?,
                max_score = ?, coding_starter_code = ?, test_cases = ?, due_date = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title, $description, $type, $instructions,
            $maxScore, $starterCode, $testCases, $dueDate ?: null, 
            $activityId
        ]);
        
        return [
            'success' => true,
            'message' => 'Activity updated successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error updating activity: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update activity: ' . $e->getMessage()];
    }
}

/**
 * Delete an activity
 * 
 * @param int $activityId Activity ID
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function deleteActivity($activityId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the activity
        if ($teacherId !== null) {
            $stmt = $pdo->prepare("
                SELECT a.id 
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                WHERE a.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$activityId, $teacherId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'You do not have permission to delete this activity'];
            }
        }
        
        // Check if activity has submissions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE activity_id = ?");
        $stmt->execute([$activityId]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Cannot delete activity with existing submissions'];
        }
        
        // Delete the activity
        $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->execute([$activityId]);
        
        return [
            'success' => true,
            'message' => 'Activity deleted successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error deleting activity: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete activity: ' . $e->getMessage()];
    }
}

/**
 * Toggle activity publish status
 * 
 * @param int $activityId Activity ID
 * @param bool $isPublished Whether to publish or unpublish
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function toggleActivityPublishStatus($activityId, $isPublished, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the activity
        if ($teacherId !== null) {
            $stmt = $pdo->prepare("
                SELECT a.id 
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                WHERE a.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$activityId, $teacherId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'You do not have permission to modify this activity'];
            }
        }
        
        // Update the activity
        $stmt = $pdo->prepare("
            UPDATE activities
            SET is_published = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$isPublished ? 1 : 0, $activityId]);
        
        return [
            'success' => true,
            'message' => $isPublished ? 'Activity published successfully' : 'Activity unpublished successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error toggling activity publish status: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update activity status: ' . $e->getMessage()];
    }
}

/**
 * Get activity type display name
 * 
 * @param string $type Activity type code
 * @return string Display name
 */
function getActivityTypeName($type) {
    switch ($type) {
        case 'assignment':
            return 'Assignment';
        case 'quiz':
            return 'Quiz';
        case 'coding':
            return 'Coding Task';
        case 'lab':
            return 'Lab Exercise';
        default:
            return ucfirst($type);
    }
}

/**
 * Get available programming languages
 * 
 * @return array Array of programming languages
 */
function getAvailableProgrammingLanguages() {
    return [
        'python' => 'Python',
        'java' => 'Java',
        'cpp' => 'C++',
        'csharp' => 'C#',
        'javascript' => 'JavaScript',
        'php' => 'PHP',
        'html' => 'HTML/CSS',
        'sql' => 'SQL'
    ];
}

/**
 * Get published activities for a module (student view)
 * 
 * @param int $moduleId Module ID
 * @return array Array of activities
 */
function getPublishedActivities($moduleId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                a.*
            FROM 
                activities a
                JOIN modules m ON a.module_id = m.id
            WHERE 
                a.module_id = ? 
                AND a.is_published = 1
                AND m.is_published = 1
            ORDER BY 
                CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END, 
                a.due_date ASC, 
                a.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$moduleId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting published activities: ' . $e->getMessage());
        return [];
    }
}

/**
 * Fetch recent submissions for a student.
 *
 * @param int $studentId The ID of the student.
 * @param int $limit The number of recent submissions to fetch.
 * @return array An array of recent submissions.
 */
function getStudentRecentSubmissions($studentId, $limit = 5) {
    $pdo = getDbConnection(); // Use the existing database connection function
    if (!$pdo) {
        error_log('Database connection failed in getStudentRecentSubmissions');
        return [];
    }

    try {
        $query = "SELECT a.title AS activity_title, s.updated_at 
                  FROM submissions s
                  JOIN activities a ON s.activity_id = a.id
                  WHERE s.student_id = ?
                  ORDER BY s.updated_at DESC
                  LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching recent submissions: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get upcoming activity deadlines for a student
 * 
 * @param int $studentId Student ID
 * @param int $limit Maximum number of deadlines to return
 * @return array Array of upcoming deadlines
 */
function getUpcomingDeadlines($studentId, $limit = 5) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $currentDate = date('Y-m-d');
        
        $query = "
            SELECT 
                a.id, a.title, a.due_date, a.activity_type,
                m.id AS module_id, m.title AS module_title,
                c.id AS class_id,
                s.code AS subject_code, s.name AS subject_name
            FROM 
                activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_enrollments ce ON c.id = ce.class_id
            WHERE 
                ce.student_id = ? AND
                a.is_published = 1 AND
                m.is_published = 1 AND
                a.due_date IS NOT NULL AND
                a.due_date >= ?
            ORDER BY 
                a.due_date ASC
            LIMIT ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId, $currentDate, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting upcoming deadlines: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get upcoming deadlines for activities in a class for a specific student
 *
 * @param int $classId The class ID
 * @param int $studentId The student user ID
 * @param int $limit Maximum number of deadlines to return (optional)
 * @return array Array of activities with upcoming deadlines
 */
function getUpcomingDeadlinesByClass($classId, $studentId, $limit = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Get current date in MySQL format
        $currentDate = date('Y-m-d H:i:s');
        
        $limitClause = $limit ? "LIMIT " . (int)$limit : "";
        
        $query = "SELECT a.id, a.title, a.due_date, a.activity_type
                  FROM activities a
                  JOIN modules m ON a.module_id = m.id
                  LEFT JOIN activity_submissions s ON a.id = s.activity_id AND s.student_id = ?
                  WHERE m.class_id = ? 
                    AND a.is_published = 1
                    AND a.due_date IS NOT NULL
                    AND a.due_date > ?
                    AND (s.id IS NULL OR s.status != 'completed')
                  ORDER BY a.due_date ASC
                  $limitClause";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId, $classId, $currentDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting upcoming deadlines by class: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get appropriate icon for an activity type
 * 
 * @param string $activityType The type of activity
 * @return string CSS class for the appropriate icon
 */
function getActivityIcon($activityType) {
    switch ($activityType) {
        case 'assignment':
            return 'fas fa-file-alt';
        case 'quiz':
            return 'fas fa-question-circle';
        case 'coding':
            return 'fas fa-code';
        case 'lab':
            return 'fas fa-flask';
        default:
            return 'fas fa-tasks';
    }
}

/**
 * Get appropriate badge class for an activity type
 * 
 * @param string $activityType The type of activity
 * @return string Bootstrap badge color class
 */
function getActivityBadgeClass($activityType) {
    switch ($activityType) {
        case 'assignment':
            return 'primary';
        case 'quiz':
            return 'info';
        case 'coding':
            return 'success';
        case 'lab':
            return 'warning';
        default:
            return 'secondary';
    }
}

/**
 * Check if an activity is overdue
 * 
 * @param string|array $activity Activity array or due date string
 * @return bool True if activity is overdue, false otherwise
 */
function isActivityOverdue($activity) {
    // If we received the full activity array
    if (is_array($activity)) {
        // Check if due date exists
        if (empty($activity['due_date'])) {
            return false; // No due date means never overdue
        }
        $dueDate = $activity['due_date'];
    } else {
        // We received just the due date string
        $dueDate = $activity;
    }
    
    // Check if due date is in the past
    $dueTimestamp = strtotime($dueDate);
    $currentTimestamp = time();
    
    return ($dueTimestamp < $currentTimestamp);
}

/**
 * Get CSS class for due date display based on overdue status
 * 
 * @param string $dueDate Due date string
 * @return string CSS class for styling
 */
function getDueDateStatusClass($dueDate) {
    if (empty($dueDate)) {
        return 'text-muted'; // No due date
    }
    
    if (isActivityOverdue($dueDate)) {
        return 'text-danger fw-bold'; // Overdue - red and bold
    }
    
    // Check if due date is approaching (within 2 days)
    $dueTimestamp = strtotime($dueDate);
    $currentTimestamp = time();
    $twoDaysInSeconds = 2 * 24 * 60 * 60;
    
    if (($dueTimestamp - $currentTimestamp) < $twoDaysInSeconds) {
        return 'text-warning fw-bold'; // Approaching deadline - yellow and bold
    }
    
    return 'text-success'; // Due date is far away - green
}

/**
 * Get activity details for a student, ensuring they are enrolled in the class
 */
function getStudentActivity($activityId, $studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        error_log("Database connection failed in getStudentActivity");
        return null;
    }
    
    try {
        // First try to get the activity if the student is enrolled
        $stmt = $pdo->prepare("
            SELECT a.*, m.title as module_title, c.id as class_id
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            JOIN class_enrollments e ON c.id = e.class_id
            WHERE a.id = ? AND e.student_id = ? AND a.is_published = 1
        ");
        $stmt->execute([$activityId, $studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result;
        }
        
        // If no result and user is still trying to access, let's try to get 
        // just the activity info without enrollment check for better error messages
        $stmt = $pdo->prepare("
            SELECT a.*, m.title as module_title, c.id as class_id
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$activityId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getStudentActivity: " . $e->getMessage());
        return null;
    }
}

/**
 * Get a student's submission for an activity
 */
function getStudentSubmission($activityId, $studentId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT * FROM activity_submissions
        WHERE activity_id = ? AND student_id = ?
        ORDER BY submission_date DESC
        LIMIT 1
    ");
    $stmt->execute([$activityId, $studentId]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Submit code for an activity
 * 
 * @param int $activityId Activity ID
 * @param int $studentId Student ID
 * @param string $code Submitted code
 * @param string $language Language of the code
 * @return array Result with success flag and message
 */
function submitActivityCode($activityId, $studentId, $code, $language) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get activity details for validation
        $stmt = $pdo->prepare("
            SELECT a.*, m.class_id 
            FROM activities a 
            JOIN modules m ON a.module_id = m.id
            WHERE a.id = ?
        ");
        $stmt->execute([$activityId]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$activity) {
            return ['success' => false, 'message' => 'Activity not found'];
        }
        
        // Verify student is enrolled in the class
        $stmt = $pdo->prepare("
            SELECT id FROM class_enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$studentId, $activity['class_id']]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Student not enrolled in this class'];
        }
        
        // Check if there's an existing submission
        $stmt = $pdo->prepare("
            SELECT id FROM activity_submissions 
            WHERE activity_id = ? AND student_id = ?
        ");
        $stmt->execute([$activityId, $studentId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Run auto-grading if test cases are available
        $autoGrade = null;
        $testResults = null;
        $summary = null;
        
        if (!empty($activity['test_cases'])) {
            $gradingResult = autoGradeSubmission($code, $language, $activity['test_cases']);
            $autoGrade = $gradingResult['score'];
            $testResults = json_encode($gradingResult['results']);
            $summary = $gradingResult['summary'] ?? null;
        }
        
        if ($submission) {
            // Update existing submission - ensure we don't overwrite existing grade if already graded
            $stmt = $pdo->prepare("
                UPDATE activity_submissions 
                SET code = ?, 
                    language = ?, 
                    auto_grade = ?, 
                    test_results = ?, 
                    updated_at = CURRENT_TIMESTAMP,
                    graded = CASE WHEN graded = 1 THEN 1 ELSE 0 END
                WHERE id = ?
            ");
            $stmt->execute([$code, $language, $autoGrade, $testResults, $submission['id']]);
        } else {
            // Create new submission
            $stmt = $pdo->prepare("
                INSERT INTO activity_submissions 
                (activity_id, student_id, code, language, auto_grade, test_results, graded) 
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$activityId, $studentId, $code, $language, $autoGrade, $testResults]);
        }
        
        // Update student progress
        updateStudentProgress($studentId, $activityId);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Code submitted successfully',
            'auto_grade' => $autoGrade,
            'test_results' => json_decode($testResults, true),
            'summary' => $summary
        ];
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        error_log('Error submitting code: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error submitting code: ' . $e->getMessage()];
    }
}

/**
 * Auto-grade a submission based on test cases
 * 
 * @param string $code Student code submission
 * @param string $language Programming language
 * @param string $testCases Test cases in JSON format
 * @return array Results with score and details
 */
function autoGradeSubmission($code, $language, $testCases) {
    // Default result if no valid test cases
    $defaultResult = [
        'score' => null,
        'results' => []
    ];
    
    // Try to parse test cases
    try {
        require_once __DIR__ . '/../../tests/TestRunner.php';
        
        // If test cases provided, run them through our test framework
        if (!empty($testCases)) {
            // Map language to supported test types
            $testLanguage = $language;
            if ($language === 'javascript') $testLanguage = 'js';
            if ($language === 'html' || $language === 'css') {
                // For HTML/CSS, make sure we're using the right tester
                if (strpos($code, '<!DOCTYPE html') !== false || 
                    strpos($code, '<html') !== false) {
                    $testLanguage = 'html';
                } elseif (strpos($code, '{') !== false && 
                         strpos($code, '}') !== false) {
                    $testLanguage = 'css';
                }
            }
            
            // Run the tests using our TestRunner
            return \LabSpace\Tests\TestRunner::runTests($code, $testCases, $testLanguage);
        }
        
        return $defaultResult;
    } catch (Exception $e) {
        error_log('Error in auto-grading: ' . $e->getMessage());
        return $defaultResult;
    }
}

/**
 * Get submission statistics for an activity
 * 
 * @param int $activityId Activity ID 
 * @return array Statistics about submissions
 */
function getSubmissionStats($activityId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            'total' => 0,
            'graded' => 0,
            'average_score' => 0
        ];
    }
    
    try {
        // Get total submissions
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN graded = 1 THEN 1 END) as graded_submissions,
                AVG(CASE WHEN graded = 1 THEN grade ELSE null END) as average_grade,
                AVG(auto_grade) as average_auto_grade
            FROM activity_submissions
            WHERE activity_id = ?
        ");
        $stmt->execute([$activityId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total' => (int)($stats['total_submissions'] ?? 0),
            'graded' => (int)($stats['graded_submissions'] ?? 0),
            'average_score' => round(($stats['average_grade'] ?? 0), 1),
            'average_auto_grade' => round(($stats['average_auto_grade'] ?? 0), 1)
        ];
    } catch (PDOException $e) {
        error_log('Error getting submission stats: ' . $e->getMessage());
        return [
            'total' => 0,
            'graded' => 0,
            'average_score' => 0,
            'average_auto_grade' => 0
        ];
    }
}

/**
 * Get submission details for a student and activity
 *
 * @param int $activityId Activity ID
 * @param int $studentId Student ID
 * @return array|null Full submission details or null
 */
function getSubmissionDetails($activityId, $studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM activity_submissions
            WHERE activity_id = ? AND student_id = ?
            ORDER BY submission_date DESC
            LIMIT 1
        ");
        $stmt->execute([$activityId, $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting submission details: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get student progress data across enrolled classes
 * 
 * @param int $studentId Student ID
 * @return array Array of progress data by class and module
 */
function getStudentProgress($studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Get classes the student is enrolled in
        $query = "
            SELECT 
                c.id AS class_id,
                c.class_code,
                s.code AS subject_code,
                s.name AS subject_name,
                COUNT(DISTINCT a.id) AS total_activities,
                COUNT(DISTINCT sub.id) AS completed_activities
            FROM 
                class_enrollments ce
                JOIN classes c ON ce.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                LEFT JOIN modules m ON m.class_id = c.id AND m.is_published = 1
                LEFT JOIN activities a ON a.module_id = m.id AND a.is_published = 1
                LEFT JOIN activity_submissions sub ON sub.activity_id = a.id AND sub.student_id = ?
            WHERE 
                ce.student_id = ? AND
                c.is_active = 1
            GROUP BY 
                c.id, c.class_code, s.code, s.name
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
                ce.student_id = ? AND
                c.is_active = 1
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
        error_log('Error getting student progress: ' . $e->getMessage());
        return [
            'classes' => [],
            'modules' => []
        ];
    }
}

/**
 * Gets all submissions for a specific activity
 * 
 * @param int $activityId The ID of the activity
 * @return array|false Array of submissions or false on failure
 */
function getSubmissionsForActivity($activityId) {
    global $conn;
    
    $sql = "SELECT s.*, u.first_name, u.last_name, 
               CONCAT(u.first_name, ' ', u.last_name) AS student_name
            FROM activity_submissions s
            JOIN users u ON s.student_id = u.id
            WHERE s.activity_id = ?
            ORDER BY s.submission_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $submissions = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
    }
    
    $stmt->close();
    return $submissions;
}

/**
 * Update student progress after a submission
 * 
 * @param int $studentId Student ID
 * @param int $activityId Activity ID
 * @return bool True if progress updated successfully, false otherwise
 */
function updateStudentProgress($studentId, $activityId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }

    try {
        // Get the class ID and module ID from the activity
        $stmt = $pdo->prepare("
            SELECT m.class_id, a.module_id 
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            WHERE a.id = ?
        ");
        $stmt->execute([$activityId]);
        $activityData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activityData) {
            return false;
        }

        $classId = $activityData['class_id'];
        $moduleId = $activityData['module_id'];

        // Check if the student is enrolled in the class
        $stmt = $pdo->prepare("
            SELECT id 
            FROM class_enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$studentId, $classId]);
        if (!$stmt->fetch()) {
            return false;
        }

        // Update progress for the module
        $stmt = $pdo->prepare("
            INSERT INTO student_module_progress (student_id, module_id, class_id, last_updated)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_updated = NOW()
        ");
        $stmt->execute([$studentId, $moduleId, $classId]);

        return true;
    } catch (PDOException $e) {
        error_log('Error updating student progress: ' . $e->getMessage());
        return false;
    }
}
