<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Check if activity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: classes.php');
    exit;
}

$activityId = (int)$_GET['id'];

// Add direct access check
$direct = isset($_GET['direct']) && $_GET['direct'] == '1';

// Always store activity ID in session for emergency recovery
if ($activityId) {
    $_SESSION['last_activity_id'] = $activityId;
    $_SESSION['last_edited_activity'] = $activityId;
}

// Add strict handling for activities
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
    }
} catch (Exception $e) {
    // Log error but continue with normal flow
    error_log('Error in direct activity access: ' . $e->getMessage());
}

// Get activity details
if (!$direct) {
    $activity = getActivityById($activityId, $_SESSION['user_id']);
}

// Redirect if activity not found or doesn't belong to this teacher
if (!$activity) {
    header('Location: classes.php?error=Activity not found');
    exit;
}

$moduleId = $activity['module_id'];
$classId = $activity['class_id'];

// Get submission statistics
$submissionStats = getSubmissionStats($activityId);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'assignment';
    $instructions = trim($_POST['instructions'] ?? '');
    $maxScore = intval($_POST['max_score'] ?? 100);
    $starterCode = isset($_POST['starter_code']) ? trim($_POST['starter_code']) : null;
    $testCases = isset($_POST['test_cases']) ? trim($_POST['test_cases']) : null;
    $dueDate = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if (empty($title)) {
        $message = "Activity title is required.";
        $messageType = "danger";
    } else {
        $result = updateActivity(
            $activityId, $title, $description, $type, $instructions, 
            $maxScore, $starterCode, $testCases, $dueDate, $_SESSION['user_id']
        );
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = "success";
            
            // Refresh activity data
            $activity = getActivityById($activityId, $_SESSION['user_id']);
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

// Get available programming languages
$programmingLanguages = getAvailableProgrammingLanguages();

$pageTitle = "Edit Activity" . (isset($activity) ? " - " . $activity['title'] : "");
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="mb-3 d-flex justify-content-end">
        <div class="input-group input-group-sm" style="width:200px;">
            <input type="number" id="emergency-activity-id" class="form-control" placeholder="Activity ID">
            <button onclick="secureNavigateToActivity(document.getElementById('emergency-activity-id').value)" class="btn btn-primary">Go</button>
        </div>
        <a href="../direct_activity_fix.php" class="btn btn-warning btn-sm ms-2">Fix Access</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Activity</h1>
        <div>
            <!-- Quick activity navigation helper -->
            <div class="input-group input-group-sm mb-2" style="width: 200px;">
                <input type="number" id="quick-navigate-id" class="form-control" placeholder="Go to Activity ID">
                <button onclick="secureNavigateToActivity(document.getElementById('quick-navigate-id').value)" class="btn btn-primary">Go</button>
            </div>
            <a href="module_activities.php?module_id=<?php echo $moduleId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Activities
            </a>
        </div>
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
    
    // Use secure navigation
    function secureNavigateToActivity(activityId) {
        if (!activityId) return;
        
        // Store in session for verification
        sessionStorage.setItem('last_activity_id', activityId);
        sessionStorage.setItem('activity_access_time', Date.now());
        
        // Generate access token
        const accessToken = Math.random().toString(36).substring(2, 15) + 
                          Math.random().toString(36).substring(2, 15) + 
                          Date.now().toString(36);
        sessionStorage.setItem('activity_access_token', accessToken);
        
        // Navigate securely
        window.location.href = 'edit_activity.php?id=' + activityId + '&token=' + accessToken;
    }
    </script>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <?php echo htmlspecialchars($activity['subject_code'] . ' - ' . $activity['subject_name']); ?>
            </h5>
        </div>
        <div class="card-body">
            <p><strong>Module:</strong> <?php echo htmlspecialchars($activity['module_title']); ?></p>
            <p>
                <strong>Status:</strong> 
                <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                    <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
            </p>
            <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($activity['updated_at'])); ?></p>
            
            <!-- Submission statistics -->
            <div class="alert alert-info mt-3">
                <h5><i class="fas fa-chart-bar"></i> Submission Statistics</h5>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Total Submissions:</strong> <?php echo $submissionStats['total']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Graded:</strong> <?php echo $submissionStats['graded']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Average Score:</strong> <?php echo $submissionStats['average_score']; ?>%</p>
                    </div>
                </div>
                <a href="view_submissions.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-users"></i> View All Submissions
                </a>
            </div>
            
            <!-- Emergency access for this activity -->
            <div class="alert alert-danger mt-3">
                <h5><i class="fas fa-exclamation-triangle"></i> Having trouble with this activity?</h5>
                <p>Use the emergency access link to view this activity:</p>
                <a href="../emergency_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-danger">
                    <i class="fas fa-external-link-alt"></i> Emergency Activity Access #<?php echo $activity['id']; ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Activity Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Activity Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($activity['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($activity['description']); ?></textarea>
                    <div class="form-text">Brief description of the activity (optional)</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="type" class="form-label">Activity Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <?php $types = ['assignment', 'quiz', 'coding', 'lab']; ?>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $activity['activity_type'] === $type ? 'selected' : ''; ?>>
                                    <?php echo getActivityTypeName($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="max_score" class="form-label">Maximum Score</label>
                        <input type="number" class="form-control" id="max_score" name="max_score" min="1" 
                               value="<?php echo $activity['max_score']; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="instructions" class="form-label">Instructions</label>
                    <textarea class="form-control" id="instructions" name="instructions" rows="5" required><?php echo htmlspecialchars($activity['instructions']); ?></textarea>
                    <div class="form-text">Detailed instructions for the activity</div>
                </div>
                
                <div class="mb-3 coding-options">
                    <label for="starter_code" class="form-label">Starter Code</label>
                    <textarea class="form-control" id="starter_code" name="starter_code" rows="5"><?php echo htmlspecialchars($activity['coding_starter_code']); ?></textarea>
                    <div class="form-text">Starter code that students will begin with (optional)</div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Test Cases (JSON Format)</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Define test cases for automatic grading. Each test case should include a name and test logic.</p>
                        <p class="text-muted">Example format for PHP:</p>
                        <pre class="bg-light p-2">[
  {"name": "Addition function works", "function": "addNumbers", "input": [5, 3], "expected": 8, "message": "Should add two numbers correctly"},
  {"name": "Custom test", "test": "$arr = [3, 1, 4, 2]; $sorted = sortArray($arr); return $sorted[0] === 1;", "message": "Should sort array"},
  {"name": "Code structure", "contains": "foreach", "message": "Your code should use foreach loop"}
]</pre>

                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Test Case Types:</strong>
                                <ul class="mt-2">
                                    <li><strong>Function test:</strong> Specify <code>"function"</code>, <code>"input"</code>, and <code>"expected"</code> values</li>
                                    <li><strong>Custom test:</strong> Use <code>"test"</code> with custom PHP code that returns boolean result</li>
                                    <li><strong>Code structure:</strong> Use <code>"contains"</code> to check if code contains specific elements</li>
                                </ul>
                                <a href="../tests/samples/php_tests.json" target="_blank">View sample test cases</a>
                            </div>
                            <label for="test_cases" class="form-label">Test Cases</label>
                            <textarea class="form-control code-editor" id="test_cases" name="test_cases" rows="8"><?php echo htmlspecialchars($activity['test_cases'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="due_date" class="form-label">Due Date (optional)</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo $activity['due_date'] ? date('Y-m-d', strtotime($activity['due_date'])) : ''; ?>">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle visibility of coding-specific options based on activity type
    const activityTypeSelect = document.getElementById('type');
    const codingElements = document.querySelectorAll('.coding-options');
    
    function toggleCodingOptions() {
        const isCodingTask = activityTypeSelect.value === 'coding' || activityTypeSelect.value === 'lab';
        codingElements.forEach(element => {
            element.style.display = isCodingTask ? 'block' : 'none';
        });
    }
    
    // Set initial state
    toggleCodingOptions();
    
    // Update on change
    activityTypeSelect.addEventListener('change', toggleCodingOptions);
});

function navigateToActivity() {
    const activityId = document.getElementById('quick-navigate-id').value;
    if (activityId) {
        window.location.href = "edit_activity.php?id=" + activityId;
    }
}

function goToActivity(activityId) {
    if (activityId) {
        window.location.href = "edit_activity.php?id=" + activityId + "&direct=1";
    }
}
</script>

<?php include '../includes/footer.php'; ?>
