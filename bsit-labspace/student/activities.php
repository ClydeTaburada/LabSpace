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

// Initialize arrays to store all activities
$upcomingActivities = [];
$ongoingActivities = [];
$completedActivities = [];

// Get submissions to track completed activities
$submissions = getStudentRecentSubmissions($_SESSION['user_id'], 100); // Get more submissions to track completion status
$submittedActivityIds = [];

foreach ($submissions as $submission) {
    if (isset($submission['activity_id'])) {
        $submittedActivityIds[] = $submission['activity_id'];
    }
}

// Fetch activities for each class
foreach ($enrolledClasses as $class) {
    $classId = $class['id'];
    $modules = getPublishedModules($classId);
    
    foreach ($modules as $module) {
        $activities = getPublishedActivities($module['id']);
        
        foreach ($activities as $activity) {
            // Add class and module info to the activity for display
            $activity['class_name'] = $class['subject_code'] . ' - ' . $class['subject_name'];
            $activity['module_name'] = $module['title'];
            $activity['class_id'] = $classId;
            
            // Determine if the activity has been submitted
            $isSubmitted = in_array($activity['id'], $submittedActivityIds);
            
            // Categorize activities
            if ($isSubmitted) {
                $completedActivities[] = $activity;
            } else if ($activity['due_date'] && strtotime($activity['due_date']) < time()) {
                // Past due date
                $upcomingActivities[] = $activity;
            } else {
                // No due date or future due date
                $ongoingActivities[] = $activity;
            }
        }
    }
}

// Sort upcoming activities by due date
usort($upcomingActivities, function($a, $b) {
    if (empty($a['due_date']) && empty($b['due_date'])) return 0;
    if (empty($a['due_date'])) return 1;
    if (empty($b['due_date'])) return -1;
    return strtotime($a['due_date']) - strtotime($b['due_date']);
});

$pageTitle = "My Activities";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Activities</h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if (empty($enrolledClasses)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You are not enrolled in any classes yet.
            <p class="mt-2 mb-0">
                <a href="join_class.php" class="btn btn-sm btn-primary">Join a Class</a> to see activities.
            </p>
        </div>
    <?php else: ?>
        <!-- Activity Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-9">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" data-filter="all">All Activities</button>
                            <button type="button" class="btn btn-outline-warning" data-filter="upcoming">Upcoming</button>
                            <button type="button" class="btn btn-outline-success" data-filter="completed">Completed</button>
                            <button type="button" class="btn btn-outline-secondary" data-filter="coding">Coding Tasks</button>
                            <button type="button" class="btn btn-outline-info" data-filter="quiz">Quizzes</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="activity-search" class="form-control" placeholder="Search activities...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overdue Activities Section -->
        <div class="card mb-4 activity-section" id="overdue-section">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Overdue Activities</h5>
            </div>
            <div class="card-body">
                <?php
                // Filter for overdue activities
                $overdueActivities = array_filter($upcomingActivities, function($activity) {
                    return !empty($activity['due_date']) && isActivityOverdue($activity['due_date']);
                });
                
                if (empty($overdueActivities)): 
                ?>
                    <p class="text-muted">No overdue activities. Good job keeping up!</p>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <strong>Warning:</strong> You have <?php echo count($overdueActivities); ?> overdue activities that need attention.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Class / Module</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueActivities as $activity): ?>
                                    <tr class="activity-item table-danger" 
                                        data-activity-title="<?php echo htmlspecialchars(strtolower($activity['title'])); ?>" 
                                        data-activity-type="<?php echo $activity['activity_type']; ?>">
                                        <td>
                                            <i class="fas fa-exclamation-circle text-danger me-1"></i>
                                            <?php echo htmlspecialchars($activity['title']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($activity['class_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['module_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                <?php echo getActivityTypeName($activity['activity_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-danger fw-bold">
                                                <?php echo date('M j, Y', strtotime($activity['due_date'])); ?>
                                                <span class="badge bg-danger">OVERDUE</span>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Complete Now
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

        <!-- Upcoming Activities Section -->
        <div class="card mb-4 activity-section" id="upcoming-section">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Upcoming Activities</h5>
            </div>
            <div class="card-body">
                <?php 
                // Filter to show non-overdue activities
                $nonOverdueActivities = array_filter($upcomingActivities, function($activity) {
                    return empty($activity['due_date']) || !isActivityOverdue($activity['due_date']);
                });
                
                if (empty($nonOverdueActivities)): 
                ?>
                    <p class="text-muted">No upcoming activities at the moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Class / Module</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nonOverdueActivities as $activity): ?>
                                    <tr class="activity-item" 
                                        data-activity-title="<?php echo htmlspecialchars(strtolower($activity['title'])); ?>" 
                                        data-activity-type="<?php echo $activity['activity_type']; ?>">
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($activity['class_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['module_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                <?php echo getActivityTypeName($activity['activity_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($activity['due_date']): ?>
                                                <span class="<?php echo getDueDateStatusClass($activity['due_date']); ?>">
                                                    <?php echo date('M j, Y', strtotime($activity['due_date'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
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
        
        <!-- Ongoing Activities Section -->
        <div class="card mb-4 activity-section" id="ongoing-section">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Ongoing Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ongoingActivities)): ?>
                    <p class="text-muted">No ongoing activities at the moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Class / Module</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ongoingActivities as $activity): ?>
                                    <tr class="activity-item" 
                                        data-activity-title="<?php echo htmlspecialchars(strtolower($activity['title'])); ?>" 
                                        data-activity-type="<?php echo $activity['activity_type']; ?>">
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($activity['class_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['module_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                <?php echo getActivityTypeName($activity['activity_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($activity['due_date']): ?>
                                                <?php echo date('M j, Y', strtotime($activity['due_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
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
        
        <!-- Completed Activities Section -->
        <div class="card mb-4 activity-section" id="completed-section">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Completed Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($completedActivities)): ?>
                    <p class="text-muted">You haven't completed any activities yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Class / Module</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completedActivities as $activity): ?>
                                    <tr class="activity-item" 
                                        data-activity-title="<?php echo htmlspecialchars(strtolower($activity['title'])); ?>" 
                                        data-activity-type="<?php echo $activity['activity_type']; ?>">
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($activity['class_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['module_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                                <?php echo getActivityTypeName($activity['activity_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($activity['due_date']): ?>
                                                <?php echo date('M j, Y', strtotime($activity['due_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
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
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity filtering
    const filterButtons = document.querySelectorAll('[data-filter]');
    const activityItems = document.querySelectorAll('.activity-item');
    const activitySections = document.querySelectorAll('.activity-section');
    const searchInput = document.getElementById('activity-search');
    
    // Filter activities based on selected filter and search text
    function filterActivities() {
        const activeFilter = document.querySelector('[data-filter].active').getAttribute('data-filter');
        const searchText = searchInput.value.toLowerCase();
        
        if (activeFilter === 'all' && searchText === '') {
            // Show all sections and items
            activitySections.forEach(section => section.style.display = 'block');
            activityItems.forEach(item => item.style.display = 'table-row');
            return;
        }
        
        // First hide all sections to re-evaluate
        if (activeFilter !== 'all') {
            activitySections.forEach(section => section.style.display = 'none');
            
            // Show specific section based on filter
            if (activeFilter === 'upcoming') document.getElementById('upcoming-section').style.display = 'block';
            if (activeFilter === 'completed') document.getElementById('completed-section').style.display = 'block';
            if (activeFilter === 'coding' || activeFilter === 'quiz') {
                activitySections.forEach(section => section.style.display = 'block');
            }
        } else {
            activitySections.forEach(section => section.style.display = 'block');
        }
        
        // Filter items based on criteria
        activityItems.forEach(item => {
            const title = item.getAttribute('data-activity-title');
            const type = item.getAttribute('data-activity-type');
            
            const matchesFilter = 
                activeFilter === 'all' || 
                (activeFilter === 'coding' && type === 'coding') ||
                (activeFilter === 'quiz' && type === 'quiz');
            
            const matchesSearch = searchText === '' || title.includes(searchText);
            
            item.style.display = (matchesFilter && matchesSearch) ? 'table-row' : 'none';
        });
    }
    
    // Add click event listeners to filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Apply filters
            filterActivities();
        });
    });
    
    // Add search functionality
    searchInput.addEventListener('input', filterActivities);
    
    // Make activity rows clickable
    activityItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Only navigate if the click wasn't on a button or link
            if (!e.target.closest('a, button')) {
                const viewButton = this.querySelector('a.btn-primary');
                if (viewButton) {
                    window.location.href = viewButton.getAttribute('href');
                }
            }
        });
        
        // Make cursor a pointer to indicate clickable
        item.style.cursor = 'pointer';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
