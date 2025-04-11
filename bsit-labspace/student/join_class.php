<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';

// Check if user is logged in and is a student
requireRole('student');

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_code'])) {
    $classCode = trim($_POST['class_code']);
    
    if (empty($classCode)) {
        $message = "Please enter a class code.";
        $messageType = "warning";
    } else {
        $class = getClassByCode($classCode);
        
        if (!$class) {
            $message = "Invalid class code. Please check and try again.";
            $messageType = "danger";
        } else {
            $result = enrollStudent($class['id'], $_SESSION['user_id']);
            
            if ($result['success']) {
                $message = "Successfully enrolled in " . $class['subject_code'] . " - " . $class['subject_name'] . " taught by " . $class['teacher_name'] . ".";
                $messageType = "success";
            } else {
                $message = $result['message'];
                $messageType = "danger";
            }
        }
    }
}

$pageTitle = "Join Class";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Join a Class</h1>
        <div>
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
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Enter Class Code</h5>
                </div>
                <div class="card-body">
                    <p>To join a class, enter the class code provided by your teacher.</p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="class_code" class="form-label">Class Code</label>
                            <input type="text" class="form-control form-control-lg" 
                                  id="class_code" name="class_code" 
                                  placeholder="Enter class code" required>
                            <div class="form-text">
                                For example: CS101-2A-23XYZ
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Join Class</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
