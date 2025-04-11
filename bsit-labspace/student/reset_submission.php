<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/activity_functions.php';
require_once '../includes/db/config.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if activity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php?error=Invalid activity ID');
    exit;
}

$activityId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // First check if the activity exists and student has access to it
    $stmt = $pdo->prepare("
        SELECT a.id FROM activities a
        JOIN modules m ON a.module_id = m.id
        JOIN classes c ON m.class_id = c.id
        JOIN class_enrollments e ON c.id = e.class_id
        WHERE a.id = ? AND e.student_id = ? AND e.status = 'active'
    ");
    $stmt->execute([$activityId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        // Activity not found or student doesn't have access
        header('Location: dashboard.php?error=Activity not found or not accessible');
        exit;
    }
    
    // Check if there's a submission to delete
    $stmt = $pdo->prepare("
        SELECT id FROM activity_submissions 
        WHERE activity_id = ? AND student_id = ?
    ");
    $stmt->execute([$activityId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        // No submission found
        header("Location: view_activity.php?id=$activityId&error=No submission found to reset");
        exit;
    }
    
    // Delete the submission
    $stmt = $pdo->prepare("
        DELETE FROM activity_submissions 
        WHERE activity_id = ? AND student_id = ?
    ");
    $stmt->execute([$activityId, $userId]);
    
    // Also try to delete any local storage backup
    echo "<script>
        try {
            localStorage.removeItem('code_backup_$activityId');
            localStorage.removeItem('code_backup_time_$activityId');
        } catch (e) {
            console.error('Error removing local backup:', e);
        }
    </script>";
    
    // Redirect back with success message
    header("Location: view_activity.php?id=$activityId&success=Your submission has been reset successfully");
    exit;
    
} catch (PDOException $e) {
    // Log the error and redirect with error message
    error_log("Reset submission error: " . $e->getMessage());
    header("Location: view_activity.php?id=$activityId&error=Failed to reset submission. Please try again.");
    exit;
}
