<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if class ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$classId = (int)$_GET['id'];

// Store current class ID in session for navigation recovery
$_SESSION['last_class_id'] = $classId;

// Get class details and verify student is enrolled
$class = getStudentClassById($classId, $_SESSION['user_id']);

// Redirect if class not found or student is not enrolled
if (!$class) {
    header('Location: dashboard.php?error=Class not found or you are not enrolled');
    exit;
}

// Get modules for this class
$modules = getModulesByClassId($classId);

$pageTitle = "View Class - " . $class['subject_code'] . ": " . $class['subject_name'];
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?></h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Class Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($class['subject_code']); ?></p>
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($class['section']); ?></p>
                            <p><strong>Schedule:</strong> <?php echo isset($class['schedule']) ? htmlspecialchars($class['schedule']) : 'Not available'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Instructor:</strong> <?php echo isset($class['instructor_name']) ? htmlspecialchars($class['instructor_name']) : 'Not available'; ?></p>
                            <p><strong>Room:</strong> <?php echo isset($class['room']) ? htmlspecialchars($class['room']) : 'Not available'; ?></p>
                            <p><strong>Academic Term:</strong> <?php echo isset($class['academic_term']) ? htmlspecialchars($class['academic_term']) : 'Not available'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modules Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Modules & Activities</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-info">
                            No modules have been added to this class yet.
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="modulesAccordion">
                            <?php foreach ($modules as $index => $module): ?>
                                <?php $activities = getActivitiesByModuleId($module['id']); ?>
                                <div class="accordion-item mb-3">
                                    <h2 class="accordion-header" id="heading<?php echo $module['id']; ?>">
                                        <button class="accordion-button <?php echo ($index > 0) ? 'collapsed' : ''; ?>" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $module['id']; ?>" 
                                                aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" 
                                                aria-controls="collapse<?php echo $module['id']; ?>">
                                            <strong><?php echo htmlspecialchars($module['title']); ?></strong>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $module['id']; ?>" 
                                         class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" 
                                         aria-labelledby="heading<?php echo $module['id']; ?>" 
                                         data-bs-parent="#modulesAccordion">
                                        <div class="accordion-body">
                                            <?php if (!empty($module['description'])): ?>
                                                <div class="mb-3">
                                                    <p><?php echo nl2br(htmlspecialchars($module['description'])); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (empty($activities)): ?>
                                                <div class="alert alert-info">
                                                    No activities have been added to this module yet.
                                                </div>
                                            <?php else: ?>
                                                <div class="list-group">
                                                    <?php foreach ($activities as $activity): ?>
                                                        <a href="view_activity.php?id=<?php echo $activity['id']; ?>" 
                                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php echo ucfirst(htmlspecialchars($activity['activity_type'] ?? 'Activity')); ?> | 
                                                                    Due: <?php echo isset($activity['due_date']) ? date('M d, Y, h:i A', strtotime($activity['due_date'])) : 'No deadline'; ?>
                                                                </small>
                                                            </div>
                                                            <span class="badge bg-primary rounded-pill">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Jump to specific activity form -->
                    <div class="mt-4">
                        <h6>Quick Jump to Activity</h6>
                        <div class="input-group">
                            <select class="form-select" id="direct-activity-id">
                                <option value="">Select an activity...</option>
                                <?php foreach ($modules as $module): ?>
                                    <?php $moduleActivities = getActivitiesByModuleId($module['id']); ?>
                                    <?php if (!empty($moduleActivities)): ?>
                                        <optgroup label="<?php echo htmlspecialchars($module['title']); ?>">
                                            <?php foreach ($moduleActivities as $activity): ?>
                                                <option value="<?php echo $activity['id']; ?>">
                                                    <?php echo htmlspecialchars($activity['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="button" onclick="goToActivity()">Go</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Class Information Sidebar -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Class Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Modules
                            <span class="badge bg-primary rounded-pill"><?php echo count($modules); ?></span>
                        </li>
                        <?php
                        $totalActivities = 0;
                        foreach ($modules as $module) {
                            $activities = getActivitiesByModuleId($module['id']);
                            $totalActivities += count($activities);
                        }
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Activities
                            <span class="badge bg-primary rounded-pill"><?php echo $totalActivities; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function goToActivity() {
    const activityId = document.getElementById('direct-activity-id').value;
    if (activityId) {
        // Store activity ID in local storage for emergency recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
        } catch (e) {
            console.error('Storage error:', e);
        }
        
        // Navigate to the activity
        window.location.href = "view_activity.php?id=" + activityId;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
