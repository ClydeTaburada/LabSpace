<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Get activity ID from query string
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$direct = isset($_GET['direct']) && $_GET['direct'] == '1';

// Store activity ID for emergency recovery
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
}

// Try to get activity details
try {
    // Try direct access first
    $activity = getActivityByIdDirect($activityId);
    
    // If direct access fails and we're not in direct mode, try regular access
    if (!$activity && !$direct) {
        $activity = getActivityById($activityId, $_SESSION['user_id']);
    }
} catch (Exception $e) {
    error_log('Error loading activity: ' . $e->getMessage());
    $errorMessage = "Error loading activity. Please try again or contact support.";
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
        <h4><i class="fas fa-exclamation-triangle"></i> Error Loading Activity</h4>
        <p><?php echo $errorMessage; ?></p>
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <a href="../direct_activity_fix.php" class="btn btn-warning">
                <i class="fas fa-tools"></i> Fix Access Issues
            </a>
            <button onclick="window.location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i> Try Again
            </button>
        </div>
    </div>
    <?php elseif (!$activity): ?>
    <div class="alert alert-warning">
        <h4><i class="fas fa-exclamation-triangle"></i> Activity Not Found</h4>
        <p>The activity with ID <?php echo $activityId; ?> could not be found or you don't have permission to view it.</p>
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <a href="../direct_activity_fix.php" class="btn btn-warning">
                <i class="fas fa-tools"></i> Fix Access Issues
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Emergency navigation controls -->
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Activity ID:</strong> <?php echo $activity['id']; ?> 
                    <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?> ms-2">
                        <?php echo getActivityTypeName($activity['activity_type']); ?>
                    </span>
                </div>
                <div class="direct-nav-panel">
                    <div class="input-group input-group-sm" style="width:250px;">
                        <input type="number" id="emergency-activity-id" class="form-control" placeholder="Enter Activity ID">
                        <button onclick="goToActivity(document.getElementById('emergency-activity-id').value)" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Go
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    </div>

    <script>
    // Function to navigate to activity without prompts
    function goToActivity(activityId) {
        if (!activityId) {
            console.error("No activity ID provided");
            return;
        }
        
        console.log("Navigating to activity ID: " + activityId);
        
        // Store ID in storage for recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
        } catch (e) {
            console.error("Storage error:", e);
        }
        
        // Navigate without prompting
        window.location.href = "view_activity.php?id=" + activityId + "&direct=1";
    }
    
    // Fix for emergency activity ID navigation
    document.addEventListener('DOMContentLoaded', function() {
        const emergencyInput = document.getElementById('emergency-activity-id');
        if (emergencyInput) {
            emergencyInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    goToActivity(this.value);
                }
            });
        }
    });
    </script>
    
<?php endif; ?>
</div>

<script>
// Add keyboard shortcut for quick access - Alt+A
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'a') {
        document.getElementById('emergency-activity-id').focus();
    }
});

// Auto-focus the ID input when pressing Tab
document.addEventListener('DOMContentLoaded', function() {
    const idInput = document.getElementById('emergency-activity-id');
    idInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            goToActivity(this.value);
        }
    });
});
</script>
<?php include '../includes/footer.php'; ?>
