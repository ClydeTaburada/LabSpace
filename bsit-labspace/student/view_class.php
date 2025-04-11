<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if class ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$classId = (int)$_GET['id'];

// Store current class ID in session for navigation recovery
$_SESSION['last_class_id'] = $classId;

// Get class details and verify student is enrolled
$class = getStudentClassById($classId, $_SESSION['user_id']);

// Redirect if class not found or student is not enrolled
if (!$class) {
    header('Location: dashboard.php?error=Class not found or you are not enrolled');
    exit;
}

$pageTitle = "View Class - " . $class['subject_code'] . ": " . $class['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?></h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Class Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function goToActivity() {
    const activityId = document.getElementById('direct-activity-id').value;
    if (activityId) {
        // Store activity ID in local storage for emergency recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
        } catch (e) {
            console.error('Storage error:', e);
        }
        
        // Navigate to the activity
        window.location.href = "view_activity.php?id=" + activityId;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
