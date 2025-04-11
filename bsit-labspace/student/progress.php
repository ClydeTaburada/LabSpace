<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Get student's progress data
$progressData = getStudentProgress($_SESSION['user_id']);

// Get all student submissions
$pdo = getDbConnection();
$stmt = $pdo->prepare("
    SELECT 
        sub.*, 
        a.title AS activity_title, 
        a.activity_type,
        m.title AS module_title,
        s.code AS subject_code,
        s.name AS subject_name
    FROM 
        activity_submissions sub
        JOIN activities a ON sub.activity_id = a.id
        JOIN modules m ON a.module_id = m.id
        JOIN classes c ON m.class_id = c.id
        JOIN subjects s ON c.subject_id = s.id
    WHERE 
        sub.student_id = ?
    ORDER BY
        sub.updated_at DESC
    LIMIT 15
");
$stmt->execute([$_SESSION['user_id']]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall progress statistics
$totalActivities = 0;
$completedActivities = 0;
$totalGraded = 0;
$totalScore = 0;

foreach ($progressData['classes'] as $class) {
    $totalActivities += $class['total_activities'];
    $completedActivities += $class['completed_activities'];
}

foreach ($submissions as $submission) {
    if ($submission['graded']) {
        $totalGraded++;
        $totalScore += $submission['grade'];
    }
}

$overallCompletion = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100) : 0;
$averageGrade = $totalGraded > 0 ? round($totalScore / $totalGraded) : 0;

$pageTitle = "My Progress";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Progress</h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Progress Summary -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Progress Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 mb-0"><?php echo $overallCompletion; ?>%</div>
                            <div class="text-muted">Overall Completion</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 mb-0"><?php echo $completedActivities; ?>/<?php echo $totalActivities; ?></div>
                            <div class="text-muted">Activities Completed</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 mb-0"><?php echo $totalGraded; ?></div>
                            <div class="text-muted">Submissions Graded</div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h1 mb-0"><?php echo $averageGrade; ?>%</div>
                            <div class="text-muted">Average Grade</div>
                        </div>
                    </div>

                    <div class="progress mt-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $overallCompletion; ?>%;" 
                             aria-valuenow="<?php echo $overallCompletion; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo $overallCompletion; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Class Progress -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Class Progress</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($progressData['classes'])): ?>
                        <p class="text-muted">You need to enroll in classes to track your progress.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Completed</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progressData['classes'] as $classId => $class): ?>
                                        <tr>
                                            <td>
                                                <a href="view_class.php?id=<?php echo $classId; ?>">
                                                    <?php echo htmlspecialchars($class['subject_code']); ?>
                                                </a>
                                                <div class="small text-muted"><?php echo htmlspecialchars($class['subject_name']); ?></div>
                                            </td>
                                            <td>
                                                <?php echo $class['completed_activities']; ?>/<?php echo $class['total_activities']; ?>
                                            </td>
                                            <td class="w-50">
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $class['completion_percentage']; ?>%;" 
                                                         aria-valuenow="<?php echo $class['completion_percentage']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo $class['completion_percentage']; ?>%
                                                    </div>
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

        <!-- Recent Submissions -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <p class="text-muted">No submissions yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Date</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <a href="view_activity.php?id=<?php echo $submission['activity_id']; ?>">
                                                    <?php echo htmlspecialchars($submission['activity_title']); ?>
                                                </a>
                                                <div class="small text-muted"><?php echo htmlspecialchars($submission['subject_code']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($submission['updated_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($submission['graded']): ?>
                                                    <span class="badge bg-success"><?php echo $submission['grade']; ?>%</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
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

    <!-- Module Detail Progress -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Module Progress</h5>
        </div>
        <div class="card-body">
            <?php if (empty($progressData['modules'])): ?>
                <p class="text-muted">No modules available yet.</p>
            <?php else: ?>
                <div class="accordion" id="moduleProgressAccordion">
                    <?php foreach ($progressData['modules'] as $classId => $modules): ?>
                        <?php 
                            // Get class details for this group of modules
                            $classInfo = $progressData['classes'][$classId];
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $classId; ?>">
                                    <strong><?php echo htmlspecialchars($classInfo['subject_code'] . ': ' . $classInfo['subject_name']); ?></strong>
                                    <span class="badge bg-primary ms-2"><?php echo $classInfo['completion_percentage']; ?>% Complete</span>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $classId; ?>" class="accordion-collapse collapse" data-bs-parent="#moduleProgressAccordion">
                                <div class="accordion-body">
                                    <?php foreach ($modules as $module): ?>
                                        <div class="mb-3 pb-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($module['module_title']); ?></h6>
                                                <span class="badge bg-info"><?php echo $module['completed_activities']; ?>/<?php echo $module['total_activities']; ?> Activities</span>
                                            </div>
                                            <div class="progress mt-2" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $module['completion_percentage']; ?>%;" 
                                                     aria-valuenow="<?php echo $module['completion_percentage']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small><?php echo $module['completion_percentage']; ?>% Complete</small>
                                                <small><?php echo $module['total_activities'] - $module['completed_activities']; ?> Remaining</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.accordion-button:not(.collapsed) {
    background-color: rgba(0,0,0,.05);
    color: #212529;
}
</style>

<?php include '../includes/footer.php'; ?>
