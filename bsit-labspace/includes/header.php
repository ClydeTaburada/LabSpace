<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle ?? 'BSIT LabSpace'; ?></title>
    
    <!-- Emergency navigation -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/emergency-navigation.js"></script>
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/theme.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/navigation-helpers.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>assets/css/activity-clickable.css">
    
    <!-- Custom JavaScript -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/functions.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/activity-navigator.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/activity-helpers.js"></script>
    <!-- Add navigation core JS -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/navigation-core.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/activity-direct-access.js"></script>
    
    <!-- Add emergency activity access -->
    <?php include_once dirname(__FILE__) . '/activity-emergency-access.php'; ?>
    
    <!-- Activity navigation scripts -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/activity-reliable-click.js"></script>
    <script src="<?php echo getBaseUrl(); ?>assets/js/direct-activity-loader.js"></script>
    
    <!-- Critical navigation fix immediately available -->
    <script>
    function emergencyGo(activityId) {
        try {
            // Use our improved navigation function
            if (window.navigateToActivity) {
                navigateToActivity(activityId);
                return;
            }
            
            // Legacy fallback if function not available
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
            
            // Try multiple navigation approaches for reliability
            window.location.href = 'view_activity.php?id=' + activityId;
            
            setTimeout(() => {
                window.location.replace('view_activity.php?id=' + activityId);
            }, 300);
        } catch(e) {
            console.error('Navigation error:', e);
            // Fallback to emergency mode
            window.location.href = '../emergency_activity.php?id=' + activityId;
        }
    }
    
    // Force hide any stuck loading overlay
    function forceHideLoading() {
        console.log('[Header Emergency] Force hiding loading overlay');
        document.getElementById('loading-overlay')?.classList.remove('show');
    }
    
    // One-click emergency access button
    document.addEventListener('DOMContentLoaded', function() {
        // Add keyboard shortcut for emergency navigation
        document.addEventListener('keydown', function(e) {
            // Alt+E for emergency mode
            if (e.altKey && e.key.toLowerCase() === 'e') {
                window.location.href = '../emergency_activity.php';
            }
        });
        setTimeout(forceHideLoading, 5000);
    });
    </script>
    <script src="../assets/js/activity-navigation.js"></script>
</head>
<body class="<?php echo isLoginPage() ? 'auth-page' : ''; ?>">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="loading-text mt-3">Loading...</p>
            <button id="cancel-loading" class="btn btn-danger mt-3">Cancel Loading</button>
            
            <!-- Enhanced direct activity navigation within loading overlay -->
            <div class="mt-4 emergency-nav">
                <button class="btn btn-sm btn-warning" id="emergency-activity-btn">
                    <i class="fas fa-bolt"></i> Emergency Activity Navigation
                </button>
            </div>
        </div>
    </div>

    <script>
    // Make sure cancel button works
    document.getElementById('cancel-loading')?.addEventListener('click', function() {
        console.log('Cancel loading clicked');
        document.getElementById('loading-overlay').classList.remove('show');
    });
    
    // Ensure Enter key works on quick activity input
    document.getElementById('quick-activity-id')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            emergencyGo(this.value);
        }
    });
    
    // Safety timeout to auto-hide loading overlay
    setTimeout(function() {
        if (document.getElementById('loading-overlay')?.classList.contains('show')) {
            console.log('[Safety] Automatically hiding loading overlay after timeout');
            document.getElementById('loading-overlay').classList.remove('show');
        }
    }, 8000);
    </script>

    <!-- Main Navigation Bar -->
    <?php if (!isLoginPage()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>">
                <i class="fas fa-laptop-code"></i> BSIT LabSpace
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_type'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl() . $_SESSION['user_type']; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <?php if ($_SESSION['user_type'] == 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>student/my_classes.php">
                                    <i class="fas fa-chalkboard"></i> My Classes
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>teacher/my_classes.php">
                                    <i class="fas fa-chalkboard-teacher"></i> My Classes
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/manage_users.php">
                                    <i class="fas fa-users-cog"></i> Manage Users
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo getBaseUrl() . $_SESSION['user_type']; ?>/profile.php">
                                        <i class="fas fa-id-card"></i> My Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo getBaseUrl(); ?>logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Secondary Navigation for main sections - only shown on specific pages -->
    <?php 
    $showSecondaryNav = !isLoginPage() && isset($_SESSION['user_type']) && !isStudentDashboard();
    $currentFile = basename($_SERVER['PHP_SELF']);
    
    if ($showSecondaryNav): 
    ?>
    <div class="container mt-3">
        <div class="main-navigation">
            <?php if ($_SESSION['user_type'] == 'student'): ?>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>student/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'my_classes.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>student/my_classes.php">
                            <i class="fas fa-chalkboard"></i> My Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'activities.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>student/activities.php">
                            <i class="fas fa-tasks"></i> Activities
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getBaseUrl(); ?>direct_activity_viewer.php">
                            <i class="fas fa-bolt"></i> Quick Access
                        </a>
                    </li>
                </ul>
            <?php elseif ($_SESSION['user_type'] == 'teacher'): ?>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>teacher/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'my_classes.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>teacher/my_classes.php">
                            <i class="fas fa-chalkboard-teacher"></i> Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'modules.php' || $currentFile == 'module_activities.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>teacher/modules.php">
                            <i class="fas fa-book"></i> Modules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'activities.php' || $currentFile == 'edit_activity.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>teacher/activities.php">
                            <i class="fas fa-tasks"></i> Activities
                        </a>
                    </li>
                </ul>
            <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'manage_users.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/manage_users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'manage_classes.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/manage_classes.php">
                            <i class="fas fa-chalkboard"></i> Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'manage_subjects.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/manage_subjects.php">
                            <i class="fas fa-book"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'view_all_modules.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/view_all_modules.php">
                            <i class="fas fa-cubes"></i> Modules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentFile == 'system_settings.php' ? 'active' : ''; ?>" href="<?php echo getBaseUrl(); ?>admin/system_settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content">
<?php
// Helper function to determine if current page is a login/register page
function isLoginPage() {
    $loginPages = ['/index.php', '/login.php', '/register.php', '/process_registration.php'];
    foreach ($loginPages as $page) {
        if (strpos($_SERVER['PHP_SELF'], $page) !== false) {
            return true;
        }
    }
    return false;
}

// Helper function to determine if current page is the student dashboard
function isStudentDashboard() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student') {
        if (strpos($_SERVER['PHP_SELF'], '/student/dashboard.php') !== false) {
            return true;
        }
    }
    return false;
}
?>