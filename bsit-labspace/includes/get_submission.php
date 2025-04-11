<?php
/**
 * AJAX handler for fetching submission details
 */
session_start();
require_once 'functions/auth.php';
require_once 'db/config.php';

// Only allow logged in teachers or students
if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['teacher', 'student'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if submission ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

$submissionId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

try {
    $pdo = getDbConnection();
    
    // Get submission details with appropriate access control
    $query = "
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
            s.id = ?
    ";
    
    // Add condition based on user type
    if ($userType === 'teacher') {
        $query .= " AND c.teacher_id = ?";
        $params = [$submissionId, $userId];
    } else { // student
        $query .= " AND s.student_id = ?";
        $params = [$submissionId, $userId];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
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
            'language' => $submission['language'],
            'auto_grade' => $submission['auto_grade'],
            'test_results' => $submission['test_results'],
            'grade' => $submission['grade'],
            'graded' => $submission['graded'],
            'feedback' => htmlspecialchars($submission['feedback'] ?? '', ENT_QUOTES, 'UTF-8')
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching submission: ' . $e->getMessage()]);
}
