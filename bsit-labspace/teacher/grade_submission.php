<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Process form submission for grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {
    $submissionId = $_POST['submission_id'] ?? 0;
    $grade = $_POST['grade'] ?? 0;
    $feedback = $_POST['feedback'] ?? '';
    $returnUrl = $_POST['return_url'] ?? 'classes.php';
    
    // Simple validation
    if ($submissionId <= 0) {
        header("Location: $returnUrl?error=Invalid submission selected");
        exit;
    } elseif ($grade < 0 || $grade > 100) {
        header("Location: $returnUrl?error=Grade must be between 0 and 100");
        exit;
    } else {
        // Update the submission with grade and feedback
        $pdo = getDbConnection();
        
        // First check if this submission belongs to a class taught by this teacher
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM activity_submissions s
            JOIN activities a ON s.activity_id = a.id
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            WHERE s.id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$submissionId, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            header("Location: $returnUrl?error=Unauthorized access");
            exit;
        }
        
        // Update the submission
        $stmt = $pdo->prepare("
            UPDATE activity_submissions 
            SET grade = ?, feedback = ?, graded = 1
            WHERE id = ?
        ");
        $stmt->execute([$grade, $feedback, $submissionId]);
        
        header("Location: $returnUrl?success=Submission graded successfully");
        exit;
    }
} else {
    // Redirect if not a POST request
    header('Location: classes.php');
    exit;
}
