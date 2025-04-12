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
    header('Location: dashboard.php?error=Invalid+activity+ID');
    exit;
}

$activityId = (int)$_GET['id'];

// Add direct access check and token verification
$direct = isset($_GET['direct']) && $_GET['direct'] == '1';
$recovered = isset($_GET['recovered']) && $_GET['recovered'] == '1';
$token = isset($_GET['token']) ? $_GET['token'] : null;

// Verify token if provided
$tokenValid = false;
if ($token && isset($_SESSION['activity_access_token']) && $token === $_SESSION['activity_access_token']) {
    $tokenValid = true;
}

// Always store activity ID in session for emergency recovery
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
    
    // Generate a new token for future access if needed
    if (!isset($_SESSION['activity_access_token'])) {
        $_SESSION['activity_access_token'] = bin2hex(random_bytes(16));
        $_SESSION['activity_access_time'] = time();
    }
}

// Add error handling
try {
    // Get activity with appropriate method based on flags
    if (($direct || $tokenValid || $recovered) && $activityId) {
        // Direct access with less restrictions
        $pdo = getDbConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT a.*, m.class_id, m.title as module_title, c.subject_id,
                    s.code as subject_code, s.name as subject_name
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                WHERE a.id = ? AND a.is_published = 1
            ");
            $stmt->execute([$activityId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        // Regular access with enrollment verification
        $activity = getActivityById($activityId, $_SESSION['user_id']);
    }
    
    // If direct access failed but we need this activity, try emergency access
    if (!$activity && $activityId) {
        // Log the attempt
        error_log("Student {$_SESSION['user_id']} attempted to access activity $activityId but failed - trying emergency access");
        
        // Try direct access with minimal restrictions as a last resort
        $pdo = getDbConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT a.*, m.class_id, m.title as module_title, c.subject_id,
                    s.code as subject_code, s.name as subject_name
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                WHERE a.id = ? AND a.is_published = 1
            ");
            $stmt->execute([$activityId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activity) {
                // Check if the student is enrolled in this class
                $stmt = $pdo->prepare("
                    SELECT 1 FROM class_enrollments 
                    WHERE student_id = ? AND class_id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $activity['class_id']]);
                $isEnrolled = (bool)$stmt->fetch();
                
                // If not enrolled, clear the activity (unless using emergency access)
                if (!$isEnrolled && !$recovered && !$direct && !$tokenValid) {
                    $activity = null;
                }
            }
        }
    }
    
    // Get submission details if available
    if ($activity) {
        $submission = getSubmissionDetails($activityId, $_SESSION['user_id']);
    }
    
    // Success message for submission
    $justSubmitted = isset($_GET['submitted']) && $_GET['submitted'] == '1';
} catch (Exception $e) {
    error_log('Error loading student activity: ' . $e->getMessage());
    $errorMessage = "Error: " . $e->getMessage();
    $activityLoadError = true;
}

// If activity not found but we have a stored ID, redirect to recovery
if (!$activity && !isset($activityLoadError)) {
    header("Location: ../direct_activity_recovery.php?id=$activityId");
    exit;
}

$pageTitle = isset($activity) ? htmlspecialchars($activity['title']) : "Activity Not Found";
include '../includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($activityLoadError) && $activityLoadError): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Error Loading Activity</h4>
            <p><?php echo $errorMessage; ?></p>
            <div class="mt-3">
                <a href="../direct_activity_recovery.php?id=<?php echo $activityId; ?>" class="btn btn-primary">
                    <i class="fas fa-sync me-2"></i>Attempt Recovery
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    <?php elseif (!$activity): ?>
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Activity Not Found</h4>
            <p>The requested activity could not be found or you don't have permission to view it.</p>
            <div class="mt-3">
                <a href="../direct_activity_recovery.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Find Activity
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Success message for submission -->
        <?php if ($justSubmitted): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <h4><i class="fas fa-check-circle me-2"></i>Submission Successful</h4>
                <p>Your work has been submitted successfully.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($activity['title']); ?></h1>
            <div>
                <a href="view_class.php?id=<?php echo $activity['class_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Class
                </a>
            </div>
        </div>
        
        <?php if ($activity['due_date'] && isActivityOverdue($activity['due_date']) && !$submission): ?>
        <!-- Overdue Warning Banner -->
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div>
                <h4 class="alert-heading">This activity is overdue!</h4>
                <p class="mb-0">The deadline was <?php echo date('F j, Y', strtotime($activity['due_date'])); ?>. Submit your work as soon as possible.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Activity Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> 
                            <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                <?php echo getActivityTypeName($activity['activity_type']); ?>
                            </span>
                        </p>
                        <p><strong>Module:</strong> <?php echo htmlspecialchars($activity['module_title']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Due Date:</strong> 
                            <?php if ($activity['due_date']): ?>
                                <span class="<?php echo getDueDateStatusClass($activity['due_date']); ?>">
                                    <?php echo date('F j, Y', strtotime($activity['due_date'])); ?>
                                    <?php if (isActivityOverdue($activity['due_date'])): ?>
                                        <span class="badge bg-danger">OVERDUE</span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No deadline</span>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong>Status:</strong> 
                            <?php if ($submission): ?>
                                <span class="badge bg-success">Submitted</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Not Submitted</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <p><strong>Maximum Score:</strong> <?php echo $activity['max_score']; ?></p>
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
        
        <!-- Emergency navigation options -->
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-life-ring me-2"></i>Navigation Assistance</h5>
            </div>
            <div class="card-body">
                <p>If you're having trouble with this activity, you can use one of these options:</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="../direct_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-warning">
                        <i class="fas fa-external-link-alt me-2"></i>Open in Simple View
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Back to Dashboard
                    </a>
                    <a href="../direct_activity_recovery.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-danger">
                        <i class="fas fa-ambulance me-2"></i>Emergency Recovery
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