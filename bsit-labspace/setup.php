<?php
/**
 * Setup script to initialize the database
 * Run this file once to create tables and seed initial data
 */
require_once 'includes/db/config.php';

$pdo = getDbConnection();
if (!$pdo) {
    die('Could not connect to the database. Please check your database configuration.');
}

// Read and execute the SQL schema
try {
    $sql = file_get_contents('includes/db/schema.sql');
    $pdo->exec($sql);
    echo '<h2>Database tables created successfully!</h2>';
} catch (PDOException $e) {
    die('<h2>Error creating database tables:</h2><p>' . $e->getMessage() . '</p>');
}

// Create default admin user
try {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin@example.com', $hashedPassword, 'System', 'Administrator', 'admin']);
    
    echo '<h3>Default admin user created:</h3>';
    echo '<p>Email: admin@example.com<br>Password: admin123</p>';
    
    // Create sample teacher user with force_password_change flag set to TRUE
    $hashedTeacherPassword = password_hash('teacher123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, user_type, force_password_change) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['teacher@example.com', $hashedTeacherPassword, 'John', 'Doe', 'teacher', true]);
    
    // Insert teacher profile
    $teacherId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO teacher_profiles (teacher_id, department, employee_id) VALUES (?, ?, ?)");
    $stmt->execute([$teacherId, 'Information Technology', 'EMP001']);
    
    echo '<h3>Sample teacher user created:</h3>';
    echo '<p>Email: teacher@example.com<br>Password: teacher123 (change required on first login)</p>';
    
    // Create sample student user
    $hashedStudentPassword = password_hash('student123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['student@example.com', $hashedStudentPassword, 'Jane', 'Smith', 'student']);
    
    // Insert student profile
    $studentId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO student_profiles (student_id, year_level, section, student_number) VALUES (?, ?, ?, ?)");
    $stmt->execute([$studentId, 2, 'A', '2023-12345']);
    
    echo '<h3>Sample student user created:</h3>';
    echo '<p>Email: student@example.com<br>Password: student123</p>';
    
    // Create sample subjects
    $subjects = [
        ['CS101', 'Introduction to Programming', 'Basic programming concepts and problem solving', true],
        ['CS102', 'Advanced Programming', 'Object-oriented programming and design patterns', true],
        ['CS201', 'Data Structures', 'Fundamental data structures and algorithms', true],
        ['IT101', 'Introduction to IT', 'Overview of information technology and its applications', false],
        ['IT205', 'Web Development', 'Client-side and server-side web development', true]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO subjects (code, name, description, is_programming) VALUES (?, ?, ?, ?)");
    foreach ($subjects as $subject) {
        $stmt->execute($subject);
    }
    
    echo '<h3>Sample subjects created successfully!</h3>';
    
    // Create sample classes with enrollment codes for registration testing
    $classes = [
        [1, $teacherId, 'CS101-2A-23XYZ', '2A', 2, '2023-2024', 1, true],  // Intro to Programming
        [2, $teacherId, 'CS102-2B-23ABC', '2B', 2, '2023-2024', 1, true],  // Advanced Programming
        [5, $teacherId, 'WEB205-3A-23DEF', '3A', 3, '2023-2024', 1, true], // Web Development
    ];
    
    $stmt = $pdo->prepare("INSERT INTO classes (subject_id, teacher_id, class_code, section, year_level, school_year, semester, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($classes as $class) {
        $stmt->execute($class);
    }
    
    // Enroll sample student in a class
    $stmt = $pdo->prepare("INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)");
    $stmt->execute([1, $studentId]); // Enroll in Intro to Programming
    
    echo '<h3>Sample classes created successfully!</h3>';
    echo '<p>Available class codes for registration:</p>';
    echo '<ul>';
    foreach ($classes as $class) {
        echo '<li><strong>' . $class[2] . '</strong> - Section ' . $class[3] . '</li>';
    }
    echo '</ul>';
    
    echo '<p><a href="index.php" class="btn btn-primary">Go to Login Page</a></p>';
    
} catch (PDOException $e) {
    die('<h2>Error seeding initial data:</h2><p>' . $e->getMessage() . '</p>');
}