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

// Check if class ID is provided
if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    header('Location: classes.php');
    exit;
}

$classId = (int)$_GET['class_id'];

// Get class details
$class = getClassById($classId, $_SESSION['user_id']);

// Redirect if class not found or doesn't belong to this teacher
if (!$class) {
    header('Location: classes.php?error=Class not found');
    exit;
}

// Get enrolled students
$students = getEnrolledStudents($classId, $_SESSION['user_id']);

// Export to CSV functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $class['subject_code'] . '_' . $class['section'] . '_students.csv"');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output header row
    fputcsv($output, ['Student ID', 'Last Name', 'First Name', 'Email', 'Year Level', 'Section', 'Enrolled Date']);
    
    // Output each student
    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_number'],
            $student['last_name'],
            $student['first_name'],
            $student['email'],
            $student['year_level'],
            $student['section'],
            date('Y-m-d', strtotime($student['enrollment_date']))
        ]);
    }
    
    fclose($output);
    exit;
}

$pageTitle = "Class Students - " . $class['subject_code'] . ": " . $class['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Student Roster</h1>
        <div>
            <a href="classes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>
    
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
            <h5 class="mb-0">Enrolled Students (<?php echo count($students); ?>)</h5>
            <div>
                <?php if (!empty($students)): ?>
                    <a href="?class_id=<?php echo $classId; ?>&export=csv" class="btn btn-sm btn-light">
                        <i class="fas fa-download"></i> Export to CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No students have enrolled in this class yet.
                    <p class="mt-2 mb-0">Students can join using the class code: <strong><?php echo $class['class_code']; ?></strong></p>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="studentSearch" class="form-control" placeholder="Search students...">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Year Level</th>
                                <th>Section</th>
                                <th>Enrolled Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['year_level']; ?></td>
                                    <td><?php echo htmlspecialchars($student['section']); ?></td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?>
                                    </td>
                                    <td>
                                        <a href="view_student_progress.php?student_id=<?php echo $student['id']; ?>&class_id=<?php echo $classId; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-chart-line"></i> View Progress
                                        </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('studentSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#studentsTable tbody tr');
            
            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
