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

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if necessary data is available
if (!isset($data['activity_id']) || !isset($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$activityId = (int)$data['activity_id'];
$code = $data['code'];
$language = $data['language'] ?? 'unknown';

// Verify student can access this activity
$activity = getStudentActivity($activityId, $_SESSION['user_id']);
if (!$activity) {
    echo json_encode(['success' => false, 'message' => 'Activity not found or not accessible']);
    exit;
}

// Submit the code and run auto-grading
$result = submitActivityCode($activityId, $_SESSION['user_id'], $code, $language);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
