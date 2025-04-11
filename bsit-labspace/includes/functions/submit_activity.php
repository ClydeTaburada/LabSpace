<?php
session_start();
require_once '../database.php';
require_once 'auth.php';
require_once 'activity_functions.php';

// Ensure this script only handles POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Ensure user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Verify required fields are present
if (!isset($data['activity_id']) || !isset($data['code'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$activityId = (int)$data['activity_id'];
$code = $data['code'];
$language = isset($data['language']) ? $data['language'] : 'javascript';
$userId = $_SESSION['user_id'];

// Verify user has access to this activity
$activity = getStudentActivity($activityId, $userId);
if (!$activity) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have access to this activity']);
    exit;
}

try {
    // Connect to database
    $db = getDbConnection();
    
    // Check if a submission already exists
    $stmt = $db->prepare("SELECT * FROM submissions WHERE activity_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $activityId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing submission
        $stmt = $db->prepare("UPDATE submissions SET code = ?, language = ?, submission_date = NOW(), status = 'submitted' WHERE activity_id = ? AND student_id = ?");
        $stmt->bind_param("ssii", $code, $language, $activityId, $userId);
    } else {
        // Create new submission
        $stmt = $db->prepare("INSERT INTO submissions (activity_id, student_id, code, language, submission_date, status) VALUES (?, ?, ?, ?, NOW(), 'submitted')");
        $stmt->bind_param("iiss", $activityId, $userId, $code, $language);
    }
    
    if ($stmt->execute()) {
        // Record the activity for analytics
        recordStudentAction($userId, 'submission', $activityId);
        
        // Return success response
        echo json_encode(['success' => true, 'message' => 'Code submitted successfully']);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to submit code: ' . $e->getMessage()]);
}
?>
