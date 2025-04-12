<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Get student's enrolled classes - Add error handling
$enrolledClasses = getStudentClasses($_SESSION['user_id']) ?: [];

// Get student's progress data - Add error handling
$progressData = getStudentProgress($_SESSION['user_id']) ?: ['classes' => [], 'modules' => []];

// Fetch recent submissions using the existing function - Add error handling
$recentActivities = getStudentRecentSubmissions($_SESSION['user_id'], 5) ?: [];

// Get upcoming deadlines - Make sure we're using the function from activity_functions.php
$upcomingDeadlines = getUpcomingDeadlines($_SESSION['user_id'], 5) ?: [];

$pageTitle = "Student Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Student Dashboard</h1>
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
                    <h5 class="card-title"><i class="fas fa-book-open"></i> My Classes</h5>
                    <p class="card-text">View your enrolled classes and learning materials.</p>
                    <a href="my_classes.php" class="btn btn-primary">View Classes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-tasks"></i> Activities</h5>
                    <p class="card-text">Access assignments, quizzes, and coding tasks.</p>
                    <a href="activities.php" class="btn btn-primary">View Activities</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Progress</h5>
                    <p class="card-text">Track your academic progress and view feedback.</p>
                    <a href="progress.php" class="btn btn-primary">View Progress</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enrolled Classes Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Enrolled Classes</h5>
                    <a href="join_class.php" class="btn btn-sm btn-light">
                        <i class="fas fa-plus"></i> Join New Class
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolledClasses)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You are not enrolled in any classes yet.
                            <p class="mt-2 mb-0">
                                <a href="join_class.php" class="btn btn-sm btn-primary">Join a Class</a> to get started.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="classAccordion">
                            <?php foreach ($enrolledClasses as $index => $class): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                                type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $index; ?>" 
                                                aria-expanded="<?php echo $index == 0 ? 'true' : 'false'; ?>" 
                                                aria-controls="collapse<?php echo $index; ?>">
                                            <div class="d-flex justify-content-between w-100 me-3">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($class['subject_code']); ?>: 
                                                    <?php echo htmlspecialchars($class['subject_name']); ?></strong>
                                                </div>
                                                <div>
                                                    <span class="badge bg-secondary me-2">Section: <?php echo htmlspecialchars($class['section']); ?></span>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo htmlspecialchars($class['teacher_name']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" 
                                         class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" 
                                         aria-labelledby="heading<?php echo $index; ?>" 
                                         data-bs-parent="#classAccordion">
                                        <div class="accordion-body">
                                            <!-- Class modules -->
                                            <?php 
                                            // Make sure to get only published modules
                                            $modules = getPublishedModules($class['id']); 
                                            if (empty($modules)): 
                                            ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i> No modules are available yet for this class.
                                                </div>
                                            <?php else: ?>
                                                <div class="list-group mb-3">
                                                    <?php foreach ($modules as $module): ?>
                                                        <div class="list-group-item">
                                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                                <h6 class="mb-1">
                                                                    <i class="fas fa-book me-2"></i>
                                                                    <?php echo htmlspecialchars($module['title']); ?>
                                                                </h6>
                                                                <span class="badge bg-primary rounded-pill">
                                                                    <?php echo $module['activity_count']; ?> activities
                                                                </span>
                                                            </div>
                                                            <?php if (!empty($module['description'])): ?>
                                                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($module['description']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Activities in this module -->
                                                            <?php 
                                                            // Get only published activities
                                                            $activities = getPublishedActivities($module['id']);
                                                            if (!empty($activities)): 
                                                            ?>
                                                                <div class="mt-2">
                                                                    <div class="list-group list-group-flush activity-list">
                                                                        <?php foreach ($activities as $activity): ?>
                                                                            <div class="list-group-item list-group-item-action activity-item" data-activity-id="<?php echo $activity['id']; ?>">
                                                                                <div class="d-flex w-100 justify-content-between">
                                                                                    <div>
                                                                                        <i class="<?php echo getActivityIcon($activity['activity_type']); ?> me-2"></i>
                                                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                                                        <?php if ($activity['due_date']): ?>
                                                                                            <small class="text-warning ms-2">
                                                                                                <i class="fas fa-clock"></i> Due: <?php echo date('M j, Y', strtotime($activity['due_date'])); ?>
                                                                                            </small>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                                                        <?php echo getActivityTypeName($activity['activity_type']); ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View Full Class
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-2">
        <!-- Progress Tracker -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">My Progress</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($progressData['classes'])): ?>
                        <p class="text-muted">You need to enroll in classes to track your progress.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($progressData['classes'] as $classId => $class): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-primary">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>Overall Completion:</div>
                                                <div class="text-end">
                                                    <strong><?php echo $class['completion_percentage']; ?>%</strong>
                                                    <span class="text-muted">(<?php echo $class['completed_activities']; ?>/<?php echo $class['total_activities']; ?>)</span>
                                                </div>
                                            </div>
                                            <div class="progress mb-4" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $class['completion_percentage']; ?>%;" 
                                                     aria-valuenow="<?php echo $class['completion_percentage']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                            
                                            <?php if (isset($progressData['modules'][$classId])): ?>
                                                <h6 class="mt-3">Modules:</h6>
                                                <?php foreach ($progressData['modules'][$classId] as $module): ?>
                                                    <?php if ($module['total_activities'] > 0): ?>
                                                        <div class="mb-2">
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <div><?php echo htmlspecialchars($module['module_title']); ?></div>
                                                                <div><?php echo $module['completion_percentage']; ?>%</div>
                                                            </div>
                                                            <div class="progress" style="height: 5px;">
                                                                <div class="progress-bar" role="progressbar" 
                                                                     style="width: <?php echo $module['completion_percentage']; ?>%;" 
                                                                     aria-valuenow="<?php echo $module['completion_percentage']; ?>" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            
                                            <div class="mt-3">
                                                <a href="view_class.php?id=<?php echo $classId; ?>" class="btn btn-sm btn-outline-primary">
                                                    View Class Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Deadlines Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Upcoming Deadlines</h5>
                </div>
                <div class="card-body">
                    <?php
                    $upcomingDeadlines = getUpcomingDeadlines($_SESSION['user_id'], 5);
                    if (empty($upcomingDeadlines)):
                    ?>
                        <p class="card-text text-muted">No upcoming deadlines.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($upcomingDeadlines as $item): ?>
                                <a href="view_activity.php?id=<?php echo $item['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="<?php echo getDueDateStatusClass($item['due_date']); ?>">
                                            <?php echo date('M j, Y', strtotime($item['due_date'])); ?>
                                            <?php if (isActivityOverdue($item['due_date'])): ?>
                                                <span class="badge bg-danger ms-1">OVERDUE</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($item['subject_code'] . ' - ' . $item['subject_name']); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Grades</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">No recent grades.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Recent Activities Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Recent Activities</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivities)): ?>
                        <p class="text-muted">No recent activities found.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($recentActivities as $activity): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($activity['activity_title']); ?></strong>
                                    <br>
                                    <small class="text-muted">Submitted on: <?php echo date('M j, Y', strtotime($activity['updated_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Submissions Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivities)): ?>
                        <p class="text-muted">No recent submissions found.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($recentActivities as $activity): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($activity['activity_title']); ?></strong>
                                    <br>
                                    <small class="text-muted">Submitted on: <?php echo date('M j, Y', strtotime($activity['updated_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Direct navigation for activity items
    document.querySelectorAll('.activity-item').forEach(activityItem => {
        const activityId = activityItem.dataset.activityId;
        
        if (activityId) {
            activityItem.style.cursor = 'pointer';
            
            activityItem.addEventListener('click', function(e) {
                // Prevent default behavior if not clicking on a link or button
                if (!e.target.closest('a, button')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Navigating to activity:', activityId);
                    
                    // Navigate directly to the activity page
                    window.location.href = `view_activity.php?id=${activityId}`;
                }
            });
        }
    });
    
    // Debug information to console to help troubleshoot
    console.log('Dashboard loaded with:');
    console.log('- Classes:', <?php echo json_encode(array_keys($progressData['classes'] ?? [])); ?>);
    console.log('- Recent activities:', <?php echo json_encode(count($recentActivities)); ?>);
    console.log('- Upcoming deadlines:', <?php echo json_encode(count($upcomingDeadlines)); ?>);
});
</script>

<?php include '../includes/footer.php'; ?>