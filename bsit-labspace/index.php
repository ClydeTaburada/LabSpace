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

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-logo-wrapper">
            <div class="auth-logo">
                <i class="fas fa-laptop-code"></i> BSIT LabSpace
            </div>
            <div class="auth-tagline">Learning Management System</div>
        </div>
        
        <div class="auth-card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login to Your Account</h4>
            </div>
            <div class="card-body">
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <a href="forgot_password.php">Forgot your password?</a>
            </div>
        </div>

        <!-- Demo Accounts Information -->
        <div class="demo-accounts mt-4">
            <h6>Demo Accounts</h6>
            <div class="account-item">
                <span>Admin:</span>
                <span>admin@example.com / admin123</span>
            </div>
            <div class="account-item">
                <span>Teacher:</span>
                <span>teacher@example.com / teacher123</span>
            </div>
            <div class="account-item">
                <span>Student:</span>
                <span>student@example.com / student123</span>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>