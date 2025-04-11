<?php
/**
 * Ultra-Simple Activity Emergency Access
 * This script uses the absolute minimum code needed to load an activity
 * with no dependencies that could cause issues
 */

// Get activity ID
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Determine whether to redirect to student or teacher view
$userType = null;
if (isset($_COOKIE['user_type'])) {
    $userType = $_COOKIE['user_type'];
} elseif (isset($_SESSION['user_type'])) {
    $userType = $_SESSION['user_type'];
}

// Default to student if user type can't be determined
if (!in_array($userType, ['student', 'teacher', 'admin'])) {
    $userType = 'student';
}

// Build the redirect URL
$redirectTo = '';
switch ($userType) {
    case 'teacher':
        $redirectTo = 'teacher/edit_activity.php?id=' . $activityId . '&direct=1';
        break;
    case 'admin':
        $redirectTo = 'admin/view_activity.php?id=' . $activityId . '&direct=1';
        break;
    case 'student':
    default:
        $redirectTo = 'student/view_activity.php?id=' . $activityId . '&direct=1';
        break;
}

// If we have an activity ID, redirect right away
if ($activityId) {
    header('Location: ' . $redirectTo);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Activity Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        h1 {
            color: #dc3545;
        }
        input, button {
            padding: 10px;
            margin: 10px;
            font-size: 16px;
        }
        button {
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Emergency Activity Access</h1>
    <p>Enter an activity ID to directly access it:</p>
    
    <form method="get" action="">
        <input type="number" name="id" placeholder="Activity ID" required>
        <button type="submit">Access Activity</button>
    </form>
    
    <p><a href="index.php">Return to Homepage</a></p>
    
    <script>
    // Auto recover from local storage if available
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.querySelector('input[name="id"]');
        
        try {
            const lastActivityId = localStorage.getItem('last_activity_id') || 
                                 sessionStorage.getItem('last_activity_id');
            
            if (lastActivityId) {
                input.value = lastActivityId;
            }
        } catch (e) {
            console.error('Storage error:', e);
        }
        
        // Auto focus the input
        input.focus();
    });
    </script>
</body>
</html>
