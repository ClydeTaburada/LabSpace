<?php
/**
 * Direct Activity Recovery Page
 * This is a minimal implementation to recover activity sessions when normal navigation fails
 */

session_start();

// Database connection function - ultra simple implementation
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'bsit_labspace';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        return null; // Silent fail for emergency system
    }
}

// Get activity ID from various sources
$activityId = null;

// Check query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $activityId = (int)$_GET['id'];
}

// Check localStorage (via POST from JavaScript)
if (isset($_POST['localStorage']) && is_numeric($_POST['localStorage'])) {
    $activityId = (int)$_POST['localStorage'];
}

// Check session storage
if (!$activityId && isset($_SESSION['last_activity_id']) && is_numeric($_SESSION['last_activity_id'])) {
    $activityId = (int)$_SESSION['last_activity_id'];
}

// Create the security token for verification
$token = bin2hex(random_bytes(16));
$_SESSION['activity_access_token'] = $token;
$_SESSION['activity_access_time'] = time();

// Try to get activity details
$activity = null;
$error = '';

if ($activityId) {
    try {
        $pdo = getDbConnection();
        if ($pdo) {
            // Get activity details
            $stmt = $pdo->prepare("
                SELECT a.*, m.class_id, m.title as module_title 
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                WHERE a.id = ?
            ");
            $stmt->execute([$activityId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Store in session for future recovery
            if ($activity) {
                $_SESSION['last_activity_id'] = $activityId;
            }
        } else {
            $error = "Database connection failed";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get user type for proper redirection
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// Determine where to redirect
$redirectUrl = '';
if ($activity) {
    if ($userType == 'student') {
        $redirectUrl = "student/view_activity.php?id={$activityId}&token={$token}&recovered=1";
    } elseif ($userType == 'teacher') {
        $redirectUrl = "teacher/view_activity.php?id={$activityId}&token={$token}&recovered=1";
    } else {
        $redirectUrl = "direct_activity.php?id={$activityId}&token={$token}";
    }
}

// If we have a redirect URL, redirect immediately
if ($redirectUrl) {
    header("Location: $redirectUrl");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Recovery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .recovery-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .error-container {
            background-color: #f8d7da;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <h1 class="mb-4"><i class="fas fa-life-ring me-2"></i>Activity Recovery</h1>
        
        <?php if (!$activityId): ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>No Activity ID Found</h4>
                <p>Please enter an activity ID to access:</p>
                <form method="get" action="direct_activity_recovery.php" class="mt-3">
                    <div class="input-group mb-3">
                        <input type="number" name="id" class="form-control" placeholder="Enter Activity ID" required>
                        <button type="submit" class="btn btn-primary">Access Activity</button>
                    </div>
                </form>
                
                <div class="mt-4">
                    <h5>Try to recover from local storage:</h5>
                    <button id="recover-local-storage" class="btn btn-warning">
                        <i class="fas fa-history me-2"></i>Check Browser Storage
                    </button>
                </div>
            </div>
        <?php elseif (!$activity): ?>
            <div class="error-container">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Activity Not Found</h4>
                <p>Could not find activity with ID: <?php echo $activityId; ?></p>
                <?php if ($error): ?>
                    <p class="text-muted"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mt-4">
                <a href="emergency_navigation.php" class="btn btn-danger">
                    <i class="fas fa-compass me-2"></i>Go to Emergency Navigation
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Go to Homepage
                </a>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <h5>Navigation Options:</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student'): ?>
                    <a href="student/dashboard.php" class="btn btn-outline-primary">Student Dashboard</a>
                <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'teacher'): ?>
                    <a href="teacher/dashboard.php" class="btn btn-outline-primary">Teacher Dashboard</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-primary">Home</a>
                <?php endif; ?>
                
                <a href="emergency_navigation.php" class="btn btn-outline-danger">Emergency Navigation</a>
                <a href="direct_activity.php" class="btn btn-outline-secondary">Simple Activity Viewer</a>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Try to recover from localStorage
        document.getElementById('recover-local-storage')?.addEventListener('click', function() {
            try {
                const lastActivityId = localStorage.getItem('last_activity_id') || 
                                      sessionStorage.getItem('last_activity_id');
                                      
                if (lastActivityId) {
                    window.location.href = `direct_activity_recovery.php?id=${lastActivityId}`;
                } else {
                    alert('No activity ID found in local storage');
                }
            } catch (e) {
                alert('Error accessing local storage: ' + e.message);
            }
        });
        
        // Auto-recovery - check if we have an activity ID in storage 
        // and no ID was provided in the URL
        if (!window.location.search.includes('id=')) {
            try {
                const storedActivityId = localStorage.getItem('last_activity_id') || 
                                        sessionStorage.getItem('last_activity_id');
                                        
                if (storedActivityId) {
                    console.log('Found stored activity ID:', storedActivityId);
                    // Use a form to submit to avoid issues with the browser's HTTP cache
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'direct_activity_recovery.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'localStorage';
                    input.value = storedActivityId;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            } catch (e) {
                console.error('Auto-recovery error:', e);
            }
        }
    });
    </script>
</body>
</html>
