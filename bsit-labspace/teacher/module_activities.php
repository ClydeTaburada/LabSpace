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

// Check if module ID is provided
if (!isset($_GET['module_id']) || !is_numeric($_GET['module_id'])) {
    header('Location: classes.php');
    exit;
}

$moduleId = (int)$_GET['module_id'];

// Get module details
$module = getModuleById($moduleId, $_SESSION['user_id']);

// Redirect if module not found or doesn't belong to a class owned by this teacher
if (!$module) {
    header('Location: classes.php?error=Module not found');
    exit;
}

$classId = $module['class_id'];

// Handle form submissions
$message = '';
$messageType = '';

// Handle activity creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'assignment';
    $instructions = trim($_POST['instructions'] ?? '');
    $maxScore = intval($_POST['max_score'] ?? 100);
    $starterCode = isset($_POST['starter_code']) ? trim($_POST['starter_code']) : null;
    $testCases = isset($_POST['test_cases']) ? trim($_POST['test_cases']) : null;
    $dueDate = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $publishNow = isset($_POST['publish_now']) ? true : false;
    
    if (empty($title)) {
        $message = "Activity title is required.";
        $messageType = "danger";
    } else {
        $result = createActivity(
            $moduleId, $title, $description, $type, $instructions, 
            $maxScore, $starterCode, $testCases, $dueDate, $_SESSION['user_id']
        );
        
        if ($result['success']) {
            $activityId = $result['activity_id'];
            
            // Store the activity ID for direct access
            $_SESSION['last_activity_id'] = $activityId;
            
            // Publish immediately if requested
            if ($publishNow && $activityId) {
                toggleActivityPublishStatus($activityId, true, $_SESSION['user_id']);
                $message = "Activity created and published successfully.";
            } else {
                $message = "Activity created successfully (as draft).";
            }
            $messageType = "success";
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

// Handle activity deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $activityId = (int)$_GET['delete'];
    $result = deleteActivity($activityId, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

// Handle direct access to activities
if (isset($_GET['view_activity']) && is_numeric($_GET['view_activity'])) {
    $activityId = (int)$_GET['view_activity'];
    
    // Store in session for security verification
    $_SESSION['last_activity_id'] = $activityId;
    $_SESSION['activity_access_time'] = time();
    $_SESSION['activity_access_token'] = bin2hex(random_bytes(16));
    
    // Redirect to activity view page
    header("Location: view_activity.php?id=$activityId&token={$_SESSION['activity_access_token']}");
    exit;
}

// Handle activity publication toggle
if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    $activityId = (int)$_GET['publish'];
    $result = toggleActivityPublishStatus($activityId, true, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

if (isset($_GET['unpublish']) && is_numeric($_GET['unpublish'])) {
    $activityId = (int)$_GET['unpublish'];
    $result = toggleActivityPublishStatus($activityId, false, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

// Get activities for this module
$activities = getModuleActivities($moduleId, $_SESSION['user_id']);

// Get available programming languages
$programmingLanguages = getAvailableProgrammingLanguages();

$pageTitle = "Module Activities - " . $module['title'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Module Activities</h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="fas fa-plus"></i> Add Activity
            </button>
            <a href="edit_module.php?id=<?php echo $moduleId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Module
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <?php echo htmlspecialchars($module['subject_code'] . ' - ' . $module['subject_name']); ?>:
                <?php echo htmlspecialchars($module['title']); ?>
            </h5>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($module['description'])); ?></p>
            <div class="mt-2">
                <span class="badge bg-<?php echo $module['is_published'] ? 'success' : 'secondary'; ?>">
                    <?php echo $module['is_published'] ? 'Published' : 'Draft'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Activities (<?php echo count($activities); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($activities)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No activities have been added to this module yet.
                    <p class="mb-0 mt-2">Click the "Add Activity" button to create your first activity.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Max Score</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                        <?php if (!empty($activity['description'])): ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge activity-<?php echo $activity['activity_type']; ?>">
                                            <?php echo getActivityTypeName($activity['activity_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $activity['max_score']; ?></td>
                                    <td>
                                        <?php echo $activity['due_date'] ? date('M j, Y', strtotime($activity['due_date'])) : 'No deadline'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_submissions.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-info" title="Submissions">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <?php if ($activity['is_published']): ?>
                                                <a href="?module_id=<?php echo $moduleId; ?>&unpublish=<?php echo $activity['id']; ?>" 
                                                   class="btn btn-outline-warning" title="Unpublish">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?module_id=<?php echo $moduleId; ?>&publish=<?php echo $activity['id']; ?>" 
                                                   class="btn btn-outline-success" title="Publish">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?module_id=<?php echo $moduleId; ?>&delete=<?php echo $activity['id']; ?>" 
                                               class="btn btn-outline-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this activity? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <a href="?module_id=<?php echo $moduleId; ?>&view_activity=<?php echo $activity['id']; ?>" 
                                               class="btn btn-outline-dark" title="View Activity">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addActivityModalLabel">Add New Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="activity-title" class="form-label">Activity Title</label>
                        <input type="text" class="form-control" id="activity-title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity-description" class="form-label">Description</label>
                        <textarea class="form-control" id="activity-description" name="description" rows="2"></textarea>
                        <div class="form-text">Brief description of the activity (optional)</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="activity-type" class="form-label">Activity Type</label>
                            <select class="form-select" id="activity-type" name="type" required>
                                <option value="assignment">Assignment</option>
                                <option value="quiz">Quiz</option>
                                <option value="coding">Coding Task</option>
                                <option value="lab">Lab Exercise</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="max-score" class="form-label">Maximum Score</label>
                            <input type="number" class="form-control" id="max-score" name="max_score" min="1" value="100" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 coding-options">
                        <label for="programming-language" class="form-label">Programming Language</label>
                        <select class="form-select" id="programming-language">
                            <option value="">-- Select Language --</option>
                            <?php foreach ($programmingLanguages as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">The language will be added as a comment in the starter code</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity-instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="activity-instructions" name="instructions" rows="5" required></textarea>
                        <div class="form-text">Detailed instructions for the activity</div>
                    </div>
                    
                    <div class="mb-3 coding-options">
                        <label for="starter-code" class="form-label">Starter Code</label>
                        <textarea class="form-control" id="starter-code" name="starter_code" rows="5"></textarea>
                        <div class="form-text">Starter code that students will begin with (optional)</div>
                    </div>
                    
                    <div class="mb-3 coding-options">
                        <label for="test-cases" class="form-label">Test Cases (JSON format)</label>
                        <textarea class="form-control" id="test-cases" name="test_cases" rows="4"></textarea>
                        <div class="form-text">
                            Specify test cases in JSON format for automatic grading. Example:
                            <br><code>[{"name": "Test 1", "function": "addNumbers", "input": [5,3], "expected": 8}]</code>
                            <br>For PHP: <a href="../tests/samples/php_tests.json" target="_blank">View sample PHP test cases</a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due-date" class="form-label">Due Date (optional)</label>
                        <input type="date" class="form-control" id="due-date" name="due_date">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="publish-now" name="publish_now">
                        <label class="form-check-label" for="publish-now">
                            Publish immediately (otherwise saved as draft)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle visibility of coding-specific options based on activity type
    const activityTypeSelect = document.getElementById('activity-type');
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
    
    // Add programming language template to starter code
    const languageSelect = document.getElementById('programming-language');
    const starterCodeArea = document.getElementById('starter-code');
    
    languageSelect.addEventListener('change', function() {
        const language = this.value;
        if (!language) return;
        
        let template = '';
        switch(language) {
            case 'python':
                template = '# Python code\n\ndef main():\n    # Your code here\n    pass\n\nif __name__ == "__main__":\n    main()';
                break;
            case 'java':
                template = '// Java code\n\npublic class Solution {\n    public static void main(String[] args) {\n        // Your code here\n    }\n}';
                break;
            case 'cpp':
                template = '// C++ code\n\n#include <iostream>\nusing namespace std;\n\nint main() {\n    // Your code here\n    return 0;\n}';
                break;
            case 'csharp':
                template = '// C# code\n\nusing System;\n\nclass Program\n{\n    static void Main()\n    {\n        // Your code here\n    }\n}';
                break;
            case 'javascript':
                template = '// JavaScript code\n\nfunction main() {\n    // Your code here\n}\n\nmain();';
                break;
            case 'php':
                template = '<' + '?php\n// PHP code\n\nfunction main() {\n    // Your code here\n}\n\nmain();\n?' + '>';
                break;
            case 'html':
                template = '<!DOCTYPE html>\n<html>\n<head>\n    <title>Activity</title>\n</head>\n<body>\n    <!-- Your HTML here -->\n</body>\n</html>';
                break;
            case 'sql':
                template = '-- SQL code\n\nSELECT * FROM table_name\nWHERE condition;';
                break;
        }
        
        starterCodeArea.value = template;
    });
    
    // Reset form when modal is closed
    const modal = document.getElementById('addActivityModal');
    modal.addEventListener('hidden.bs.modal', function() {
        this.querySelector('form').reset();
    });
});

// Secure activity navigation function
function viewActivity(activityId) {
    // Store in session storage for verification
    sessionStorage.setItem('last_activity_id', activityId);
    sessionStorage.setItem('activity_access_time', Date.now());
    
    // Generate access token
    const accessToken = Math.random().toString(36).substring(2, 15) + 
                       Math.random().toString(36).substring(2, 15) + 
                       Date.now().toString(36);
    sessionStorage.setItem('activity_access_token', accessToken);
    
    // Navigate with the token
    window.location.href = 'edit_activity.php?id=' + activityId + '&token=' + accessToken;
}
</script>

<?php include '../includes/footer.php'; ?>
