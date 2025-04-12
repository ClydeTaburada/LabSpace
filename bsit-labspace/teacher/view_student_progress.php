<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';
require_once '../includes/functions/student_progress_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Check if student ID and class ID are provided
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id']) || 
    !isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    header('Location: classes.php?error=Invalid student or class ID');
    exit;
}   

$studentId = (int)$_GET['student_id'];
$classId = (int)$_GET['class_id'];

// Get class details and verify teacher owns it
$class = getClassById($classId, $_SESSION['user_id']);

// Redirect if class not found or doesn't belong to this teacher
if (!$class) {
    header('Location: classes.php?error=Class not found');
    exit;
}

// Get student details
$student = getStudentById($studentId);

// Verify student is enrolled in this class
if (!$student || !isStudentEnrolledInClass($studentId, $classId)) {
    header('Location: view_students.php?class_id=' . $classId . '&error=Student not found or not enrolled in this class');
    exit;
}

// Get student progress data for this class
$progressData = getStudentClassProgress($studentId, $classId);

// Get all student submissions for activities in this class
$submissions = getStudentClassSubmissions($studentId, $classId);

// Calculate aggregate statistics
$totalActivities = $progressData['total_activities'] ?? 0;
$completedActivities = $progressData['completed_activities'] ?? 0;
$avgGrade = $progressData['average_grade'] ?? 0;
$completionPercentage = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100) : 0;

$pageTitle = "Student Progress - " . $student['first_name'] . " " . $student['last_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Student Progress</h1>
        <div>
            <a href="view_students.php?class_id=<?php echo $classId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Students List
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Student Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_number']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student['year_level']); ?></p>
                    <p><strong>Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                    <p><strong>Enrolled Date:</strong> <?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Class: <?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="h1 mb-0"><?php echo $completionPercentage; ?>%</div>
                    <p class="text-muted">Progress</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $completionPercentage; ?>%" 
                             aria-valuenow="<?php echo $completionPercentage; ?>" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="h1 mb-0"><?php echo $completedActivities; ?>/<?php echo $totalActivities; ?></div>
                    <p class="text-muted">Activities Completed</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="h1 mb-0"><?php echo $avgGrade ?? '0'; ?>%</div>
                    <p class="text-muted">Average Grade</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Module-by-module progress -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Module Progress</h5>
        </div>
        <div class="card-body">
            <?php if (empty($progressData['modules'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No modules available for this class.
                </div>
            <?php else: ?>
                <div class="accordion" id="moduleAccordion">
                    <?php foreach ($progressData['modules'] as $index => $module): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $index; ?>" 
                                        aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                        aria-controls="collapse<?php echo $index; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <?php echo htmlspecialchars($module['title']); ?>
                                        </div>
                                        <span class="ms-3 badge bg-<?php echo getCompletionBadgeColor($module['completion_percentage']); ?>">
                                            <?php echo $module['completion_percentage']; ?>% Complete
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                 aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#moduleAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($module['activities'])): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No activities in this module.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Activity</th>
                                                        <th>Type</th>
                                                        <th>Due Date</th>
                                                        <th>Status</th>
                                                        <th>Auto Grade</th>
                                                        <th>Final Grade</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($module['activities'] as $activity): ?>
                                                        <?php 
                                                            $hasSubmission = isset($activity['submission']);
                                                            $isGraded = $hasSubmission && $activity['submission']['graded']; 
                                                            
                                                            // Add emergency navigation data attribute
                                                            $emergencyUrl = "../emergency_activity.php?id=" . $activity['id'];
                                                        ?>
                                                        <tr data-activity-id="<?php echo $activity['id']; ?>" class="activity-item">
                                                            <td>
                                                                <?php echo htmlspecialchars($activity['title']); ?>
                                                                <a href="<?php echo $emergencyUrl; ?>" class="direct-activity-link btn-sm mt-1">
                                                                    <i class="fas fa-external-link-alt"></i> Emergency Access
                                                                </a>
                                                            </td>
                                                            <td><?php echo getActivityTypeName($activity['activity_type']); ?></td>
                                                            <td>
                                                                <?php echo $activity['due_date'] ? date('M j, Y', strtotime($activity['due_date'])) : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($hasSubmission): ?>
                                                                    <span class="badge bg-success">Submitted</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Not Submitted</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo ($hasSubmission && $activity['submission']['auto_grade'] !== null) ? 
                                                                    $activity['submission']['auto_grade'] . '%' : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $isGraded ? 
                                                                    $activity['submission']['grade'] . '%' : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($hasSubmission): ?>
                                                                    <a href="../teacher/view_submission.php?id=<?php echo $activity['submission']['id']; ?>" 
                                                                       class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-eye"></i> View Submission
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                                        <i class="fas fa-eye-slash"></i> No Submission
                                                                    </button>
                                                                <?php endif; ?>
                                                                <a href="../teacher/activity_details.php?id=<?php echo $activity['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-info ms-1">
                                                                    <i class="fas fa-info-circle"></i> Activity Details
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
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Submissions -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Recent Submissions</h5>
        </div>
        <div class="card-body">
            <?php if (empty($submissions)): ?>
                <p class="text-muted">No submissions found.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($submissions as $submission): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($submission['activity_title']); ?></strong>
                            <br>
                            <small class="text-muted">Submitted on: <?php echo date('M j, Y', strtotime($submission['submission_date'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Ensure modules load properly
document.addEventListener('DOMContentLoaded', function() {
    // Add loading indicator for modules
    const moduleButtons = document.querySelectorAll('.accordion-button');
    moduleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            // Only add loading indicator if accordion is closed
            if (target && !target.classList.contains('show')) {
                console.log("Opening module accordion: " + targetId);
                
                const accordionBody = target.querySelector('.accordion-body');
                if (accordionBody) {
                    // Store original content if not already stored
                    if (!accordionBody.hasAttribute('data-original-content')) {
                        accordionBody.setAttribute('data-original-content', accordionBody.innerHTML);
                    }
                    
                    // Show loading indicator
                    accordionBody.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading module content...</div></div>';
                    
                    // Restore original content after short timeout
                    setTimeout(() => {
                        const originalContent = accordionBody.getAttribute('data-original-content');
                        if (originalContent) {
                            accordionBody.innerHTML = originalContent;
                        }
                    }, 300);
                }
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
