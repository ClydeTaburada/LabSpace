<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Get recent classes for quick access
$recentClasses = getTeacherClasses($_SESSION['user_id'], 5);

$pageTitle = "Teacher Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Teacher Dashboard</h1>
    <p class="lead">Welcome to BSIT LabSpace, <?php echo $_SESSION['user_name']; ?>!</p>
    
    <!-- Add a direct logout button -->
    <div class="mb-4 text-end">
        <a href="<?php echo getBaseUrl(); ?>logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chalkboard"></i> My Classes</h5>
                    <p class="card-text">Manage your classes and student enrollments.</p>
                    <div class="d-flex gap-2">
                        <a href="classes.php" class="btn btn-primary">View Classes</a>
                        <a href="create_class.php" class="btn btn-outline-primary">Create Class</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tasks"></i> Activities</h5>
                    <p class="card-text">Create and manage assignments, quizzes, and coding tasks.</p>
                    <div class="d-flex gap-2">
                        <a href="activities.php" class="btn btn-primary">Manage Activities</a>
                        <a href="../direct_activity_viewer.php" class="btn btn-outline-primary">
                            <i class="fas fa-bolt"></i> Quick Access
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Student Progress</h5>
                    <p class="card-text">View and evaluate student submissions and progress.</p>
                    <a href="student_progress.php" class="btn btn-primary">View Progress</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Classes Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Recent Classes</h5>
                    <a href="classes.php" class="btn btn-sm btn-light">View All Classes</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentClasses)): ?>
                        <p class="text-muted text-center mb-0">You haven't created any classes yet. <a href="create_class.php">Create your first class</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Section</th>
                                        <th>Class Code</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentClasses as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['subject_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($class['subject_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($class['section']); ?><br>
                                                <small class="text-muted">Year <?php echo $class['year_level']; ?></small>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" class="form-control form-control-sm code-value" 
                                                           value="<?php echo htmlspecialchars($class['class_code']); ?>" readonly>
                                                    <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                            data-bs-toggle="tooltip" title="Copy Code">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?php echo $class['student_count']; ?> students
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $class['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $class['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-info" title="View Students">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                    <?php if ($class['is_active']): ?>
                                                        <a href="classes.php?toggle=deactivate&id=<?php echo $class['id']; ?>" 
                                                           class="btn btn-outline-warning" title="Deactivate">
                                                            <i class="fas fa-toggle-off"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="classes.php?toggle=activate&id=<?php echo $class['id']; ?>" 
                                                           class="btn btn-outline-success" title="Activate">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </a>
                                                    <?php endif; ?>
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
    </div>
    
    <div class="row mt-2">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No recent submissions yet.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Upcoming Due Dates</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No upcoming due dates.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    
    // Copy class code functionality
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const inputElement = this.previousElementSibling;
            inputElement.select();
            document.execCommand('copy');
            
            // Change tooltip text temporarily
            const tooltip = bootstrap.Tooltip.getInstance(this);
            const originalTitle = this.getAttribute('data-bs-original-title');
            
            this.setAttribute('data-bs-original-title', 'Copied!');
            tooltip.show();
            
            // Reset tooltip after a delay
            setTimeout(() => {
                this.setAttribute('data-bs-original-title', originalTitle);
            }, 1000);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>