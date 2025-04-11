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

// Get teacher's classes
$classes = getTeacherClasses($_SESSION['user_id']);

// Handle class activation/deactivation
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $classId = (int)$_GET['id'];
    $newStatus = $_GET['toggle'] === 'activate' ? 1 : 0;
    
    if (toggleClassStatus($classId, $newStatus, $_SESSION['user_id'])) {
        // Refresh the classes list
        $classes = getTeacherClasses($_SESSION['user_id']);
        $message = "Class status updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update class status.";
        $messageType = "danger";
    }
}

$pageTitle = "My Classes";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Classes</h1>
        <a href="create_class.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Class
        </a>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="filter-status" class="form-label">Status:</label>
                    <select id="filter-status" class="form-select">
                        <option value="all">All Classes</option>
                        <option value="active">Active Classes</option>
                        <option value="inactive">Inactive Classes</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-year" class="form-label">School Year:</label>
                    <select id="filter-year" class="form-select">
                        <option value="all">All School Years</option>
                        <?php 
                        $schoolYears = [];
                        foreach ($classes as $class) {
                            if (!in_array($class['school_year'], $schoolYears)) {
                                $schoolYears[] = $class['school_year'];
                                echo '<option value="'.$class['school_year'].'">'.$class['school_year'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search-classes" class="form-label">Search:</label>
                    <input type="text" id="search-classes" class="form-control" placeholder="Search classes...">
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($classes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't created any classes yet. Click the "Create New Class" button to get started.
        </div>
    <?php else: ?>
        <div class="row" id="classes-container">
            <?php foreach ($classes as $class): ?>
                <div class="col-md-6 mb-4 class-card" 
                     data-status="<?php echo $class['is_active'] ? 'active' : 'inactive'; ?>"
                     data-year="<?php echo htmlspecialchars($class['school_year']); ?>">
                    <div class="card h-100 <?php echo $class['is_active'] ? 'border-success' : 'border-secondary'; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?>
                            </h5>
                            <span class="badge bg-<?php echo $class['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $class['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Year Level:</strong> <?php echo $class['year_level']; ?> Year
                            </div>
                            <div class="mb-2">
                                <strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>School Year:</strong> <?php echo htmlspecialchars($class['school_year']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Semester:</strong> <?php echo getSemesterName($class['semester']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Class Code:</strong> 
                                <span class="badge bg-info text-dark fs-6">
                                    <?php echo htmlspecialchars($class['class_code']); ?>
                                </span>
                            </div>
                            <div class="mb-2">
                                <strong>Students:</strong> <?php echo $class['student_count']; ?>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <div class="btn-group">
                                <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="class_modules.php?id=<?php echo $class['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-book"></i> Modules
                                </a>
                                <a href="view_students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-users"></i> Students
                                </a>
                            </div>
                            <?php if ($class['is_active']): ?>
                                <a href="?toggle=deactivate&id=<?php echo $class['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-toggle-off"></i> Deactivate
                                </a>
                            <?php else: ?>
                                <a href="?toggle=activate&id=<?php echo $class['id']; ?>" class="btn btn-outline-success">
                                    <i class="fas fa-toggle-on"></i> Activate
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Class filtering
    const filterStatus = document.getElementById('filter-status');
    const filterYear = document.getElementById('filter-year');
    const searchInput = document.getElementById('search-classes');
    const classCards = document.querySelectorAll('.class-card');
    
    function filterClasses() {
        const statusFilter = filterStatus.value;
        const yearFilter = filterYear.value;
        const searchValue = searchInput.value.toLowerCase();
        
        classCards.forEach(card => {
            const status = card.dataset.status;
            const year = card.dataset.year;
            const title = card.querySelector('.card-header h5').textContent.toLowerCase();
            const section = card.querySelector('.card-body div:nth-child(2)').textContent.toLowerCase();
            
            // Check if matches all filters
            const matchesStatus = statusFilter === 'all' || status === statusFilter;
            const matchesYear = yearFilter === 'all' || year === yearFilter;
            const matchesSearch = title.includes(searchValue) || section.includes(searchValue);
            
            // Show/hide card based on filters
            card.style.display = matchesStatus && matchesYear && matchesSearch ? '' : 'none';
        });
    }
    
    filterStatus.addEventListener('change', filterClasses);
    filterYear.addEventListener('change', filterClasses);
    searchInput.addEventListener('input', filterClasses);
});
</script>

<?php include '../includes/footer.php'; ?>
