<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';
require_once '../includes/functions/student_progress_functions.php';
require_once '../includes/functions/user_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Check if student ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_users.php?error=Invalid student ID');
    exit;
}   

$studentId = (int)$_GET['id'];

// Get student details
$student = getStudentById($studentId);

// Verify student exists
if (!$student) {
    header('Location: manage_users.php?error=Student not found');
    exit;
}

// Get student progress data across all classes
$progressData = getStudentAllClassesProgress($studentId);

$pageTitle = "Student Progress";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Student Progress</h1>
        <a href="manage_users.php?filter=student" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
    </div>
    
    <!-- Student Info Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Student Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                        <p class="text-muted mb-0">Student ID: <?php echo htmlspecialchars($student['student_number']); ?></p>
                        <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <p class="mb-0"><strong>Year Level:</strong> <?php echo $student['year_level']; ?></p>
                        <p class="mb-0"><strong>Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                        <p class="mb-0"><strong>Account Created:</strong> <?php echo date('M j, Y', strtotime($student['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <a href="edit_user.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit Student
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Progress Summary -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Overall Progress Summary</h5>
        </div>
        <div class="card-body">
            <?php 
            // Calculate overall statistics
            $totalActivities = 0;
            $completedActivities = 0;
            $totalGraded = 0;
            $totalScore = 0;
            
            foreach ($progressData['classes'] as $class) {
                $totalActivities += $class['total_activities'];
                $completedActivities += $class['completed_activities'];
                $totalGraded += $class['graded_count'] ?? 0;
                $totalScore += isset($class['graded_count']) && $class['graded_count'] > 0 
                             ? $class['average_grade'] * $class['graded_count'] 
                             : 0;
            }
            
            $overallCompletion = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100) : 0;
            $averageGrade = $totalGraded > 0 ? round($totalScore / $totalGraded, 1) : 0;
            ?>
            
            <div class="row">
                <div class="col-md-3 text-center mb-3">
                    <div class="h1 mb-0"><?php echo $overallCompletion; ?>%</div>
                    <div class="text-muted">Overall Completion</div>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $overallCompletion; ?>%;" 
                             aria-valuenow="<?php echo $overallCompletion; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
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
            
            <div class="mt-3">
                <h6>Classes Enrolled: <?php echo count($progressData['classes']); ?></h6>
                <canvas id="classProgressChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Per-Class Progress -->
    <div class="row">
        <?php 
        if (empty($progressData['classes'])): 
        ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This student is not enrolled in any classes yet.
                </div>
            </div>
        <?php 
        else: 
            foreach ($progressData['classes'] as $classId => $class): 
        ?>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($class['subject_code'] . ': ' . $class['subject_name']); ?></h5>
                        <div class="small text-muted">Teacher: <?php echo htmlspecialchars($class['teacher_name']); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($class['section']); ?></span>
                                <span class="text-muted ms-2">Year <?php echo $class['year_level']; ?></span>
                            </div>
                            <div class="text-end">
                                <strong><?php echo $class['completion_percentage']; ?>%</strong> completed
                                <small class="text-muted">(<?php echo $class['completed_activities']; ?>/<?php echo $class['total_activities']; ?>)</small>
                            </div>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $class['completion_percentage']; ?>%;" 
                                 aria-valuenow="<?php echo $class['completion_percentage']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        
                        <?php if (isset($class['average_grade'])): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <div>Average Grade:</div>
                                    <strong><?php echo round($class['average_grade'], 1); ?>%</strong>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($progressData['modules'][$classId]) && !empty($progressData['modules'][$classId])): ?>
                            <h6 class="mt-4">Module Progress:</h6>
                            <?php foreach ($progressData['modules'][$classId] as $module): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <div><?php echo htmlspecialchars($module['module_title']); ?></div>
                                        <div><?php echo $module['completion_percentage']; ?>%</div>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $module['completion_percentage']; ?>%;" 
                                             aria-valuenow="<?php echo $module['completion_percentage']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="../teacher/view_student_progress.php?student_id=<?php echo $student['id']; ?>&class_id=<?php echo $classId; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chart-line"></i> View Detailed Progress
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            endforeach;
        endif; 
        ?>
    </div>
    
    <!-- Recent Submissions -->
    <div class="card mt-2">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Recent Submissions</h5>
        </div>
        <div class="card-body">
            <?php 
            $submissions = getStudentRecentSubmissions($studentId);
            if (empty($submissions)): 
            ?>
                <p class="text-muted">No recent submissions found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($submission['activity_title']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['subject_code']); ?></td>
                                    <td>
                                        <?php if ($submission['graded']): ?>
                                            <span class="badge bg-success">Graded</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($submission['graded']) {
                                            echo $submission['grade'] . '%';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="../teacher/view_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-outline-primary">
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
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Class Progress Chart
    const classProgressChart = document.getElementById('classProgressChart');
    
    if (classProgressChart) {
        const classLabels = [];
        const completionData = [];
        const gradeData = [];
        
        <?php foreach ($progressData['classes'] as $class): ?>
            classLabels.push('<?php echo addslashes($class['subject_code']); ?>');
            completionData.push(<?php echo $class['completion_percentage']; ?>);
            gradeData.push(<?php echo isset($class['average_grade']) ? $class['average_grade'] : 0; ?>);
        <?php endforeach; ?>
        
        new Chart(classProgressChart, {
            type: 'bar',
            data: {
                labels: classLabels,
                datasets: [
                    {
                        label: 'Completion %',
                        data: completionData,
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Average Grade',
                        data: gradeData,
                        backgroundColor: 'rgba(23, 162, 184, 0.7)',
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage'
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
