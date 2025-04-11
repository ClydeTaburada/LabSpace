<?php
session_start();
require_once 'includes/db/config.php';
require_once 'includes/functions/auth.php';
require_once 'includes/functions/class_functions.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// Get form data
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$studentNumber = $_POST['student_number'] ?? '';
$yearLevel = $_POST['year_level'] ?? '';
$section = $_POST['section'] ?? '';
$classCode = $_POST['class_code'] ?? '';

// Basic validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || 
    empty($confirmPassword) || empty($studentNumber) || empty($yearLevel) || 
    empty($section) || empty($classCode)) {
    header('Location: register.php?error=All fields are required');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=Invalid email format');
    exit;
}

// Validate password strength
if (strlen($password) < 8) {
    header('Location: register.php?error=Password must be at least 8 characters long');
    exit;
}

// Check if passwords match
if ($password !== $confirmPassword) {
    header('Location: register.php?error=Passwords do not match');
    exit;
}

// Connect to database
$pdo = getDbConnection();
if (!$pdo) {
    header('Location: register.php?error=Database connection failed');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=Email already registered');
        exit;
    }
    
    // Check if student number already exists
    $stmt = $pdo->prepare("SELECT student_id FROM student_profiles WHERE student_number = ?");
    $stmt->execute([$studentNumber]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=Student number already registered');
        exit;
    }
    
    // Validate class code
    $class = getClassByCode($classCode);
    if (!$class) {
        header('Location: register.php?error=Invalid class code');
        exit;
    }
    
    // Check if class is active
    if (!$class['is_active']) {
        header('Location: register.php?error=This class is not active for enrollment');
        exit;
    }
    
    $classId = $class['id'];
    
    // Create user account
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, first_name, last_name, user_type)
        VALUES (?, ?, ?, ?, 'student')
    ");
    $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
    $userId = $pdo->lastInsertId();
    
    // Create student profile
    $stmt = $pdo->prepare("
        INSERT INTO student_profiles (student_id, year_level, section, student_number)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $yearLevel, $section, $studentNumber]);
    
    // Enroll student in the class
    $stmt = $pdo->prepare("
        INSERT INTO class_enrollments (class_id, student_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$classId, $userId]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect with success message
    $classInfo = htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']);
    header('Location: register.php?success=Registration successful! You have been enrolled in ' . $classInfo);
    exit;
    
} catch (PDOException $e) {
    // Roll back transaction on error
    $pdo->rollBack();
    error_log('Registration Error: ' . $e->getMessage());
    header('Location: register.php?error=Registration failed: ' . $e->getMessage());
    exit;
}
