<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Check if module ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: classes.php');
    exit;
}

$moduleId = (int)$_GET['id'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $orderIndex = intval($_POST['order_index'] ?? 1);
    
    if (empty($title)) {
        $message = "Module title is required.";
        $messageType = "danger";
    } else {
        $result = updateModule($moduleId, $title, $description, $orderIndex, $_SESSION['user_id']);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = "success";
            
            // Refresh module data
            $module = getModuleById($moduleId, $_SESSION['user_id']);
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

$pageTitle = "Edit Module - " . $module['subject_code'] . ": " . $module['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Module</h1>
        <div>
            <a href="class_modules.php?id=<?php echo $classId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Modules
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
            <h5 class="mb-0"><?php echo htmlspecialchars($module['subject_code'] . ' - ' . $module['subject_name']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Section:</strong> <?php echo htmlspecialchars($module['section']); ?></p>
                    <p><strong>Year Level:</strong> <?php echo $module['year_level']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Module Status:</strong> 
                        <?php if ($module['is_published']): ?>
                            <span class="badge bg-success">Published</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Last Updated:</strong> 
                        <?php echo date('M j, Y g:i A', strtotime($module['updated_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Module Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Module Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($module['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($module['description']); ?></textarea>
                    <div class="form-text">Briefly describe what this module covers.</div>
                </div>
                
                <div class="mb-3">
                    <label for="order_index" class="form-label">Order</label>
                    <input type="number" class="form-control" id="order_index" name="order_index" 
                           min="1" value="<?php echo $module['order_index']; ?>" required>
                    <div class="form-text">Determines the display order of the module in the list.</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="class_modules.php?id=<?php echo $classId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Module Activities</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0">Manage the activities within this module.</p>
                <a href="module_activities.php?module_id=<?php echo $moduleId; ?>" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> Manage Activities
                </a>
            </div>
            
            <!-- Direct activity access -->
            <div class="alert alert-info mt-3">
                <h5><i class="fas fa-info-circle"></i> Quick Activity Access</h5>
                <p>Enter an activity ID to view or edit directly:</p>
                <div class="input-group">
                    <input type="number" id="direct-activity-id" class="form-control" placeholder="Enter Activity ID">
                    <button onclick="secureViewActivity()" class="btn btn-primary">View Activity</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function secureViewActivity() {
    const activityId = document.getElementById('direct-activity-id').value;
    if (activityId) {
        // Store in session storage for verification
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('activity_access_time', Date.now());
            
            // Generate access token
            const accessToken = Math.random().toString(36).substring(2, 15) + 
                              Math.random().toString(36).substring(2, 15) + 
                              Date.now().toString(36);
            sessionStorage.setItem('activity_access_token', accessToken);
            
            // Navigate with token
            window.location.href = "edit_activity.php?id=" + activityId + "&token=" + accessToken;
        } catch (e) {
            console.error('Storage error:', e);
            // Fallback to standard navigation if storage fails
            window.location.href = "edit_activity.php?id=" + activityId;
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>
