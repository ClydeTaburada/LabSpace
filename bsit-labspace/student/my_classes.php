<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Get student's enrolled classes
$enrolledClasses = getStudentClasses($_SESSION['user_id']);

$pageTitle = "My Classes";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Classes</h1>
        <div>
            <a href="join_class.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Join New Class
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if (empty($enrolledClasses)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You are not enrolled in any classes yet.
            <p class="mt-2 mb-0">
                <a href="join_class.php" class="btn btn-sm btn-primary">Join a Class</a> to get started.
            </p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($enrolledClasses as $class): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <span class="badge bg-secondary">Section: <?php echo htmlspecialchars($class['section']); ?></span>
                                    <span class="badge bg-info text-dark">Year <?php echo $class['year_level']; ?></span>
                                </div>
                                <span class="badge bg-primary">SY <?php echo htmlspecialchars($class['school_year'] ?? 'Not specified'); ?></span>
                            </div>
                            
                            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($class['teacher_name']); ?></p>
                            <p><strong>Enrolled since:</strong> <?php echo date('M j, Y', strtotime($class['enrollment_date'])); ?></p>
                            
                            <?php
                            // Get progress for this class
                            $modules = getPublishedModules($class['id']);
                            $totalActivities = 0;
                            $moduleList = [];
                            
                            foreach ($modules as $module) {
                                $activities = getPublishedActivities($module['id']);
                                $totalActivities += count($activities);
                                $moduleList[] = $module;
                            }
                            ?>
                            
                            <div class="mt-3">
                                <p><strong>Total Modules:</strong> <?php echo count($moduleList); ?></p>
                                <p><strong>Total Activities:</strong> <?php echo $totalActivities; ?></p>
                            </div>
                            
                            <?php
                            // Get upcoming deadlines
                            $upcomingDeadlines = getUpcomingDeadlinesByClass($class['id'], $_SESSION['user_id'], 2);
                            if (!empty($upcomingDeadlines)):
                            ?>
                                <div class="mt-3">
                                    <h6 class="border-bottom pb-2">Upcoming Deadlines</h6>
                                    <ul class="list-unstyled">
                                        <?php foreach ($upcomingDeadlines as $deadline): ?>
                                            <li class="mb-2">
                                                <?php if (!empty($deadline['due_date']) && isActivityOverdue($deadline['due_date'])): ?>
                                                    <i class="fas fa-exclamation-circle text-danger"></i>
                                                    <a href="view_activity.php?id=<?php echo $deadline['id']; ?>" class="text-danger fw-bold">
                                                        <?php echo htmlspecialchars($deadline['title']); ?>
                                                        <span class="badge bg-danger">OVERDUE</span>
                                                    </a>
                                                <?php else: ?>
                                                    <i class="fas fa-clock text-warning"></i>
                                                    <a href="view_activity.php?id=<?php echo $deadline['id']; ?>">
                                                        <?php echo htmlspecialchars($deadline['title']); ?>
                                                    </a>
                                                <?php endif; ?>
                                                <br>
                                                <small class="<?php echo getDueDateStatusClass($deadline['due_date']); ?>">
                                                    Due: <?php echo date('M j, Y', strtotime($deadline['due_date'])); ?>
                                                </small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Class
                            </a>
                            <a href="#" class="btn btn-outline-secondary class-details-btn" data-bs-toggle="modal" data-bs-target="#classInfoModal" 
                               data-class-id="<?php echo $class['id']; ?>"
                               data-class-code="<?php echo htmlspecialchars($class['class_code']); ?>"
                               data-subject-code="<?php echo htmlspecialchars($class['subject_code']); ?>"
                               data-subject-name="<?php echo htmlspecialchars($class['subject_name']); ?>"
                               data-section="<?php echo htmlspecialchars($class['section']); ?>"
                               data-teacher="<?php echo htmlspecialchars($class['teacher_name']); ?>"
                               data-enrolled="<?php echo date('M j, Y', strtotime($class['enrollment_date'])); ?>">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Class Info Modal -->
        <div class="modal fade" id="classInfoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="classModalTitle">Class Information</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Class Code:</th>
                                <td id="classCode"></td>
                            </tr>
                            <tr>
                                <th>Subject:</th>
                                <td id="subjectInfo"></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td id="sectionInfo"></td>
                            </tr>
                            <tr>
                                <th>Teacher:</th>
                                <td id="teacherInfo"></td>
                            </tr>
                            <tr>
                                <th>Enrolled Date:</th>
                                <td id="enrolledDate"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="#" id="viewClassBtn" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Class
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle class detail buttons
    const classDetailBtns = document.querySelectorAll('.class-details-btn');
    
    classDetailBtns.forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.getAttribute('data-class-id');
            const classCode = this.getAttribute('data-class-code');
            const subjectCode = this.getAttribute('data-subject-code');
            const subjectName = this.getAttribute('data-subject-name');
            const section = this.getAttribute('data-section');
            const teacher = this.getAttribute('data-teacher');
            const enrolled = this.getAttribute('data-enrolled');
            
            // Update modal content
            document.getElementById('classCode').textContent = classCode;
            document.getElementById('subjectInfo').textContent = `${subjectCode} - ${subjectName}`;
            document.getElementById('sectionInfo').textContent = section;
            document.getElementById('teacherInfo').textContent = teacher;
            document.getElementById('enrolledDate').textContent = enrolled;
            
            // Update view class button
            document.getElementById('viewClassBtn').href = `view_class.php?id=${classId}`;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
