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
            session_destroy();
            header('Location: index.php');
    }
    exit;
}

// Otherwise show the login page
$pageTitle = "BSIT LabSpace - Login";
include 'includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-header text-center">
            <img src="assets/images/logo-0630.png" alt="BSIT LabSpace Logo" class="login-logo">
            <h1 class="login-title">Welcome to BSIT LabSpace</h1>
            <p class="login-subtitle">Your Learning Management System</p>
        </div>
        <div class="login-card">
            <form action="login.php" method="post">
                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="text-decoration-none">Forgot your password?</a>
            </div>
        </div>
        <div class="demo-accounts text-center mt-4">
            <h6>Demo Accounts</h6>
            <p><strong>Admin:</strong> admin@example.com / admin123</p>
            <p><strong>Teacher:</strong> teacher@example.com / teacher123</p>
            <p><strong>Student:</strong> student@example.com / student123</p>
        </div>
    </div>
</div>

<style>
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: url('assets/images/background.jpg') no-repeat center center fixed;
        background-size: cover;
        color: #333;
    }
    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }
    .login-container {
        background: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 100%;
    }
    .login-header {
        margin-bottom: 20px;
    }
    .login-logo {
        max-width: 100px;
        margin-bottom: 10px;
    }
    .login-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .login-subtitle {
        font-size: 0.9rem;
        color: #666;
    }
    .login-card {
        margin-bottom: 20px;
    }
    .form-label {
        font-weight: 500;
    }
    .btn-primary {
        background-color: #007bff;
        border: none;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    .demo-accounts {
        font-size: 0.9rem;
        color: #555;
        display: none;
    }
</style>

