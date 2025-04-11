<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/user_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';
$user = null;

// Verify user exists
if ($userId > 0) {
    $user = getUserById($userId);
    if (!$user) {
        header('Location: manage_users.php?error=User+not+found');
        exit;
    }
} else {
    header('Location: manage_users.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $resetPassword = isset($_POST['reset_password']) && $_POST['reset_password'] === '1';
    
    // Additional fields based on user type
    $userType = $user['user_type'];
    $additionalFields = [];
    
    if ($userType === 'teacher') {
        $additionalFields = [
            'department' => $_POST['department'] ?? '',
            'employee_id' => $_POST['employee_id'] ?? ''
        ];
    } else if ($userType === 'student') {
        $additionalFields = [
            'year_level' => $_POST['year_level'] ?? '',
            'section' => $_POST['section'] ?? '',
            'student_number' => $_POST['student_number'] ?? ''
        ];
    }
    
    // Simple validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $message = "Name and email are required fields.";
        $messageType = "danger";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "danger";
    } else {
        $result = updateUser($userId, $firstName, $lastName, $email, $additionalFields, $resetPassword);
        
        if ($result['success']) {
            $message = "User updated successfully. " . ($resetPassword ? "Password has been reset." : "");
            $messageType = "success";
            // Refresh user data
            $user = getUserById($userId);
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

// Get additional information based on user type
$additionalInfo = [];
if ($user['user_type'] === 'teacher') {
    $additionalInfo = getTeacherProfile($userId);
} else if ($user['user_type'] === 'student') {
    $additionalInfo = getStudentProfile($userId);
}

$pageTitle = "Edit User";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit User</h1>
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
        <div class="card-header">
            <span class="badge bg-<?php echo getUserTypeClass($user['user_type']); ?>">
                <?php echo ucfirst($user['user_type']); ?>
            </span>
            User ID: <?php echo $userId; ?>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <?php if ($user['user_type'] === 'teacher' && isset($additionalInfo)): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" 
                               value="<?php echo htmlspecialchars($additionalInfo['employee_id'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               value="<?php echo htmlspecialchars($additionalInfo['department'] ?? ''); ?>" required>
                    </div>
                </div>
                <?php elseif ($user['user_type'] === 'student' && isset($additionalInfo)): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="student_number" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="student_number" name="student_number" 
                               value="<?php echo htmlspecialchars($additionalInfo['student_number'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="year_level" class="form-label">Year Level</label>
                        <select class="form-select" id="year_level" name="year_level" required>
                            <?php for($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($additionalInfo['year_level'] ?? 0) == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section" 
                               value="<?php echo htmlspecialchars($additionalInfo['section'] ?? ''); ?>" required>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="reset_password" name="reset_password" value="1">
                    <label class="form-check-label" for="reset_password">
                        Reset password to default and require change on next login
                    </label>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Account Created</label>
                    <p class="form-control-plaintext">
                        <?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?>
                    </p>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
