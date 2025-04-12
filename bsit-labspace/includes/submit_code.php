<?php
/**
 * Handle code submission for activities
 */
session_start();
require_once 'functions/auth.php';
require_once 'functions/activity_functions.php';
require_once 'db/config.php';

// Only allow logged-in students to submit code
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set correct content type for all responses
header('Content-Type: application/json');

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if JSON was valid
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit;
}

// Check if necessary data is available
if (!isset($data['activity_id']) || !isset($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$activityId = (int)$data['activity_id'];
$code = $data['code'];
$language = $data['language'] ?? 'unknown';

try {
    // Verify student can access this activity
    $activity = getStudentActivity($activityId, $_SESSION['user_id']);
    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Activity not found or not accessible']);
        exit;
    }

    // Submit the code and run auto-grading
    $result = submitActivityCode($activityId, $_SESSION['user_id'], $code, $language);
    
    // Return JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Submission error: " . $e->getMessage());
    
    // Return a friendly error message
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while submitting your code. Please try again.', 
        'error_details' => $e->getMessage()
    ]);
}
