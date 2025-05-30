<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

// Check if activity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: classes.php');
    exit;
}

$activityId = (int)$_GET['id'];

// Get activity details
$activity = getActivityById($activityId, $_SESSION['user_id']);

// Redirect if activity not found or doesn't belong to this teacher
if (!$activity) {
    header('Location: classes.php?error=Activity not found');
    exit;
}

$classId = $activity['class_id'];
$moduleId = $activity['module_id'];

// Process form submission for grading
$message = '';
$messageType = 'info'; // Add proper message type for alerts

// Direct navigation function for activities
function goToActivity($activityId) {
    header("Location: edit_activity.php?id=$activityId");
    exit;
}

// Add a button for emergency navigation
$emergencyNavButton = '<a href="../emergency_navigation.php" class="btn btn-warning btn-sm">Emergency Navigation</a>';

// Fetch submissions for the activity
$submissions = getSubmissionsForActivity($activityId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Activity Submissions</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Activity Submissions</h1>
            <a href="module_activities.php?module_id=<?php echo $moduleId; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Activities
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Module:</strong> <?php echo htmlspecialchars($activity['module_title'] ?? ''); ?></p>
                        <p><strong>Type:</strong> <?php echo getActivityTypeName($activity['activity_type']); ?></p>
                        <p><strong>Due Date:</strong> <?php echo $activity['due_date'] ? date('M j, Y', strtotime($activity['due_date'])) : 'No deadline'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Max Score:</strong> <?php echo $activity['max_score']; ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                                <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Activity Description:</h6>
                    <p><?php echo nl2br(htmlspecialchars($activity['description'] ?? '')); ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Student Submissions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($submissions)): ?>
                    <p class="text-muted">No submissions found for this activity.</p>
                <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Submission Date</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></td>
                                    <td><?php echo $submission['graded'] ? $submission['grade'] . '%' : 'Pending'; ?></td>
                                    <td>
                                        <a href="view_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript resources -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
</body>
</html>
