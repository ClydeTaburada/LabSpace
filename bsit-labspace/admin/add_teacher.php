<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/user_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $employeeId = $_POST['employee_id'] ?? '';
    $department = $_POST['department'] ?? '';
    
    // Simple validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($employeeId) || empty($department)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "danger";
    } else {
        // Generate a default password - can be changed later
        $defaultPassword = generateDefaultPassword();
        
        // Create teacher account with force_password_change flag set
        $result = createTeacher($firstName, $lastName, $email, $defaultPassword, $employeeId, $department);
        
        if ($result['success']) {
            $message = "Teacher account created successfully.";
            $messageType = "success";
            // Clear form after successful submission
            $firstName = $lastName = $email = $employeeId = $department = '';
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

$pageTitle = "Add New Teacher";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Add New Teacher</h1>
        <a href="manage_users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <div class="form-text">This will be used for login.</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" 
                               value="<?php echo htmlspecialchars($employeeId ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               value="<?php echo htmlspecialchars($department ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    A default password will be generated for this account. The teacher will be required to change it upon first login.
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Create Teacher Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
