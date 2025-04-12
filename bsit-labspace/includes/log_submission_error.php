<?php
/**
 * Utility file to log submission errors for debugging
 */

/**
 * Log submission errors to help diagnose issues
 * 
 * @param string $errorMessage The error message
 * @param int $activityId The activity ID
 * @param int $studentId The student ID
 * @param array $additionalInfo Additional debugging info
 * @return bool Whether logging was successful
 */
function logSubmissionError($errorMessage, $activityId, $studentId, $additionalInfo = []) {
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            error_log('Failed to create logs directory');
            return false;
        }
    }
    
    $logFile = $logDir . '/submission_errors.log';
    
    // Prepare log data
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $errorMessage,
        'activity_id' => $activityId,
        'student_id' => $studentId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'additional_info' => $additionalInfo
    ];
    
    // Format as JSON for easier parsing
    $logEntry = json_encode($logData) . PHP_EOL;
    
    // Write to log file
    if (file_put_contents($logFile, $logEntry, FILE_APPEND) === false) {
        error_log('Failed to write to submission error log');
        return false;
    }
    
    return true;
}

/**
 * Check the database tables to diagnose submission issues
 * 
 * @param int $activityId Activity ID
 * @param int $studentId Student ID
 * @return array Diagnostic information
 */
function diagnoseTables($activityId, $studentId) {
    try {
        require_once __DIR__ . '/db/config.php';
        $pdo = getDbConnection();
        
        $diagnostics = [];
        
        // Check activity_submissions table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_submissions WHERE activity_id = ? AND student_id = ?");
        $stmt->execute([$activityId, $studentId]);
        $diagnostics['activity_submissions_count'] = (int)$stmt->fetchColumn();
        
        // Check if submissions table exists and has data (to detect wrong table usage)
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE activity_id = ? AND student_id = ?");
            $stmt->execute([$activityId, $studentId]);
            $diagnostics['submissions_count'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $diagnostics['submissions_table_error'] = $e->getMessage();
        }
        
        // Check activity existence
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE id = ?");
        $stmt->execute([$activityId]);
        $diagnostics['activity_exists'] = (bool)$stmt->fetchColumn();
        
        // Check enrollment
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM class_enrollments e
            JOIN modules m ON m.class_id = e.class_id
            JOIN activities a ON a.module_id = m.id
            WHERE a.id = ? AND e.student_id = ?
        ");
        $stmt->execute([$activityId, $studentId]);
        $diagnostics['is_enrolled'] = (bool)$stmt->fetchColumn();
        
        return $diagnostics;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
