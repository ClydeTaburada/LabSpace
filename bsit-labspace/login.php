<?php
// Process login form
session_start();
require_once 'includes/db/config.php';
require_once 'includes/functions/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        header('Location: index.php?error=Email and password are required');
        exit;
    }
    
    // Process login
    $result = loginUser($email, $password);
    
    if ($result['success']) {
        // Set session variables
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_name'] = $result['user_name'];
        $_SESSION['user_type'] = $result['user_type'];
        $_SESSION['force_password_change'] = $result['force_password_change'];
        
        // Check if password change is required for teachers
        if ($result['user_type'] == 'teacher' && $result['force_password_change']) {
            header("Location: teacher/change_password.php");
            exit;
        }
        
        // Redirect to appropriate dashboard
        $userType = $result['user_type'];
        header("Location: {$userType}/dashboard.php");
        exit;
    } else {
        header('Location: index.php?error=' . urlencode($result['message']));
        exit;
    }
}

// If not POST request, redirect to index
header('Location: index.php');
exit;