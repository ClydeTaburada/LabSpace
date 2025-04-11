<?php
/**
 * Direct Activity Viewer
 * A simple tool for directly viewing and submitting activities by ID
 */

session_start();
require_once 'includes/functions/auth.php';
require_once 'includes/functions/activity_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=You must be logged in to view activities');
    exit;
}

// Get activity ID from query string
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$message = '';
$messageType = '';

// Handle activity viewing
if ($activityId) {
    // Get activity details
    $activity = getActivityById($activityId);
    
    // Get submission if user is a student
    $submission = null;
    if ($_SESSION['user_type'] == 'student') {
        $submission = getSubmissionDetails($activityId, $_SESSION['user_id']);
    }
    
    // Handle submission if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['user_type'] == 'student') {
        $code = $_POST['code'] ?? '';
        $language = $_POST['language'] ?? 'text';
        
        if (empty($code)) {
            $message = "Please enter your code before submitting";
            $messageType = "danger";
        } else {
            $result = submitActivityCode($activityId, $_SESSION['user_id'], $code, $language);
            
            if ($result['success']) {
                $message = $result['message'];
                $messageType = "success";
                
                // Refresh submission data
                $submission = getSubmissionDetails($activityId, $_SESSION['user_id']);
            } else {
                $message = $result['message'];
                $messageType = "danger";
            }
        }
    }
}

$pageTitle = $activity ? "Activity: " . $activity['title'] : "Activity Viewer";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Activity Viewer</h1>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                
                    <!-- Activity ID Input Form -->
                    <form action="" method="get" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="number" name="id" class="form-control" placeholder="Enter Activity ID" value="<?php echo $activityId; ?>" required>
                                    <button type="submit" class="btn btn-primary">View Activity</button>
                                </div>
                                <div class="form-text">Enter the ID of the activity you want to view and complete</div>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if ($_SESSION['user_type'] == 'student'): ?>
                                    <a href="student/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                                    <a href="teacher/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($activity): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h2 class="h4 mb-0"><?php echo htmlspecialchars($activity['title']); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Activity Type:</strong> <?php echo getActivityTypeName($activity['activity_type']); ?></p>
                                <p><strong>Maximum Score:</strong> <?php echo $activity['max_score']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Due Date:</strong> 
                                    <?php echo $activity['due_date'] ? date('M j, Y', strtotime($activity['due_date'])) : 'No deadline'; ?>
                                </p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <h3 class="h5">Instructions</h3>
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($activity['instructions'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                            <?php if ($submission): ?>
                                <!-- Show existing submission -->
                                <div class="card border-success mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h3 class="h5 mb-0">Your Submission</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i a', strtotime($submission['submission_date'])); ?></p>
                                                <p><strong>Language:</strong> <?php echo ucfirst($submission['language']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if ($submission['graded']): ?>
                                                    <p><strong>Grade:</strong> <?php echo $submission['grade']; ?>%</p>
                                                <?php else: ?>
                                                    <p><strong>Status:</strong> <span class="badge bg-warning text-dark">Not yet graded</span></p>
                                                <?php endif; ?>
                                                
                                                <?php if ($submission['auto_grade'] !== null): ?>
                                                    <p><strong>Auto-Score:</strong> <?php echo $submission['auto_grade']; ?>%</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <h4 class="h6">Your Submitted Code</h4>
                                        <pre class="bg-dark text-light p-3 rounded"><?php echo htmlspecialchars($submission['code']); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Submission Form -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h3 class="h5 mb-0"><?php echo $submission ? 'Update Your Submission' : 'Submit Your Work'; ?></h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="?id=<?php echo $activityId; ?>">
                                        <div class="mb-3">
                                            <label for="language" class="form-label">Programming Language</label>
                                            <select name="language" id="language" class="form-select">
                                                <option value="text" <?php echo ($submission && $submission['language'] === 'text') ? 'selected' : ''; ?>>Plain Text</option>
                                                <option value="python" <?php echo ($submission && $submission['language'] === 'python') ? 'selected' : ''; ?>>Python</option>
                                                <option value="java" <?php echo ($submission && $submission['language'] === 'java') ? 'selected' : ''; ?>>Java</option>
                                                <option value="cpp" <?php echo ($submission && $submission['language'] === 'cpp') ? 'selected' : ''; ?>>C++</option>
                                                <option value="csharp" <?php echo ($submission && $submission['language'] === 'csharp') ? 'selected' : ''; ?>>C#</option>
                                                <option value="javascript" <?php echo ($submission && $submission['language'] === 'javascript') ? 'selected' : ''; ?>>JavaScript</option>
                                                <option value="php" <?php echo ($submission && $submission['language'] === 'php') ? 'selected' : ''; ?>>PHP</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="code" class="form-label">Your Code</label>
                                            <textarea name="code" id="code" class="form-control" rows="15" placeholder="Enter your code here..."><?php echo $submission ? htmlspecialchars($submission['code']) : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> <?php echo $submission ? 'Update Submission' : 'Submit Code'; ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                            <div class="d-grid gap-2">
                                <a href="teacher/view_submissions.php?id=<?php echo $activityId; ?>" class="btn btn-primary">
                                    <i class="fas fa-users"></i> View Student Submissions
                                </a>
                                <a href="teacher/edit_activity.php?id=<?php echo $activityId; ?>" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Edit Activity
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($activityId): ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Activity Not Found</h4>
                    <p>The activity with ID <?php echo $activityId; ?> was not found or you do not have permission to view it.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> Enter an Activity ID</h4>
                    <p>Enter an activity ID in the field above to view and complete the activity.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
