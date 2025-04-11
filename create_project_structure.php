<?php
/**
 * Script to create the BSIT-LabSpace project structure
 * Run this file once to set up the directory structure
 */

// Define the project root directory
$projectRoot = __DIR__ . '/bsit-labspace';

// Define all directories to create
$directories = [
    '',                     // Root directory
    '/admin',               // Admin area
    '/teacher',             // Teacher area
    '/student',             // Student area
    '/classes',             // Classes management
    '/modules',             // Learning modules
    '/activities',          // Learning activities
    '/submissions',         // Student submissions
    '/assets',              // Static assets
    '/assets/css',          // CSS files
    '/assets/js',           // JavaScript files
    '/assets/images',       // Image files
    '/includes',            // Includes (shared code)
    '/includes/db',         // Database related files
    '/includes/functions',  // Helper functions
    '/tests'                // Unit tests
];

// Core files to create (path => content)
$files = [
    '/index.php' => <<<'EOT'
<?php
// Main entry point for the application
session_start();
require_once 'includes/db/config.php';
require_once 'includes/functions/auth.php';

// Redirect to appropriate dashboard if logged in
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'] ?? '';
    
    switch ($userType) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            // If user type is invalid, destroy session and reload
            session_destroy();
            header('Location: index.php');
    }
    exit;
}

// Otherwise show the login page
$pageTitle = "BSIT LabSpace - Login";
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">BSIT LabSpace - Login</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_type" class="form-label">Login As</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">-- Select Role --</option>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
EOT,

    '/login.php' => <<<'EOT'
<?php
// Process login form
session_start();
require_once 'includes/db/config.php';
require_once 'includes/functions/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password) || empty($userType)) {
        header('Location: index.php?error=All fields are required');
        exit;
    }
    
    // Process login based on user type
    $result = loginUser($email, $password, $userType);
    
    if ($result['success']) {
        // Set session variables
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_name'] = $result['user_name'];
        $_SESSION['user_type'] = $userType;
        
        // Redirect to appropriate dashboard
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
EOT,

    '/logout.php' => <<<'EOT'
<?php
// Logout user and destroy session
session_start();
session_destroy();
header('Location: index.php');
exit;
EOT,

    '/includes/header.php' => <<<'EOT'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BSIT LabSpace'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>">BSIT LabSpace</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav">
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/manage_users.php">Users</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/manage_classes.php">Classes</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>teacher/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>teacher/classes.php">Classes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>teacher/activities.php">Activities</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>student/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>student/classes.php">My Classes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>student/activities.php">Activities</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['user_name'] ?? 'User'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo getBaseUrl(); ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="main-content">
EOT,

    '/includes/footer.php' => <<<'EOT'
    </div><!-- /.main-content -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> BSIT LabSpace. All rights reserved.</span>
        </div>
    </footer>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/script.js"></script>
</body>
</html>
EOT,

    '/includes/db/config.php' => <<<'EOT'
<?php
/**
 * Database Configuration
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'bsit_labspace');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get database connection
 * @return PDO|null PDO object if connection successful, null otherwise
 */
function getDbConnection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database Connection Error: ' . $e->getMessage());
        return null;
    }
}
EOT,

    '/includes/db/schema.sql' => <<<'EOT'
-- BSIT LabSpace Database Schema

-- Create users table with common fields
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'teacher', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create student profiles table
CREATE TABLE student_profiles (
    student_id INT PRIMARY KEY,
    year_level INT NOT NULL,
    section VARCHAR(10),
    student_number VARCHAR(20) UNIQUE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create teacher profiles table
CREATE TABLE teacher_profiles (
    teacher_id INT PRIMARY KEY,
    department VARCHAR(50),
    employee_id VARCHAR(20) UNIQUE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_programming BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    section VARCHAR(20) NOT NULL,
    school_year VARCHAR(9) NOT NULL,
    semester INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Create class enrollments table
CREATE TABLE class_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Create modules table
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    publish_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Create activities table
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    activity_type ENUM('assignment', 'quiz', 'coding', 'lab') NOT NULL,
    instructions TEXT,
    max_score INT DEFAULT 100,
    coding_starter_code TEXT,
    test_cases TEXT,
    due_date TIMESTAMP NULL,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id)
);

-- Create submissions table
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('code', 'file', 'text') NOT NULL,
    content TEXT,
    file_path VARCHAR(255),
    score DECIMAL(5,2),
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    graded_by INT,
    FOREIGN KEY (activity_id) REFERENCES activities(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (graded_by) REFERENCES users(id)
);
EOT,

    '/includes/functions/auth.php' => <<<'EOT'
<?php
/**
 * Authentication related functions
 */

/**
 * Log in a user
 * @param string $email User email
 * @param string $password User password
 * @param string $userType User type (admin, teacher, student)
 * @return array Result with success flag and message or user data
 */
function loginUser($email, $password, $userType) {
    // For demo purposes, we'll just create a mock login system
    // In a real application, you would validate against the database
    
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, password FROM users WHERE email = :email AND user_type = :user_type");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_type', $userType);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials or user type'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        return [
            'success' => true, 
            'user_id' => $user['id'],
            'user_name' => $user['first_name'] . ' ' . $user['last_name']
        ];
        
    } catch (PDOException $e) {
        error_log('Login Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed due to system error'];
    }
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * @param string|array $roles Single role or array of roles
 * @return bool True if user has one of the specified roles
 */
function hasRole($roles) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_type'], $roles);
    }
    
    return $_SESSION['user_type'] == $roles;
}

/**
 * Ensure user is logged in, redirect to login otherwise
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'index.php?error=Please log in to continue');
        exit;
    }
}

/**
 * Ensure user has a specific role, redirect otherwise
 * @param string|array $roles Required role(s)
 * @param string $redirect URL to redirect to if check fails
 */
function requireRole($roles, $redirect = null) {
    requireLogin();
    
    if (!hasRole($roles)) {
        if (!$redirect) {
            $redirect = getBaseUrl() . 'index.php?error=Access denied';
        }
        
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Get base URL for the application
 * @return string Base URL
 */
function getBaseUrl() {
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = $dir == '/' ? '' : $dir;
    return 'http://' . $_SERVER['HTTP_HOST'] . $dir . '/';
}
EOT,

    '/assets/css/style.css' => <<<'EOT'
/* Custom styles for BSIT LabSpace */

body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1;
}

.navbar-brand {
    font-weight: bold;
}

.card-dashboard {
    transition: transform 0.3s;
}

.card-dashboard:hover {
    transform: translateY(-5px);
}

/* Activity types */
.badge.activity-coding {
    background-color: #28a745;
}

.badge.activity-quiz {
    background-color: #007bff;
}

.badge.activity-lab {
    background-color: #6f42c1;
}

.badge.activity-assignment {
    background-color: #fd7e14;
}

/* Code editor styling */
.code-editor {
    height: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Submission status */
.submission-status-pending {
    color: #fd7e14;
}

.submission-status-graded {
    color: #28a745;
}

.submission-status-late {
    color: #dc3545;
}
EOT,

    '/assets/js/script.js' => <<<'EOT'
// Main JavaScript file for BSIT LabSpace

document.addEventListener('DOMContentLoaded', function() {
    // Enable all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Enable all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add fade effect for alerts with auto-close
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-auto-close');
        alerts.forEach(function(alert) {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
});
EOT,

    '/admin/dashboard.php' => <<<'EOT'
<?php
session_start();
require_once '../includes/functions/auth.php';

// Check if user is logged in and is an admin
requireRole('admin');

$pageTitle = "Admin Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Admin Dashboard</h1>
    <p class="lead">Welcome to BSIT LabSpace, <?php echo $_SESSION['user_name']; ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users"></i> User Management</h5>
                    <p class="card-text">Add, edit, or remove users including teachers and students.</p>
                    <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chalkboard"></i> Classes Management</h5>
                    <p class="card-text">Manage classes, subjects, and teacher assignments.</p>
                    <a href="manage_classes.php" class="btn btn-primary">Manage Classes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-cogs"></i> System Settings</h5>
                    <p class="card-text">Configure system settings and parameters.</p>
                    <a href="system_settings.php" class="btn btn-primary">System Settings</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-2">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No recent activities yet.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <p><strong>Current Version:</strong> 1.0.0</p>
                    <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                    <p><strong>Last Backup:</strong> Not available</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
EOT,

    '/teacher/dashboard.php' => <<<'EOT'
<?php
session_start();
require_once '../includes/functions/auth.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

$pageTitle = "Teacher Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Teacher Dashboard</h1>
    <p class="lead">Welcome to BSIT LabSpace, <?php echo $_SESSION['user_name']; ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chalkboard"></i> My Classes</h5>
                    <p class="card-text">Manage your classes and student enrollments.</p>
                    <a href="classes.php" class="btn btn-primary">View Classes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tasks"></i> Activities</h5>
                    <p class="card-text">Create and manage assignments, quizzes, and coding tasks.</p>
                    <a href="activities.php" class="btn btn-primary">Manage Activities</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Student Progress</h5>
                    <p class="card-text">View and evaluate student submissions and progress.</p>
                    <a href="student_progress.php" class="btn btn-primary">View Progress</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-2">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No recent submissions yet.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Upcoming Due Dates</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No upcoming due dates.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
EOT,

    '/student/dashboard.php' => <<<'EOT'
<?php
session_start();
require_once '../includes/functions/auth.php';

// Check if user is logged in and is a student
requireRole('student');

$pageTitle = "Student Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Student Dashboard</h1>
    <p class="lead">Welcome to BSIT LabSpace, <?php echo $_SESSION['user_name']; ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book-open"></i> My Classes</h5>
                    <p class="card-text">View your enrolled classes and learning materials.</p>
                    <a href="classes.php" class="btn btn-primary">View Classes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tasks"></i> Activities</h5>
                    <p class="card-text">Access assignments, quizzes, and coding tasks.</p>
                    <a href="activities.php" class="btn btn-primary">View Activities</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Progress</h5>
                    <p class="card-text">Track your academic progress and view feedback.</p>
                    <a href="progress.php" class="btn btn-primary">View Progress</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-2">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Upcoming Deadlines</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No upcoming deadlines.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Grades</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No recent grades.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
EOT,

    '/README.md' => <<<'EOT'
# BSIT-LabSpace

A laboratory management system for BSIT students.

## Project Structure

```
bsit-labspace/
├── admin/              # Admin area
├── activities/         # Learning activities
├── assets/             # Static assets
│   ├── css/            # CSS files
│   ├── images/         # Image files
│   └── js/             # JavaScript files
├── classes/            # Classes management
├── includes/           # Includes (shared code)
│   ├── db/             # Database related files
│   └── functions/      # Helper functions
├── modules/            # Learning modules
├── student/            # Student area
├── submissions/        # Student submissions
├── teacher/            # Teacher area
├── tests/              # Unit tests
└── index.php           # Entry point
```

## Getting Started

1. Clone this repository into your XAMPP htdocs folder
2. Create a new MySQL database named "bsit_labspace"
3. Import the database schema from `includes/db/schema.sql`
4. Configure database connection in `includes/db/config.php`
5. Access the application at http://localhost/bsit-labspace/

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or equivalent)

## Features

- User management with role-based access control
- Class management for teachers
- Assignment and activity management
- Code execution environment for programming tasks
- Student progress tracking
EOT,

    '/setup.php' => <<<'EOT'
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
    
    // Create sample teacher user
    $hashedTeacherPassword = password_hash('teacher123', PASSWORD_DEFAULT);
    $stmt->execute(['teacher@example.com', $hashedTeacherPassword, 'John', 'Doe', 'teacher']);
    
    // Insert teacher profile
    $teacherId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO teacher_profiles (teacher_id, department, employee_id) VALUES (?, ?, ?)");
    $stmt->execute([$teacherId, 'Information Technology', 'EMP001']);
    
    echo '<h3>Sample teacher user created:</h3>';
    echo '<p>Email: teacher@example.com<br>Password: teacher123</p>';
    
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
    
    echo '<p><a href="index.php" class="btn btn-primary">Go to Login Page</a></p>';
    
} catch (PDOException $e) {
    die('<h2>Error seeding initial data:</h2><p>' . $e->getMessage() . '</p>');
}
EOT
];

// Function to create directory
function createDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            echo "Directory created: $path<br>";
            return true;
        } else {
            echo "Failed to create directory: $path<br>";
            return false;
        }
    } else {
        echo "Directory already exists: $path<br>";
        return true;
    }
}

// Function to create file
function createFile($path, $content) {
    if (!file_exists($path)) {
        if (file_put_contents($path, $content)) {
            echo "File created: $path<br>";
            return true;
        } else {
            echo "Failed to create file: $path<br>";
            return false;
        }
    } else {
        echo "File already exists: $path<br>";
        return true;
    }
}

// Create the project structure
echo "<h2>Creating BSIT-LabSpace Project Structure</h2>";

// Create main project directory
if (!createDirectory($projectRoot)) {
    die("Failed to create project directory. Check permissions and try again.");
}

// Create all directories
foreach ($directories as $dir) {
    createDirectory($projectRoot . $dir);
}

// Create all files
foreach ($files as $filePath => $fileContent) {
    createFile($projectRoot . $filePath, $fileContent);
}

echo "<h2>Project Structure Created Successfully!</h2>";
echo "<p>Your new BSIT-LabSpace project is now ready at: <strong>" . $projectRoot . "</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Start your XAMPP Apache and MySQL services</li>";
echo "<li>Create a MySQL database named 'bsit_labspace'</li>";
echo "<li>Run the <a href='bsit-labspace/setup.php'>setup.php</a> script to initialize the database</li>";
echo "<li>Access the application at <a href='bsit-labspace/index.php'>bsit-labspace/index.php</a></li>";
echo "</ol>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h2 { color: #0066cc; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
    code, pre { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
</style>

<p><strong>Note:</strong> This script has created the project structure only. You still need to set up the database and configure your web server.</p>
