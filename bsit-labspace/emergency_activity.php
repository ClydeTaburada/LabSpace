<?php
// Emergency Activity Access
// This file provides a direct, ultra-simple way to access activities when normal navigation fails

session_start();
require_once 'includes/functions/auth.php';
require_once 'includes/functions/class_functions.php';
require_once 'includes/functions/module_functions.php';
require_once 'includes/functions/activity_functions.php';

// Basic validation
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : null;
$errorMessage = '';

// Get all activities for selection
$pdo = getDbConnection();
$allActivities = [];
$moduleActivities = [];

try {
    // Get activities the user has access to
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        if ($_SESSION['user_type'] == 'student') {
            // For students, get enrolled class activities
            $stmt = $pdo->prepare("
                SELECT a.id, a.title, a.activity_type, a.is_published, a.created_at,
                       m.title as module_title, c.section, s.code as subject_code
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN class_enrollments e ON c.id = e.class_id
                WHERE e.student_id = ? AND a.is_published = 1
                ORDER BY a.id DESC
            ");
            
            $stmt->execute([$userId]);
            $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif ($_SESSION['user_type'] == 'teacher') {
            // For teachers, get activities they created
            $stmt = $pdo->prepare("
                SELECT a.id, a.title, a.activity_type, a.is_published, a.created_at,
                       m.title as module_title, c.section, s.code as subject_code
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                WHERE c.teacher_id = ?
                ORDER BY a.id DESC
            ");
            
            $stmt->execute([$userId]);
            $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        elseif ($_SESSION['user_type'] == 'admin') {
            // For admins, get all activities
            $stmt = $pdo->query("
                SELECT a.id, a.title, a.activity_type, a.is_published, a.created_at,
                       m.title as module_title, c.section, s.code as subject_code
                FROM activities a
                JOIN modules m ON a.module_id = m.id
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                ORDER BY a.id DESC
                LIMIT 100
            ");
            
            $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // If a module ID is provided, get activities for that module
    if ($moduleId) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.activity_type, a.is_published, a.created_at
            FROM activities a
            WHERE a.module_id = ?
            ORDER BY a.id DESC
        ");
        
        $stmt->execute([$moduleId]);
        $moduleActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// If activity ID exists and user is logged in, redirect to the activity page with emergency mode
if ($activityId && isset($_SESSION['user_type'])) {
    $redirectUrl = '';
    
    if ($_SESSION['user_type'] == 'student') {
        $redirectUrl = "student/view_activity.php?id={$activityId}&emergency=1";
    } elseif ($_SESSION['user_type'] == 'teacher') {
        $redirectUrl = "teacher/edit_activity.php?id={$activityId}&emergency=1";
    } elseif ($_SESSION['user_type'] == 'admin') {
        $redirectUrl = "admin/view_activity.php?id={$activityId}&emergency=1";
    }
    
    if ($redirectUrl) {
        header("Location: $redirectUrl");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Activity Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: 'Arial', sans-serif;
        }
        .emergency-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .emergency-header {
            background: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .emergency-body {
            padding: 30px;
        }
        .activity-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .activity-card {
            margin-bottom: 15px;
            transition: transform 0.2s;
            border-left: 4px solid #dc3545;
        }
        .activity-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .navigation-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="emergency-container">
        <div class="emergency-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Emergency Activity Access</h2>
            <p>Use this page to directly access activities when normal navigation is not working</p>
        </div>
        
        <div class="emergency-body">
            <?php if ($errorMessage): ?>
                <div class="alert alert-warning"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <div class="navigation-panel">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Direct Access by ID</h5>
                        <form action="emergency_activity.php" method="get">
                            <div class="input-group mb-3">
                                <input type="number" name="id" class="form-control form-control-lg" placeholder="Enter Activity ID" value="<?php echo $activityId; ?>">
                                <button type="submit" class="btn btn-primary btn-lg">Go to Activity</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h5>Alternative Methods</h5>
                        <div class="d-grid gap-2">
                            <a href="direct_activity.php" class="btn btn-warning">
                                <i class="fas fa-bolt"></i> Ultra-Simple Activity Viewer
                            </a>
                            <a href="index.php?direct=1" class="btn btn-secondary">>
                                <i class="fas fa-door-open"></i> Alternative Navigation
                            </a>tton>
                        </div> href="index.php" class="btn btn-secondary">
                    </div>      <i class="fas fa-home"></i> Return to Homepage
                </div>      </a>
            </div>      </div>
                    </div>
            <?php if ($moduleId && $moduleActivities): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">lt) -->
                        <h5 class="mb-0">Module Activities</h5>tyle="display:none;">
                    </div>lass="card">
                    <div class="card-body">ader bg-info text-white">
                        <div class="row">-0">Recent Activities</h5>
                            <?php foreach ($moduleActivities as $activity): ?>
                                <div class="col-md-6">
                                    <div class="card activity-card">ow">
                                        <div class="card-body">py-3">
                                            <h6><?php echo htmlspecialchars($activity['title']); ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php echo ($activity['activity_type'] == 'coding') ? 'success' : 
                                                                                (($activity['activity_type'] == 'quiz') ? 'info' : 
                                                                                (($activity['activity_type'] == 'lab') ? 'warning' : 'primary')); ?>">
                                                    <?php echo ucfirst($activity['activity_type']); ?>
                                                </span>
                                                <span class="badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                                                </span>
                                            </div>es): ?>
                                            <div class="mt-3">
                                                <a href="?id=<?php echo $activity['id']; ?>" class="btn btn-danger w-100">
                                                    Access Activity #<?php echo $activity['id']; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>($moduleActivities as $activity): ?>
                                </div>lass="col-md-6">
                            <?php endforeach; ?>card activity-card">
                        </div>          <div class="card-body">
                    </div>                  <h6><?php echo htmlspecialchars($activity['title']); ?></h6>
                </div>                      <div class="d-flex justify-content-between align-items-center">
            <?php endif; ?>                     <span class="badge bg-<?php echo ($activity['activity_type'] == 'coding') ? 'success' : 
                                                                                (($activity['activity_type'] == 'quiz') ? 'info' : 
            <?php if ($allActivities): ?>                                       (($activity['activity_type'] == 'lab') ? 'warning' : 'primary')); ?>">
                <div class="card">                  <?php echo ucfirst($activity['activity_type']); ?>
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Your Activities</h5>badge bg-<?php echo $activity['is_published'] ? 'success' : 'secondary'; ?>">
                    </div>                          <?php echo $activity['is_published'] ? 'Published' : 'Draft'; ?>
                    <div class="card-body">     </span>
                        <div class="activity-list">
                            <div class="row">div class="mt-3">
                                <?php foreach ($allActivities as $activity): ?>ty['id']; ?>" class="btn btn-danger w-100">
                                    <div class="col-md-6"> Activity #<?php echo $activity['id']; ?>
                                        <div class="card activity-card">
                                            <div class="card-body">
                                                <h6><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                <p class="small text-muted">
                                                    <?php echo htmlspecialchars($activity['subject_code']); ?> | 
                                                    <?php echo htmlspecialchars($activity['module_title']); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge bg-<?php echo ($activity['activity_type'] == 'coding') ? 'success' : 
                                                                                    (($activity['activity_type'] == 'quiz') ? 'info' : 
                                                                                    (($activity['activity_type'] == 'lab') ? 'warning' : 'primary')); ?>">
                                                        <?php echo ucfirst($activity['activity_type']); ?>
                                                    </span>
                                                    <small class="text-muted">ID: <?php echo $activity['id']; ?></small>
                                                </div>es</h5>
                                                <a href="?id=<?php echo $activity['id']; ?>" 
                                                   class="btn btn-danger btn-sm w-100">
                                                    Open Activity #<?php echo $activity['id']; ?>
                                                </a>
                                                allActivities as $activity): ?>
                                                <!-- Add direct activity links with alternative methods -->
                                                <div class="mt-2 d-flex gap-2">
                                                    <a href="direct_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-warning btn-sm flex-grow-1">
                                                        Simple Viewspecialchars($activity['title']); ?></h6>
                                                    </a>="small text-muted">
                                                    <?php if ($_SESSION['user_type'] == 'student'): ?>e']); ?> | 
                                                    <a href="student/view_activity.php?id=<?php echo $activity['id']; ?>&emergency=1" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                                        Standard View
                                                    </a>ss="d-flex justify-content-between align-items-center mb-2">
                                                    <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>type'] == 'coding') ? 'success' : 
                                                    <a href="teacher/edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                                        Edit View                   (($activity['activity_type'] == 'lab') ? 'warning' : 'primary')); ?>">
                                                    </a><?php echo ucfirst($activity['activity_type']); ?>
                                                    <?php endif; ?>
                                                </div>mall class="text-muted">ID: <?php echo $activity['id']; ?></small>
                                            </div>div>
                                        </div>  <a href="?id=<?php echo $activity['id']; ?>" 
                                    </div>         class="btn btn-danger btn-sm w-100">
                                <?php endforeach; ?>Open Activity #<?php echo $activity['id']; ?>
                            </div>              </a>
                        </div>                  
                    </div>                      <!-- Add direct activity links with alternative methods -->
                </div>                          <div class="mt-2 d-flex gap-2">
            <?php else: ?>                          <a href="direct_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-warning btn-sm flex-grow-1">
                <div class="alert alert-info">          Simple View
                    <h5><i class="fas fa-info-circle"></i> No activities found</h5>
                    <p>Please enter an activity ID manually to access an activity.</p>= 'student'): ?>
                </div>                              <a href="student/view_activity.php?id=<?php echo $activity['id']; ?>&emergency=1" class="btn btn-outline-secondary btn-sm flex-grow-1">
            <?php endif; ?>                             Standard View
                                                    </a>
            <div class="mt-4 d-flex justify-content-between">eif ($_SESSION['user_type'] == 'teacher'): ?>
                <?php if ($_SESSION['user_type'] == 'student'): ?>er/edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                    <a href="student/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                    <a href="teacher/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
                    <a href="admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php else: ?>          </div>
                    <a href="index.php" class="btn btn-secondary">Back to Home</a>
                <?php endif; ?> <?php endforeach; ?>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>      </div>
        </div>      </div>
    </div>      </div>
            <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>        <h5><i class="fas fa-info-circle"></i> No activities found</h5>
    // Auto focus the ID input if emptyactivity ID manually to access an activity.</p>
    document.addEventListener('DOMContentLoaded', function() {
        const idInput = document.querySelector('input[name="id"]');
        if (idInput && !idInput.value) {
            idInput.focus(); d-flex justify-content-between">
        }       <?php if ($_SESSION['user_type'] == 'student'): ?>
                    <a href="student/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        // Store any accessed activity ID in local/session storage for recovery
        const activityCards = document.querySelectorAll('.activity-card');ry">Back to Dashboard</a>
        activityCards.forEach(card => {['user_type'] == 'admin'): ?>
            card.addEventListener('click', function(e) {"btn btn-secondary">Back to Dashboard</a>
                const link = this.querySelector('a[href*="id="]');
                if (link) {="index.php" class="btn btn-secondary">Back to Home</a>
                    const url = new URL(link.href, window.location.origin);
                    const activityId = url.searchParams.get('id');">Logout</a>
                    if (activityId) {
                        try {
                            sessionStorage.setItem('last_activity_id', activityId);
                            localStorage.setItem('last_activity_id', activityId);
                            .net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                            // Update activity history
                            let activityHistory = JSON.parse(localStorage.getItem('activity_history') || '[]');input if empty
                            if (!activityHistory.includes(activityId)) {Listener('DOMContentLoaded', function() {
                                activityHistory.unshift(activityId);nput = document.querySelector('input[name="id"]');
                                if (activityHistory.length > 10) activityHistory.pop();nput && !idInput.value) {
                                localStorage.setItem('activity_history', JSON.stringify(activityHistory)); idInput.focus();
                            } }
                        } catch (e) {
                            console.error('Storage error:', e); // Store any accessed activity ID in local/session storage for recovery
                        } const activityCards = document.querySelectorAll('.activity-card');
                    }        activityCards.forEach(card => {
























































</html></body>    </script>    });        }            }                historyContainer.innerHTML = '<div class="col-12 p-3 text-center text-danger">Error loading history</div>';                console.error('Error loading activity history:', e);            } catch (e) {                });                    historyContainer.appendChild(card);                    `;                        </div>                            </div>                                </a>                                    Open Activity                                <a href="?id=${activityId}" class="btn btn-danger btn-sm w-100 mb-1">                                <h6 class="mb-1">Activity #${activityId}</h6>                            <div class="card-body p-3">                        <div class="card activity-card h-100">                    card.innerHTML = `                    card.className = 'col-md-4 mb-2';                    const card = document.createElement('div');                activityHistory.forEach(activityId => {                // Create activity cards for each history item                                historyContainer.innerHTML = '';                // Clear loading spinner                                }                    return;                    historyContainer.innerHTML = '<div class="col-12 p-3 text-center">No recent activities found</div>';                if (activityHistory.length === 0) {                                const activityHistory = JSON.parse(localStorage.getItem('activity_history') || '[]');            try {                        const historyContainer = document.getElementById('activity-history-list');        function loadActivityHistory() {        // Load activity history from localStorage                });            }                panel.style.display = 'none';            } else {                loadActivityHistory();                panel.style.display = 'block';            if (panel.style.display === 'none') {            const panel = document.getElementById('activity-history-panel');        document.getElementById('show-activity-history')?.addEventListener('click', function() {        // Activity history panel functionality                });            });                }            card.addEventListener('click', function(e) {
                const link = this.querySelector('a[href*="id="]');
                if (link) {
                    const url = new URL(link.href, window.location.origin);
                    const activityId = url.searchParams.get('id');
                    if (activityId) {
                        try {
                            sessionStorage.setItem('last_activity_id', activityId);
                            localStorage.setItem('last_activity_id', activityId);
                        } catch (e) {
                            console.error('Storage error:', e);
                        }
                    }
                }
            });
        });

        // Show recent activity history panel
        const showHistoryButton = document.getElementById('show-activity-history');
        const historyPanel = document.getElementById('activity-history-panel');
        const historyList = document.getElementById('activity-history-list');

        showHistoryButton.addEventListener('click', function() {
            historyPanel.style.display = historyPanel.style.display === 'none' ? 'block' : 'none';

            if (historyPanel.style.display === 'block') {
                historyList.innerHTML = ''; // Clear previous content
                const recentIds = JSON.parse(localStorage.getItem('recent_activity_ids') || '[]');
                if (recentIds.length > 0) {
                    recentIds.forEach(id => {
                        const activityLink = document.createElement('a');
                        activityLink.href = `?id=${id}`;
                        activityLink.className = 'btn btn-outline-primary btn-sm mb-2';
                        activityLink.textContent = `Activity #${id}`;
                        historyList.appendChild(activityLink);
                    });
                } else {
                    historyList.innerHTML = '<p class="text-center text-muted">No recent activities found.</p>';
                }
            }
        });
    });
    </script>
</body>
</html>
