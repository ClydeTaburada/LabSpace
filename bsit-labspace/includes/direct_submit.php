<?php
session_start();
require_once 'functions/auth.php';
require_once 'functions/activity_functions.php';
require_once 'db/config.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if this is a POST request with required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['activity_id']) || !isset($_POST['code'])) {
    header('Location: ../student/dashboard.php?error=Invalid%20request');
    exit;
}

$activityId = (int)$_POST['activity_id'];
$code = $_POST['code'];
$language = isset($_POST['language']) ? $_POST['language'] : 'javascript';

try {
    // Get activity details
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
    
    // Process submission - FIXED: Changed table from 'submissions' to 'activity_submissions'
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
    
    // Redirect back to activity with success message
    header("Location: ../student/view_activity.php?id=$activityId&submitted=1");
    exit;
    
} catch (Exception $e) {
    // Redirect with error
    header("Location: ../student/code_editor.php?id=$activityId&error=" . urlencode($e->getMessage()));
    exit;
}
?>
