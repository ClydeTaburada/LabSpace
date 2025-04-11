<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/system_stats_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Get filter parameters
$logType = isset($_GET['type']) ? $_GET['type'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Get logs
$logs = getSystemLogs($limit, $logType);

/**
 * Return CSS class for log type row
 * @param string $type The log type
 * @return string CSS class name
 */
function getLogTypeClass($type) {
    switch ($type) {
        case 'error':
            return 'table-danger';
        case 'auth':
            return 'table-info';
        case 'activity':
            return 'table-success';
        case 'system':
            return 'table-warning';
        default:
            return '';
    }
}

/**
 * Return badge class for log type
 * @param string $type The log type
 * @return string Badge class name
 */
function getLogTypeBadgeClass($type) {
    switch ($type) {
        case 'error':
            return 'danger';
        case 'auth':
            return 'info';
        case 'activity':
            return 'success';
        case 'system':
            return 'warning';
        default:
            return 'secondary';
    }
}

$pageTitle = "System Logs";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>System Logs</h1>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="log-type" class="form-label">Log Type:</label>
                    <select id="log-type" name="type" class="form-select">
                        <option value="" <?php echo $logType === null ? 'selected' : ''; ?>>All Types</option>
                        <option value="auth" <?php echo $logType === 'auth' ? 'selected' : ''; ?>>Authentication</option>
                        <option value="error" <?php echo $logType === 'error' ? 'selected' : ''; ?>>Errors</option>
                        <option value="activity" <?php echo $logType === 'activity' ? 'selected' : ''; ?>>User Activity</option>
                        <option value="system" <?php echo $logType === 'system' ? 'selected' : ''; ?>>System</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="limit" class="form-label">Show Entries:</label>
                    <select id="limit" name="limit" class="form-select">
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                        <option value="250" <?php echo $limit === 250 ? 'selected' : ''; ?>>250</option>
                        <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="system_logs.php" class="btn btn-outline-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">System Logs</h5>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">No logs found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="logs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>IP Address</th>
                                <th>User ID</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr class="<?php echo getLogTypeClass($log['log_type']); ?>">
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getLogTypeBadgeClass($log['log_type']); ?>">
                                            <?php echo ucfirst($log['log_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['message']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <a href="edit_user.php?id=<?php echo $log['user_id']; ?>">
                                                <?php echo $log['user_id']; ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="button" id="export-csv" class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                    <button type="button" id="purge-logs" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Purge Old Logs
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export to CSV
    document.getElementById('export-csv').addEventListener('click', function() {
        const table = document.getElementById('logs-table');
        const rows = table.querySelectorAll('tbody tr');
        
        // Create CSV content
        let csvContent = 'data:text/csv;charset=utf-8,ID,Type,Message,IP Address,User ID,Date\n';
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const id = cells[0].textContent;
            const type = cells[1].textContent.trim();
            const message = cells[2].textContent.replace(/,/g, ' ').replace(/"/g, '""');
            const ip = cells[3].textContent;
            const userId = cells[4].textContent.trim();
            const date = cells[5].textContent;
            
            csvContent += `${id},"${type}","${message}","${ip}","${userId}","${date}"\n`;
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'system_logs.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Purge logs confirmation
    document.getElementById('purge-logs').addEventListener('click', function() {
        if (confirm('Are you sure you want to purge logs older than 30 days? This action cannot be undone.')) {
            // Here you would typically make an AJAX call or form submission to purge logs
            alert('Purge functionality not implemented in this demo.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
