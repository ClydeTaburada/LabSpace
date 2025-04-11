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

// Check if class ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: classes.php');
    exit;
}

$classId = (int)$_GET['id'];

// Get class details
$class = getClassById($classId, $_SESSION['user_id']);

// Redirect if class not found or doesn't belong to this teacher
if (!$class) {
    header('Location: classes.php?error=Class not found');
    exit;
}

// Handle form submissions
$message = '';
$messageType = 'info'; // Initialize message type for alerts

// Handle module creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $orderIndex = intval($_POST['order_index'] ?? getNextModuleOrderIndex($classId));
    
    if (empty($title)) {
        $message = "Module title is required.";
        $messageType = "danger";
    } else {
        $result = createModule($classId, $title, $description, $orderIndex, $_SESSION['user_id']);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = "success";
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

// Handle module deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $moduleId = (int)$_GET['delete'];
    $result = deleteModule($moduleId, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

// Handle module publication toggle
if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    $moduleId = (int)$_GET['publish'];
    $result = toggleModulePublishStatus($moduleId, true, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

if (isset($_GET['unpublish']) && is_numeric($_GET['unpublish'])) {
    $moduleId = (int)$_GET['unpublish'];
    $result = toggleModulePublishStatus($moduleId, false, $_SESSION['user_id']);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = "success";
    } else {
        $message = $result['message'];
        $messageType = "danger";
    }
}

// Get modules for this class
$modules = getModules($classId, $_SESSION['user_id']);

$pageTitle = "Modules - " . $class['subject_code'] . ": " . $class['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Class Modules</h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                <i class="fas fa-plus"></i> Add Module
            </button>
            <a href="view_class.php?id=<?php echo $classId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Class
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
            <h5 class="mb-0"><?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?></p>
                    <p><strong>Year Level:</strong> <?php echo $class['year_level']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>School Year:</strong> <?php echo htmlspecialchars($class['school_year']); ?></p>
                    <p><strong>Semester:</strong> <?php echo getSemesterName($class['semester']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Modules (<?php echo count($modules); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($modules)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No modules have been added to this class yet.
                    <p class="mb-0 mt-2">Click the "Add Module" button to create your first module.</p>
                </div>
            <?php else: ?>
                <div class="list-group" id="modules-list">
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div class="ms-2 me-auto">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <?php echo $module['order_index']; ?>. 
                                        <?php echo htmlspecialchars($module['title']); ?>
                                        <?php if ($module['is_published']): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($module['description']); ?></p>
                                <small>
                                    <?php if ($module['activity_count'] > 0): ?>
                                        <span class="text-primary"><?php echo $module['activity_count']; ?> activities</span>
                                    <?php else: ?>
                                        <span class="text-muted">No activities yet</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="edit_module.php?id=<?php echo $module['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="module_activities.php?module_id=<?php echo $module['id']; ?>" class="btn btn-outline-info" title="Activities">
                                    <i class="fas fa-tasks"></i>
                                </a>
                                <?php if ($module['is_published']): ?>
                                    <a href="class_modules.php?id=<?php echo $classId; ?>&unpublish=<?php echo $module['id']; ?>" 
                                       class="btn btn-outline-warning" title="Unpublish">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="class_modules.php?id=<?php echo $classId; ?>&publish=<?php echo $module['id']; ?>" 
                                       class="btn btn-outline-success" title="Publish">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($module['activity_count'] == 0): ?>
                                    <a href="class_modules.php?id=<?php echo $classId; ?>&delete=<?php echo $module['id']; ?>" 
                                       class="btn btn-outline-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this module?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Module Modal -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addModuleModalLabel">Add New Module</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="module-title" class="form-label">Module Title</label>
                        <input type="text" class="form-control" id="module-title" name="title" required>
                        <div class="form-text">Example: "HTML Basics", "CSS Fundamentals", etc.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="module-description" class="form-label">Description</label>
                        <textarea class="form-control" id="module-description" name="description" rows="3"></textarea>
                        <div class="form-text">Briefly describe what this module covers.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="module-order" class="form-label">Order</label>
                        <input type="number" class="form-control" id="module-order" name="order_index" 
                               min="1" value="<?php echo count($modules) + 1; ?>" required>
                        <div class="form-text">Determines the display order of the module in the list.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Module</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add module modal - reset form when modal is closed
    const addModuleModal = document.getElementById('addModuleModal');
    addModuleModal.addEventListener('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        form.reset();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
