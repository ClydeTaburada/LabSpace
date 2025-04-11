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

// Get recent modules
$modules = getModules($classId, $_SESSION['user_id'], 5);

$pageTitle = "View Class - " . $class['subject_code'] . ": " . $class['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?></h1>
        <div>
            <a href="classes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Class Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?></p>
                            <p><strong>Year Level:</strong> <?php echo $class['year_level']; ?> Year</p>
                            <p><strong>School Year:</strong> <?php echo htmlspecialchars($class['school_year']); ?></p>
                            <p><strong>Semester:</strong> <?php echo getSemesterName($class['semester']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <?php if ($class['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Class Code:</strong> 
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($class['class_code']); ?></span>
                            </p>
                            <p><strong>Students Enrolled:</strong> <?php echo $class['student_count']; ?></p>
                            <p><strong>Modules:</strong> <?php echo $class['module_count']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Modules Section -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Modules</h5>
                    <a href="class_modules.php?id=<?php echo $classId; ?>" class="btn btn-sm btn-light">Manage Modules</a>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No modules have been added to this class yet.
                            <p class="mb-0 mt-2">
                                <a href="class_modules.php?id=<?php echo $classId; ?>">Create your first module</a> to organize your course content.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($modules as $module): ?>
                                <div class="list-group-item list-group-item-action">
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
                                    <p class="mb-1"><?php echo htmlspecialchars($module['description']); ?></p>
                                    <small class="text-muted">
                                        <?php echo $module['activity_count']; ?> activities | 
                                        Last updated: <?php echo date('M j, Y', strtotime($module['updated_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="class_modules.php?id=<?php echo $classId; ?>" class="btn btn-outline-info">View All Modules</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add other sections as needed -->
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="view_students.php?class_id=<?php echo $classId; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-users"></i> View Students</h6>
                                <span class="badge bg-primary rounded-pill"><?php echo $class['student_count']; ?></span>
                            </div>
                            <small class="text-muted">View and manage enrolled students</small>
                        </a>
                        <a href="class_modules.php?id=<?php echo $classId; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-book"></i> Manage Modules</h6>
                                <span class="badge bg-primary rounded-pill"><?php echo $class['module_count']; ?></span>
                            </div>
                            <small class="text-muted">Organize course content into modules</small>
                        </a>
                        <a href="edit_class.php?id=<?php echo $classId; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-edit"></i> Edit Class</h6>
                            </div>
                            <small class="text-muted">Modify class details and settings</small>
                        </a>
                        <?php if ($class['is_active']): ?>
                            <a href="classes.php?toggle=deactivate&id=<?php echo $classId; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><i class="fas fa-toggle-off"></i> Deactivate Class</h6>
                                </div>
                                <small class="text-muted">Temporarily disable this class</small>
                            </a>
                        <?php else: ?>
                            <a href="classes.php?toggle=activate&id=<?php echo $classId; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><i class="fas fa-toggle-on"></i> Activate Class</h6>
                                </div>
                                <small class="text-muted">Enable this class for students</small>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Class Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Class Code:</strong></p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($class['class_code']); ?>" readonly id="classCodeInput">
                        <button class="btn btn-outline-secondary" type="button" id="copyCodeBtn">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="small text-muted">Share this code with students to enroll in this class.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy class code to clipboard
    const copyBtn = document.getElementById('copyCodeBtn');
    const codeInput = document.getElementById('classCodeInput');
    
    if (copyBtn && codeInput) {
        copyBtn.addEventListener('click', function() {
            codeInput.select();
            document.execCommand('copy');
            
            // Show tooltip or feedback
            copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(function() {
                copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
