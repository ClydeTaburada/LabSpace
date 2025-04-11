<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/user_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Process form submissions
$message = '';
$messageType = '';

// Handle password reset
if (isset($_GET['reset']) && is_numeric($_GET['reset'])) {
    $userId = $_GET['reset'];
    // Create additional fields array with empty values
    $additionalFields = [];
    
    // Reset password with updateUser function
    $result = updateUser($userId, '', '', '', $additionalFields, true);
    
    if ($result['success']) {
        $message = "Password reset successfully. New temporary password: " . $result['new_password'];
        $messageType = "success";
    } else {
        $message = "Failed to reset password: " . $result['message'];
        $messageType = "danger";
    }
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    // Don't allow admin to delete themselves
    if ($userId != $_SESSION['user_id']) {
        if (deleteUser($userId)) {
            $message = "User deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete user.";
            $messageType = "danger";
        }
    } else {
        $message = "You cannot delete your own account.";
        $messageType = "warning";
    }
}

// Handle batch actions if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_action']) && isset($_POST['selected_users'])) {
    $action = $_POST['batch_action'];
    $selectedUsers = $_POST['selected_users'];
    
    if (!empty($selectedUsers)) {
        $successCount = 0;
        $failCount = 0;
        
        foreach ($selectedUsers as $userId) {
            // Skip self for delete action
            if ($action === 'delete' && $userId == $_SESSION['user_id']) {
                $failCount++;
                continue;
            }
            
            switch ($action) {
                case 'delete':
                    if (deleteUser($userId)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;
                case 'reset_password':
                    $result = updateUser($userId, '', '', '', [], true);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                    break;
            }
        }
        
        $actionText = $action === 'delete' ? 'deleted' : 'password reset for';
        $message = "$successCount users $actionText successfully.";
        if ($failCount > 0) {
            $message .= " $failCount operations failed.";
        }
        
        $messageType = $failCount > 0 ? "warning" : "success";
    } else {
        $message = "No users selected for the action.";
        $messageType = "warning";
    }
}

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';

// Get all users for display
$users = getAllUsers();

// Apply filter if needed
if ($filter !== 'all') {
    $users = array_filter($users, function($user) use ($filter) {
        return $user['user_type'] === $filter;
    });
}

$pageTitle = "Manage Users";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Users</h1>
        <div>
            <a href="add_teacher.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Teacher
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="filter-type" class="form-label">Filter by Role:</label>
                    <select id="filter-type" class="form-select" onchange="window.location='manage_users.php?filter='+this.value">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                        <option value="teacher" <?php echo $filter === 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                        <option value="student" <?php echo $filter === 'student' ? 'selected' : ''; ?>>Students</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search-users" class="form-label">Search:</label>
                    <input type="text" id="search-users" class="form-control" placeholder="Search by name, email, or ID...">
                </div>
                <div class="col-md-3">
                    <label for="batch-action" class="form-label">Batch Actions:</label>
                    <div class="d-flex">
                        <select id="batch-action" name="batch_action" class="form-select" form="batch-form">
                            <option value="">-- Select Action --</option>
                            <option value="delete">Delete Selected</option>
                            <option value="reset_password">Reset Passwords</option>
                        </select>
                        <button type="submit" class="btn btn-primary ms-2" form="batch-form" id="apply-batch">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <form id="batch-form" method="post">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="users-table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr data-user-type="<?php echo $user['user_type']; ?>" class="user-row">
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input user-select" type="checkbox" 
                                                       name="selected_users[]" value="<?php echo $user['id']; ?>"
                                                       <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            </div>
                                        </td>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php 
                                                switch ($user['user_type']) {
                                                    case 'admin':
                                                        echo '<span class="badge bg-danger">Administrator</span>';
                                                        break;
                                                    case 'teacher':
                                                        echo '<span class="badge bg-primary">Teacher</span>';
                                                        if ($user['force_password_change']) {
                                                            echo ' <span class="badge bg-warning text-dark">Password change required</span>';
                                                        }
                                                        break;
                                                    case 'student':
                                                        echo '<span class="badge bg-success">Student</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="#" class="btn btn-outline-warning reset-password" 
                                                       data-id="<?php echo $user['id']; ?>"
                                                       data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                       title="Reset Password">
                                                        <i class="fas fa-key"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-outline-danger delete-user" 
                                                       data-id="<?php echo $user['id']; ?>"
                                                       data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                       title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($user['user_type'] == 'teacher'): ?>
                                                    <a href="teacher_classes.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-info" title="Classes">
                                                        <i class="fas fa-chalkboard"></i>
                                                    </a>
                                                <?php elseif ($user['user_type'] == 'student'): ?>
                                                    <a href="student_progress.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-success" title="Progress">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Total: <span id="user-count"><?php echo count($users); ?></span> users
                        (<span id="selected-count">0</span> selected)
                    </div>
                    <div>
                        <button type="button" id="export-csv" class="btn btn-sm btn-success">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user: <span id="user-to-delete">User</span>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Confirmation Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Password Reset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the password for: <span id="user-to-reset">User</span>?</p>
                <p>A new temporary password will be generated, and the user will be required to change it on their next login.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-reset" class="btn btn-warning">Reset Password</a>
            </div>
        </div>
    </div>
</div>

<!-- Batch Action Confirmation Modal -->
<div class="modal fade" id="batchActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Batch Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="batch-action-name">perform this action</span> on <span id="batch-user-count">0</span> selected users?</p>
                <p id="batch-action-warning" class="text-danger"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-batch" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User filtering with search
    const searchInput = document.getElementById('search-users');
    const table = document.getElementById('users-table');
    const rows = table.querySelectorAll('.user-row');
    const userCountSpan = document.getElementById('user-count');
    
    searchInput.addEventListener('input', filterUsers);
    
    function filterUsers() {
        const searchValue = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const id = row.cells[1].textContent.toLowerCase();
            const name = row.cells[2].textContent.toLowerCase();
            const email = row.cells[3].textContent.toLowerCase();
            
            // Check if matches search input
            const matchesSearch = id.includes(searchValue) || 
                                name.includes(searchValue) || 
                                email.includes(searchValue);
            
            // Show/hide row based on filters
            row.style.display = matchesSearch ? '' : 'none';
            
            if (matchesSearch) {
                visibleCount++;
            }
        });
        
        userCountSpan.textContent = visibleCount;
    }
    
    // Set up delete confirmation modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const userToDeleteSpan = document.getElementById('user-to-delete');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.id;
            const userName = this.dataset.name;
            
            userToDeleteSpan.textContent = userName;
            confirmDeleteBtn.href = `?delete=${userId}`;
            deleteModal.show();
        });
    });
    
    // Set up password reset confirmation modal
    const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    const userToResetSpan = document.getElementById('user-to-reset');
    const confirmResetBtn = document.getElementById('confirm-reset');
    
    document.querySelectorAll('.reset-password').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.id;
            const userName = this.dataset.name;
            
            userToResetSpan.textContent = userName;
            confirmResetBtn.href = `?reset=${userId}`;
            resetModal.show();
        });
    });
    
    // Handle "Select All" checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-select:not([disabled])');
    const selectedCountSpan = document.getElementById('selected-count');
    
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        
        updateSelectedCount();
    });
    
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            
            // Update "Select All" checkbox state
            const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        });
    });
    
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.user-select:checked').length;
        selectedCountSpan.textContent = selectedCount;
    }
    
    // Handle batch actions
    const batchForm = document.getElementById('batch-form');
    const batchActionSelect = document.getElementById('batch-action');
    const applyBatchBtn = document.getElementById('apply-batch');
    
    const batchActionModal = new bootstrap.Modal(document.getElementById('batchActionModal'));
    const batchActionNameSpan = document.getElementById('batch-action-name');
    const batchUserCountSpan = document.getElementById('batch-user-count');
    const batchActionWarningSpan = document.getElementById('batch-action-warning');
    const confirmBatchBtn = document.getElementById('confirm-batch');
    
    batchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedUsers = document.querySelectorAll('.user-select:checked');
        
        if (selectedUsers.length === 0) {
            alert('Please select at least one user for this action.');
            return;
        }
        
        const action = batchActionSelect.value;
        
        if (!action) {
            alert('Please select an action to perform.');
            return;
        }
        
        // Set modal content based on selected action
        let actionName = '';
        let actionWarning = '';
        
        switch (action) {
            case 'delete':
                actionName = 'delete';
                actionWarning = 'This action cannot be undone.';
                break;
            case 'reset_password':
                actionName = 'reset passwords for';
                actionWarning = 'Users will be required to change their password on next login.';
                break;
        }
        
        batchActionNameSpan.textContent = actionName;
        batchUserCountSpan.textContent = selectedUsers.length;
        batchActionWarningSpan.textContent = actionWarning;
        
        batchActionModal.show();
    });
    
    confirmBatchBtn.addEventListener('click', function() {
        batchActionModal.hide();
        batchForm.submit();
    });
    
    // CSV Export
    document.getElementById('export-csv').addEventListener('click', function() {
        // Get visible rows only (respects search filter)
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        
        // Create CSV content
        let csvContent = 'data:text/csv;charset=utf-8,ID,Name,Email,Role,Created\n';
        
        visibleRows.forEach(row => {
            const id = row.cells[1].textContent;
            const name = row.cells[2].textContent.replace(/,/g, ' ');
            const email = row.cells[3].textContent;
            const role = row.cells[4].textContent.replace(/,/g, ' ').trim();
            const created = row.cells[5].textContent;
            
            csvContent += `${id},"${name}","${email}","${role}","${created}"\n`;
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'users_export.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
