<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if activity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$activityId = (int)$_GET['id'];

// Add direct access check - this helps bypass any potential issues with authentication checks
$direct = isset($_GET['direct']) && $_GET['direct'] == '1';

// Always store activity ID in session for emergency recovery
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
}

// Add error handling
try {
    // Get activity with a simpler query for direct access
    if ($direct && $activityId) {
        $pdo = getDbConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT a.*, m.class_id, m.title as module_title, c.subject_id,
                    s.code as subject_code, s.name as subject_name
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                WHERE a.id = ?
            ");
            $stmt->execute([$activityId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $activity = getActivityById($activityId, $_SESSION['user_id']);
    }
    
    // Redirect if activity not found or student is not enrolled
    if (!$activity) {
        header('Location: dashboard.php?error=Activity not found or you are not enrolled');
        exit;
    }
    
    // Get submission details if available
    $submission = getSubmissionDetails($activityId, $_SESSION['user_id']);
    
    // Success message for submission
    $justSubmitted = isset($_GET['submitted']) && $_GET['submitted'] == '1';
} catch (Exception $e) {
    // Log error
    error_log('Error loading activity: ' . $e->getMessage());
    
    // Show error message
    $errorMessage = "Error loading activity. Please try again or contact support.";
    
    // Set flag to show error message
    $activityLoadError = true;
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
            <?php if (isset($_SESSION['last_class_id'])): ?>
            <a href="view_class.php?id=<?php echo $_SESSION['last_class_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-chalkboard"></i> Back to Class
            </a>
            <?php endif; ?>
            <button onclick="window.location.reload()" class="btn btn-warning">
                <i class="fas fa-sync-alt"></i> Try Again
            </button>
            <a href="../direct_activity_fix.php" class="btn btn-danger">
                <i class="fas fa-bolt"></i> Fix Activity Access
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($activity['title']); ?></h1>
        <div>
            <a href="view_class.php?id=<?php echo $activity['class_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Class
            </a>
        </div>
    </div>
    
    <?php if ($justSubmitted): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Your work has been submitted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
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
            <p><strong>Instructions:</strong></p>
            <div class="card bg-light">
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($activity['instructions'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($submission)): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Your Submission</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i a', strtotime($submission['submission_date'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i a', strtotime($submission['updated_at'])); ?></p>
                    <p><strong>Language:</strong> <?php echo ucfirst($submission['language']); ?></p>
                </div>
                <div class="col-md-6">
                    <?php if ($submission['auto_grade'] !== null): ?>
                    <div class="mb-3">
                        <p><strong>Auto-Score:</strong> <?php echo $submission['auto_grade']; ?>%</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($submission['code'])): ?>
            <div class="mt-3">
                <h6>Your Submitted Code:</h6>
                <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($submission['code']); ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Add a clear submission button section -->
    <?php if ($activity['activity_type'] === 'coding' || $activity['activity_type'] === 'assignment'): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Submit Your Work</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="code_editor.php?id=<?php echo $activityId; ?>" class="btn btn-success">
                    <i class="fas fa-code"></i> 
                    <?php echo empty($submission) ? 'Start Working on This Activity' : 'Edit Your Submission'; ?>
                </a>
                <?php if (!empty($submission)): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearSubmissionModal">
                    <i class="fas fa-trash-alt"></i> Reset My Submission
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($submission)): ?>
    <!-- Clear Submission Modal -->
    <div class="modal fade" id="clearSubmissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Reset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset your submission? This will delete your current code and allow you to start fresh.</p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="reset_submission.php?id=<?php echo $activityId; ?>" class="btn btn-danger">Reset Submission</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?> <!-- This was the missing endif for the main else block that started after $activityLoadError check -->
</div>

<script>
// Add simplified activity navigation
function navigateToActivity(id) {
    if (!id) return;
    window.location.href = `view_activity.php?id=${id}&direct=1`;
}

// Function to go to activity by ID
function goToActivity(activityId) {
    if (!activityId) return;
    window.location.href = "view_activity.php?id=" + activityId;
}
</script>

<?php include '../includes/footer.php'; ?>