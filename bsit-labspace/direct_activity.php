<?php
// Ultra-Simple Direct Activity Viewer
// This file provides the simplest possible interface to access activities

session_start();
require_once 'includes/functions/auth.php';
require_once 'includes/functions/activity_functions.php';

// Get activity ID from URL
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errorMessage = '';
$activity = null;

// If we have an activity ID, try to load it
if ($activityId) {
    try {
        $pdo = getDbConnection();
        
        // Basic query to get activity details
        $stmt = $pdo->prepare("
            SELECT a.*, m.title as module_title, c.section, s.code as subject_code
            FROM activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE a.id = ?
        ");
        
        $stmt->execute([$activityId]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store activity ID for recovery
        $_SESSION['last_activity_id'] = $activityId;
        
    } catch (Exception $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $activity ? htmlspecialchars($activity['title']) : 'Direct Activity Access'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
        }
        .content-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        .activity-header {
            background: #343a40;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .activity-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .navigation-tools {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <?php if ($activity): ?>
            <div class="activity-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h2><i class="fas fa-cube me-2"></i> <?php echo htmlspecialchars($activity['title']); ?></h2>
                    <span class="badge bg-primary fs-6">ID: <?php echo $activity['id']; ?></span>
                </div>
                <p class="mb-0">
                    <?php echo htmlspecialchars($activity['subject_code'] . ' / ' . $activity['module_title']); ?>
                </p>
            </div>
            
            <div class="activity-body">
                <div class="navigation-tools">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <?php if ($_SESSION['user_type'] == 'student'): ?>
                                <a href="student/view_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i> View in Student Portal
                                </a>
                                <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                                <a href="teacher/edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i> Edit in Teacher Portal
                                </a>
                                <?php endif; ?>
                                <a href="emergency_activity.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-search me-2"></i> Browse All Activities
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Quick Navigation</h5>
                            <form action="direct_activity.php" method="get" class="input-group mb-3">
                                <input type="number" name="id" class="form-control" placeholder="Enter Activity ID" value="<?php echo $activityId; ?>">
                                <button type="submit" class="btn btn-primary">Go</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="activity-content">
                    <?php if ($activity['activity_type'] == 'coding'): ?>
                        <div class="alert alert-info">
                            <h4><i class="fas fa-code me-2"></i> Coding Activity</h4>
                            <p>This is a coding activity. Please use the standard portal for interactive features.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($activity['instructions'])): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Instructions</h5>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($activity['instructions'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 text-end">
                    <a href="index.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                    <?php if ($_SESSION['user_type'] == 'student'): ?>
                        <a href="student/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                        <a href="teacher/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Activity Not Found</h3>
                </div>
                <div class="card-body">
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    
                    <p>Please enter an activity ID to view:</p>
                    
                    <form action="direct_activity.php" method="get">
                        <div class="input-group mb-3">
                            <input type="number" name="id" class="form-control form-control-lg" placeholder="Enter Activity ID">
                            <button type="submit" class="btn btn-primary btn-lg">View Activity</button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <h5>Quick Links</h5>
                        <div class="list-group">
                            <a href="emergency_activity.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-search me-2"></i> Browse All Activities
                            </a>
                            <a href="<?php echo $_SESSION['user_type']; ?>/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                            </a>
                            <a href="index.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-home me-2"></i> Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-focus the ID input if activity not found
    document.addEventListener('DOMContentLoaded', function() {
        const idInput = document.querySelector('input[name="id"]');
        if (idInput && !<?php echo $activity ? 'true' : 'false'; ?>) {
            idInput.focus();
        }
        
        // Store activity ID for emergency recovery
        <?php if ($activityId): ?>
        try {
            localStorage.setItem('last_activity_id', '<?php echo $activityId; ?>');
            sessionStorage.setItem('last_activity_id', '<?php echo $activityId; ?>');
            
            // Update activity history
            let activityHistory = JSON.parse(localStorage.getItem('activity_history') || '[]');
            if (!activityHistory.includes('<?php echo $activityId; ?>')) {
                activityHistory.unshift('<?php echo $activityId; ?>');
                if (activityHistory.length > 10) activityHistory.pop();
                localStorage.setItem('activity_history', JSON.stringify(activityHistory));
            }
        } catch (e) {
            console.error('Storage error:', e);
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>
