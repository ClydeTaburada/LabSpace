<?php
/**
 * Enhanced submission handler for student activities
 */
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/activity_functions.php';
require_once '../includes/db/config.php';

// Set correct content type for all responses
header('Content-Type: application/json');

// Only allow logged-in students
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get the request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    // Check required fields
    if (!isset($data['activity_id']) || !isset($data['code'])) {
        throw new Exception('Missing required parameters (activity_id or code)');
    }
    
    $activityId = (int)$data['activity_id'];
    $code = $data['code'] ?? '';
    $language = $data['language'] ?? 'unknown';
    
    // Verify student can access this activity
    $activity = getStudentActivity($activityId, $_SESSION['user_id']);
    if (!$activity) {
        throw new Exception('Activity not found or not accessible');
    }
    
    // Perform the submission
    $result = submitActivityCode($activityId, $_SESSION['user_id'], $code, $language);
    
    // Return success response
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Submission error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}
