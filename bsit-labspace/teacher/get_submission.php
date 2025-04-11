<?php
/**
 * AJAX handler for fetching submission details
 */
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/db/config.php';

// Only allow logged in teachers
requireRole('teacher');

// Check if submission ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

$submissionId = (int)$_GET['id'];
$teacherId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Get submission details with verification that it belongs to teacher's activity
    $stmt = $pdo->prepare("
        SELECT 
            s.*, 
            u.first_name, 
            u.last_name,
            CONCAT(u.first_name, ' ', u.last_name) AS student_name,
            DATE_FORMAT(s.submission_date, '%M %d, %Y %h:%i %p') as formatted_date,
            p.student_number
        FROM 
            activity_submissions s
            JOIN users u ON s.student_id = u.id
            JOIN student_profiles p ON u.id = p.student_id
            JOIN activities a ON s.activity_id = a.id
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
        WHERE 
            s.id = ? AND c.teacher_id = ?
    ");
    
    $stmt->execute([$submissionId, $teacherId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Submission not found or unauthorized access']);
        exit;
    }
    
    // Prepare data for response
    $response = [
        'success' => true,
        'submission' => [
            'id' => $submission['id'],
            'student_id' => $submission['student_id'],
            'student_name' => $submission['student_name'],
            'student_number' => $submission['student_number'],
            'submission_date' => $submission['formatted_date'],
            'code' => htmlspecialchars($submission['code'], ENT_QUOTES, 'UTF-8'),
            'language' => ucfirst($submission['language']),
            'auto_grade' => $submission['auto_grade'],
            'test_results' => $submission['test_results'],
            'graded' => (bool)$submission['graded'],
            'grade' => $submission['grade'],
            'feedback' => $submission['feedback']
        ]
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
