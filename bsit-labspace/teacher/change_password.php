<?php
session_start();
require_once '../includes/functions/auth.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// If form is submitted
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required";
    } elseif ($newPassword != $confirmPassword) {
        $error = "New password and confirmation do not match";
    } elseif (strlen($newPassword) < 8) {
        $error = "New password must be at least 8 characters long";
    } else {
        // Verify current password
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = "Current password is incorrect";
        } else {
            // Update password
            if (updateUserPassword($_SESSION['user_id'], $newPassword)) {
                // Update session to remove password change flag
                $_SESSION['force_password_change'] = false;
                $success = "Password updated successfully";
                
                // Redirect to dashboard after successful password change
                header("Refresh: 2; url=dashboard.php");
            } else {
                $error = "Failed to update password";
            }
        }
    }
}

$pageTitle = "Change Password";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Change Password</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['force_password_change']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> You must change your default password before continuing.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                        
                        <?php if (!$_SESSION['force_password_change']): ?>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
