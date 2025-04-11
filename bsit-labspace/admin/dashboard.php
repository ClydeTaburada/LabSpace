<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/user_functions.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';
require_once '../includes/functions/system_stats_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Get system statistics
$stats = getSystemStatistics();

$pageTitle = "Admin Dashboard";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="section-title">Admin Dashboard</h1>
        <div>
            <a href="<?php echo getBaseUrl(); ?>logout.php" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="alert alert-info d-flex align-items-center">
        <i class="fas fa-user-circle fa-lg me-3"></i>
        <div>
            Welcome to BSIT LabSpace, <strong><?php echo $_SESSION['user_name']; ?></strong>! 
            You have full administrative access to the system.
        </div>
    </div>
    
    <!-- System Statistics Cards -->
    <div class="row mt-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 border-start border-primary border-4">
                <div class="card-body stats-card">
                    <i class="fas fa-users stats-icon text-primary"></i>
                    <div class="stats-details">
                        <div class="stats-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stats-text">Total Users</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-primary stretched-link" href="manage_users.php">View Details</a>
                    <div class="text-primary"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 border-start border-success border-4">
                <div class="card-body stats-card">
                    <i class="fas fa-chalkboard stats-icon text-success"></i>
                    <div class="stats-details">
                        <div class="stats-number"><?php echo $stats['total_classes']; ?></div>
                        <div class="stats-text">Active Classes</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-success stretched-link" href="manage_classes.php">View Details</a>
                    <div class="text-success"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 border-start border-warning border-4">
                <div class="card-body stats-card">
                    <i class="fas fa-book stats-icon text-warning"></i>
                    <div class="stats-details">
                        <div class="stats-number"><?php echo $stats['total_modules']; ?></div>
                        <div class="stats-text">Total Modules</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-warning stretched-link" href="view_all_modules.php">View Details</a>
                    <div class="text-warning"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 border-start border-danger border-4">
                <div class="card-body stats-card">
                    <i class="fas fa-tasks stats-icon text-danger"></i>
                    <div class="stats-details">
                        <div class="stats-number"><?php echo $stats['total_activities']; ?></div>
                        <div class="stats-text">Total Activities</div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-danger stretched-link" href="view_all_activities.php">View Details</a>
                    <div class="text-danger"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detailed Stats Row -->
    <div class="row mt-2">
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>User Statistics</h5>
                    <a href="manage_users.php" class="btn btn-sm btn-light">Manage Users</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="h1 mb-0 text-primary"><?php echo $stats['total_admins']; ?></div>
                            <div class="text-muted">Administrators</div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="h1 mb-0 text-primary"><?php echo $stats['total_teachers']; ?></div>
                            <div class="text-muted">Teachers</div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="h1 mb-0 text-primary"><?php echo $stats['total_students']; ?></div>
                            <div class="text-muted">Students</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <canvas id="userTypesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="add_teacher.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-user-plus"></i><br>
                                    Add New Teacher
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="manage_users.php?filter=teacher" class="btn btn-outline-info btn-lg">
                                    <i class="fas fa-key"></i><br>
                                    Reset Teacher Passwords
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="manage_subjects.php" class="btn btn-outline-success btn-lg">
                                    <i class="fas fa-book"></i><br>
                                    Manage Subjects
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="system_settings.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-cogs"></i><br>
                                    System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recently Added Users -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recently Added Users</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php 
                        $recentUsers = array_slice(getRecentUsers(), 0, 5);
                        
                        if (!empty($recentUsers)): 
                            foreach ($recentUsers as $user):
                        ?>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo getUserTypeClass($user['user_type']); ?>"><?php echo ucfirst($user['user_type']); ?></span>
                                        <small class="text-muted d-block text-end">Added: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No users found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <a href="manage_users.php" class="btn btn-sm btn-outline-primary">View All Users</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Current Version:</strong></td>
                                <td>1.0.0</td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server:</strong></td>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Database:</strong></td>
                                <td>
                                    <?php 
                                    $pdo = getDbConnection();
                                    echo $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ' ' . 
                                         $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <a href="system_logs.php" class="btn btn-sm btn-outline-primary">View System Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Types Chart
    const userTypesChart = document.getElementById('userTypesChart');
    
    if (userTypesChart) {
        new Chart(userTypesChart, {
            type: 'pie',
            data: {
                labels: ['Administrators', 'Teachers', 'Students'],
                datasets: [{
                    data: [<?php echo $stats['total_admins']; ?>, <?php echo $stats['total_teachers']; ?>, <?php echo $stats['total_students']; ?>],
                    backgroundColor: ['#dc3545', '#092CA0', '#198754'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>