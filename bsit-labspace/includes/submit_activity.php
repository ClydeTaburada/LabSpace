<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

// Wrap everything in a try-catch to ensure we always return JSON
try {
    session_start();
    require_once 'functions/auth.php';
    require_once 'functions/activity_functions.php';
    require_once 'db/config.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to submit an activity");
    }
    
    // Get input from POST or from JSON request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Fallback to $_POST if JSON parsing fails
    if ($data === null) {
        $data = $_POST;
    }
    
    // Check if required fields are present
    if (!isset($data['activity_id']) || !isset($data['code'])) {
        throw new Exception("Missing required fields");
    }
    
    $activityId = (int)$data['activity_id'];
    $code = $data['code'];
    $language = isset($data['language']) ? $data['language'] : 'javascript';
    
    // Get activity details
    $activity = getActivityById($activityId);
    
    // Check if activity exists
    if (!$activity) {
        throw new Exception("Activity not found");
    }
    
    // Check if student is enrolled in the class (you should implement this function)
    if (!isStudentEnrolledInClass($_SESSION['user_id'], $activity['class_id'])) {
        throw new Exception("You are not enrolled in this class");
    }
    
    // Submit the code
    $result = submitActivityCode($activityId, $_SESSION['user_id'], $code, $language);
    
    if ($result) {
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Your code has been submitted successfully',
            'activity_id' => $activityId
        ]);
    } else {
        throw new Exception("Failed to submit code");
    }
} catch (Exception $e) {
    // Discard any output before the error
    ob_clean();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();

/**
 * Check if a student is enrolled in a class
 * You should implement this function based on your database structure
 */
function isStudentEnrolledInClass($studentId, $classId) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND class_id = ?");
        $stmt->execute([$studentId, $classId]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        // Log the error but don't expose database details
        error_log("Error checking enrollment: " . $e->getMessage());
        return false;
    }
}

/**
 * Submit activity code to database
 */
function submitActivityCode($activityId, $studentId, $code, $language) {
    try {
        $pdo = getDbConnection();
        
        // Check if submission already exists
        $stmt = $pdo->prepare("SELECT id FROM submissions WHERE activity_id = ? AND student_id = ?");
        $stmt->execute([$activityId, $studentId]);
        $existingSubmission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingSubmission) {
            // Update existing submission
            $stmt = $pdo->prepare("UPDATE submissions SET code = ?, language = ?, submitted_at = NOW() WHERE id = ?");
            return $stmt->execute([$code, $language, $existingSubmission['id']]);
        } else {
            // Create new submission
            $stmt = $pdo->prepare("INSERT INTO submissions (activity_id, student_id, code, language, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            return $stmt->execute([$activityId, $studentId, $code, $language]);
        }
    } catch (PDOException $e) {
        // Log the error but don't expose database details
        error_log("Error submitting code: " . $e->getMessage());
        return false;
    }
}
?>
