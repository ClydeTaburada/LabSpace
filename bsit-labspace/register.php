<?php
// Student registration page
session_start();
require_once 'includes/db/config.php';
require_once 'includes/functions/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . $_SESSION['user_type'] . '/dashboard.php');
    exit;
}

$pageTitle = "Student Registration";
include 'includes/header.php';

// Get available sections for dropdown
$pdo = getDbConnection();
$sections = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT section FROM student_profiles ORDER BY section");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sections[] = $row['section'];
    }
} catch (PDOException $e) {
    // Continue even if this fails
}

// Default year levels
$yearLevels = [1, 2, 3, 4];
?>

<div class="auth-wrapper">
    <div class="auth-container" style="max-width: 700px">
        <div class="auth-logo-wrapper">
            <div class="auth-logo">
                <i class="fas fa-laptop-code"></i> BSIT LabSpace
            </div>
            <div class="auth-tagline">Student Registration</div>
        </div>
        
        <div class="card auth-card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Create Student Account</h3>
                <p class="mb-0 small">Enter your details to register</p>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <p class="mb-0 mt-2">You can now <a href="index.php" class="fw-bold">login</a> with your credentials.</p>
                    </div>
                <?php else: ?>
                
                <form action="process_registration.php" method="post" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="invalid-feedback">Please enter your first name</div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="invalid-feedback">Please enter your last name</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8">
                            </div>
                            <div class="form-text">Must be at least 8 characters long</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            <div class="invalid-feedback">Passwords must match</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_number" class="form-label">Student Number</label>
                            <input type="text" class="form-control" id="student_number" name="student_number" required>
                            <div class="invalid-feedback">Please enter your student number</div>
                        </div>
                        <div class="col-md-6">
                            <label for="year_level" class="form-label">Year Level</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">-- Select Year Level --</option>
                                <?php foreach ($yearLevels as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select your year level</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section" 
                               placeholder="e.g., A, B, C" required>
                        <div class="invalid-feedback">Please enter your section</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="class_code" class="form-label">Class Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="text" class="form-control" id="class_code" name="class_code" 
                                   placeholder="Enter the class code provided by your teacher" required>
                        </div>
                        <div class="form-text">You must enter a valid class code to register.</div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation script
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Check if passwords match
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords do not match");
            event.preventDefault();
            event.stopPropagation();
        } else {
            confirmPassword.setCustomValidity("");
        }
        
        form.classList.add('was-validated');
    });
    
    // Clear custom validity when typing
    confirmPassword.addEventListener('input', function() {
        if (password.value === confirmPassword.value) {
            confirmPassword.setCustomValidity("");
        } else {
            confirmPassword.setCustomValidity("Passwords do not match");
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
