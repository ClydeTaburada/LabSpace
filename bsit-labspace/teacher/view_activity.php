<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Get activity ID from query string
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$direct = isset($_GET['direct']) && $_GET['direct'] == '1';
$recovered = isset($_GET['recovered']) && $_GET['recovered'] == '1';
$token = isset($_GET['token']) ? $_GET['token'] : null;

// Verify token if provided
$tokenValid = false;
if ($token && isset($_SESSION['activity_access_token']) && $token === $_SESSION['activity_access_token']) {
    $tokenValid = true;
}

// Store activity ID for emergency recovery
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
    
    // Generate a new token for future access
    if (!isset($_SESSION['activity_access_token'])) {
        $_SESSION['activity_access_token'] = bin2hex(random_bytes(16));
        $_SESSION['activity_access_time'] = time();
    }
}

// Add error handling
try {
    // Try direct access first if we have a token or direct flag
    if (($direct || $tokenValid || $recovered) && $activityId) {
        $activity = getActivityByIdDirect($activityId);
    } else {
        // Regular access with ownership verification
        $activity = getActivityById($activityId, $_SESSION['user_id']);
    }
    
    // If no activity found, try emergency recovery
    if (!$activity && $activityId) {
        // Log the attempt
        error_log("Teacher {$_SESSION['user_id']} attempted to access activity $activityId but failed - trying emergency access");
        
        // Try direct access as a last resort
        $activity = getActivityByIdDirect($activityId);
        
        if ($activity) {
            // Successfully recovered - redirect to include the direct flag
            header("Location: view_activity.php?id={$activityId}&direct=1&recovered=1");
            exit;
        }
    }
} catch (Exception $e) {
    error_log('Error loading activity: ' . $e->getMessage());
    $errorMessage = "Error loading activity: " . $e->getMessage();
    $activityLoadError = true;
}

// If no activity found and we have a last activity ID, try to recover
if (!$activity && !$activityId && isset($_SESSION['last_activity_id'])) {
    $activityId = (int)$_SESSION['last_activity_id'];
    try {
        $activity = getActivityByIdDirect($activityId);
    } catch (Exception $e) {
        error_log('Error in recovery attempt: ' . $e->getMessage());
    }
    
    if ($activity) {
        // Redirect to the proper URL with the recovered ID
        header("Location: view_activity.php?id={$activityId}&recovered=1");
        exit;
    }
}

$pageTitle = "View Activity" . (isset($activity) ? " - " . $activity['title'] : "");
include '../includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($activityLoadError) && $activityLoadError): ?>
    <div class="alert alert-danger">
        <h4><i class="fas fa-exclamation-triangle me-2"></i> Error Loading Activity</h4>
        <p><?php echo $errorMessage; ?></p>
        <div class="mt-3">
            <a href="../direct_activity_recovery.php?id=<?php echo $activityId; ?>" class="btn btn-primary">
                <i class="fas fa-sync"></i> Attempt Recovery
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php elseif (!$activity): ?>
    <div class="alert alert-warning">
        <h4><i class="fas fa-exclamation-triangle me-2"></i> Activity Not Found</h4>
        <p>The requested activity could not be found or you don't have permission to view it.</p>
        <div class="mt-3">
            <a href="../direct_activity_recovery.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Find Activity
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($activity['title']); ?></h1>
        <div>
            <a href="edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Activity
            </a>
            <a href="view_submissions.php?id=<?php echo $activity['id']; ?>" class="btn btn-success">
                <i class="fas fa-file-alt"></i> View Submissions
            </a>
            <a href="module_activities.php?module_id=<?php echo $activity['module_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Activities
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Activity Details</h5>
        </div>
        <div class="card-body">
            <p><strong>Type:</strong> <?php echo getActivityTypeName($activity['activity_type']); ?></p>
            <p><strong>Maximum Score:</strong> <?php echo $activity['max_score']; ?></p>
            <p><strong>Due Date:</strong> 
                <?php echo $activity['due_date'] ? date('M j, Y', strtotime($activity['due_date'])) : 'No deadline'; ?>
            </p>
            <p><strong>Status:</strong>
                <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                    <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
            </p>
            <hr>
            <p><strong>Instructions:</strong></p>
            <div class="card bg-light">
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($activity['instructions'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Emergency navigation options -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-life-ring me-2"></i>Emergency Navigation</h5>
        </div>
        <div class="card-body">
            <p>If you're having trouble accessing or editing this activity, you can use one of these options:</p>
            <div class="d-flex gap-2">
                <a href="../direct_activity_fix.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-danger">
                    <i class="fas fa-external-link-alt me-2"></i>Open in Direct Access
                </a>
                <a href="edit_activity.php?id=<?php echo $activity['id']; ?>&direct=1" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Open in Editor
                </a>
                <a href="../admin/view_all_activities.php" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i>View All Activities
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Store activity ID for potential recovery -->
<script>
// Securely store current activity ID
try {
    localStorage.setItem('last_activity_id', '<?php echo $activityId; ?>');
    sessionStorage.setItem('last_activity_id', '<?php echo $activityId; ?>');
    sessionStorage.setItem('activity_access_time', Date.now());
} catch (e) {
    console.error('Storage error:', e);
}
</script>

<?php include '../includes/footer.php'; ?>
