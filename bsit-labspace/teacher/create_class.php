<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Redirect if password change is required
if (needsPasswordChange($_SESSION['user_id'])) {
    header('Location: change_password.php');
    exit;
}

$message = '';
$messageType = '';
$generatedCode = '';

// Get available subjects for dropdown
$subjects = getAllSubjects();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = $_POST['subject_id'] ?? 0;
    $section = $_POST['section'] ?? '';
    $yearLevel = $_POST['year_level'] ?? 0;
    $schoolYear = $_POST['school_year'] ?? '';
    $semester = $_POST['semester'] ?? 0;
    
    // Simple validation
    if (empty($subjectId) || empty($section) || empty($yearLevel) || empty($schoolYear) || empty($semester)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } else {
        // Create class with a unique code
        $result = createClass($_SESSION['user_id'], $subjectId, $section, $yearLevel, $schoolYear, $semester);
        
        if ($result['success']) {
            $message = "Class created successfully!";
            $messageType = "success";
            $generatedCode = $result['class_code'];
            // Clear form after successful submission
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
}

// Get current school year (e.g., "2023-2024")
$currentYear = date('Y');
$nextYear = $currentYear + 1;
$currentSchoolYear = $currentYear . '-' . $nextYear;

// Get current semester (rough estimation)
$currentMonth = date('n');
$currentSemester = ($currentMonth >= 6 && $currentMonth <= 10) ? 1 : 2;

$pageTitle = "Create Class";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create New Class</h1>
        <div>
            <a href="classes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <?php if ($generatedCode): ?>
                <div class="mt-2">
                    <strong>Class Code:</strong> 
                    <span class="fs-5 fw-bold"><?php echo $generatedCode; ?></span>
                    <p class="mt-1">Share this code with your students to enroll in this class.</p>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Class Details</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="">-- Select Year --</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="section" name="section" 
                                       placeholder="e.g., A, B, C, BSIT-1A" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="school_year" class="form-label">School Year</label>
                                <select class="form-select" id="school_year" name="school_year" required>
                                    <option value="<?php echo $currentSchoolYear; ?>" selected>
                                        <?php echo $currentSchoolYear; ?>
                                    </option>
                                    <option value="<?php echo ($currentYear+1).'-'.($nextYear+1); ?>">
                                        <?php echo ($currentYear+1).'-'.($nextYear+1); ?>
                                    </option>
                                    <option value="<?php echo ($currentYear-1).'-'.$currentYear; ?>">
                                        <?php echo ($currentYear-1).'-'.$currentYear; ?>
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="1" <?php echo $currentSemester == 1 ? 'selected' : ''; ?>>First Semester</option>
                                    <option value="2" <?php echo $currentSemester == 2 ? 'selected' : ''; ?>>Second Semester</option>
                                    <option value="3">Summer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Create Class</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-info-circle"></i> Creating a new class will generate a unique class code.</p>
                    <p><i class="fas fa-users"></i> Share this code with your students so they can enroll in your class.</p>
                    <p><i class="fas fa-book"></i> You can add modules and activities to your class after creation.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
