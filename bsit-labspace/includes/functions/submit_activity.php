<?php
// Force content type to be JSON to prevent HTML errors
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

try {
    session_start();
    require_once '../db/config.php';
    require_once 'activity_functions.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("You must be logged in to submit code");
    }
    
    // Get input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate input
    if (!isset($data['activity_id']) || !isset($data['code'])) {
        throw new Exception("Missing required fields");
    }
    
    $activityId = (int)$data['activity_id'];
    $code = $data['code'];
    $language = isset($data['language']) ? $data['language'] : 'javascript';
    
    // Get activity details to verify access
    $activity = getActivityById($activityId);
    if (!$activity) {
        throw new Exception("Activity not found");
    }
    
    // Verify student enrollment
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT 1 FROM class_enrollments WHERE student_id = ? AND class_id = ?");
    $stmt->execute([$_SESSION['user_id'], $activity['class_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("You are not enrolled in this class");
    }
    
    // Check if submission already exists
    $stmt = $pdo->prepare("SELECT id FROM activity_submissions WHERE activity_id = ? AND student_id = ?");
    $stmt->execute([$activityId, $_SESSION['user_id']]);
    $existingSubmission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSubmission) {
        // Update existing submission
        $stmt = $pdo->prepare("UPDATE activity_submissions SET code = ?, language = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$code, $language, $existingSubmission['id']]);
    } else {
        // Create new submission
        $stmt = $pdo->prepare("INSERT INTO activity_submissions (activity_id, student_id, code, language, submission_date) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$activityId, $_SESSION['user_id'], $code, $language]);
    }
    
    // Update student progress after submission
    updateStudentProgress($_SESSION['user_id'], $activityId);
    
    // Clear buffer and return success
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Code submitted successfully'
    ]);
    
} catch (Exception $e) {
    // Clear buffer and return error
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    // Handle PHP errors
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $e->getMessage()
    ]);
}
?>
