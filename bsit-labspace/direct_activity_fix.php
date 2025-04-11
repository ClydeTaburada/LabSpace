<?php
session_start();
require_once 'includes/functions.php';

// Get activity ID from GET parameter
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Check localStorage via AJAX
$checkLocal = isset($_GET['check_local']) && $_GET['check_local'] == '1';

// Determine user type for redirection
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Store the activity ID in session for security verification
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
    $_SESSION['activity_access_time'] = time();
    $_SESSION['activity_access_token'] = bin2hex(random_bytes(16)); 
}

// Determine destination path based on user type
$path = '';
switch ($userType) {
    case 'student':
        $path = 'student/view_activity.php';
        break;
    case 'teacher':
        $path = 'teacher/edit_activity.php';
        break;
    default:
        $path = 'direct_activity.php';
}

// If we have an activity ID, redirect immediately
if ($activityId) {
    header("Location: $path?id=$activityId&token={$_SESSION['activity_access_token']}&direct=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Activity Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-link-slash me-2"></i>Secure Activity Access</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p><i class="fas fa-info-circle me-2"></i>This page helps you access activities securely when normal navigation fails.</p>
                        </div>
                        
                        <form method="get" action="direct_activity_fix.php">
                            <div class="mb-3">
                                <label for="activity-id" class="form-label">Activity ID:</label>
                                <input type="number" id="activity-id" name="id" class="form-control form-control-lg" required autofocus>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Access Activity Securely</button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button id="check-local-storage" class="btn btn-warning">
                                <i class="fas fa-history me-2"></i>Recover from Last Activity
                            </button>
                            <a href="emergency_navigation.php" class="btn btn-outline-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Emergency Navigation
                            </a>
                            <?php if ($userType == 'student'): ?>
                                <a href="student/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Back to Dashboard
                                </a>
                            <?php elseif ($userType == 'teacher'): ?>
                                <a href="teacher/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Back to Dashboard
                                </a>
                            <?php else: ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Back to Home
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check local storage button
        document.getElementById('check-local-storage').addEventListener('click', function() {
            try {
                const activityId = localStorage.getItem('last_activity_id') || 
                                   sessionStorage.getItem('last_activity_id');
                                   
                if (activityId) {
                    console.log("Found activity ID in storage:", activityId);
                    window.location.href = `direct_activity_fix.php?id=${activityId}`;
                } else {
                    alert("No activity ID found in local storage");
                }
            } catch (e) {
                alert("Error accessing local storage: " + e.message);
                console.error(e);
            }
        });
        
        // Auto-check local storage if requested
        <?php if ($checkLocal): ?>
        document.getElementById('check-local-storage').click();
        <?php endif; ?>
    });
    </script>
</body>
</html>
