<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Get all activities from database with related info
$pdo = getDbConnection();
$activities = [];

try {
    $stmt = $pdo->query("
        SELECT 
            a.id, a.title, a.activity_type, a.is_published, a.due_date, a.created_at,
            m.id AS module_id, m.title AS module_title,
            c.id AS class_id, c.section,
            s.code AS subject_code, s.name AS subject_name,
            CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
            u.id AS teacher_id,
            (SELECT COUNT(*) FROM activity_submissions sub WHERE sub.activity_id = a.id) AS submission_count
        FROM 
            activities a
            JOIN modules m ON a.module_id = m.id
            JOIN classes c ON m.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            JOIN users u ON c.teacher_id = u.id
        ORDER BY 
            a.created_at DESC
    ");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error
    error_log('Error fetching activities: ' . $e->getMessage());
}

$pageTitle = "All Activities";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>All Activities</h1>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="filter-type" class="form-label">Activity Type:</label>
                    <select id="filter-type" class="form-select">
                        <option value="">All Types</option>
                        <option value="assignment">Assignment</option>
                        <option value="quiz">Quiz</option>
                        <option value="coding">Coding Task</option>
                        <option value="lab">Lab Exercise</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-subject" class="form-label">Subject:</label>
                    <select id="filter-subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php
                        $subjects = [];
                        foreach ($activities as $activity) {
                            $subjectKey = $activity['subject_code'];
                            if (!in_array($subjectKey, $subjects)) {
                                $subjects[] = $subjectKey;
                                echo '<option value="' . htmlspecialchars($subjectKey) . '">' . 
                                     htmlspecialchars($activity['subject_code'] . ' - ' . $activity['subject_name']) . 
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-teacher" class="form-label">Teacher:</label>
                    <select id="filter-teacher" class="form-select">
                        <option value="">All Teachers</option>
                        <?php
                        $teachers = [];
                        foreach ($activities as $activity) {
                            $teacherKey = $activity['teacher_id'];
                            if (!in_array($teacherKey, $teachers)) {
                                $teachers[] = $teacherKey;
                                echo '<option value="' . $teacherKey . '">' . 
                                     htmlspecialchars($activity['teacher_name']) . 
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search-activities" class="form-label">Search:</label>
                    <input type="text" id="search-activities" class="form-control" placeholder="Search activities...">
                </div>
            </div>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="activities-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Module</th>
                            <th>Due Date</th>
                            <th>Submissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($activities): ?>
                            <?php foreach ($activities as $activity): ?>
                                <tr data-type="<?php echo $activity['activity_type']; ?>" 
                                    data-subject="<?php echo htmlspecialchars($activity['subject_code']); ?>" 
                                    data-teacher="<?php echo $activity['teacher_id']; ?>">
                                    <td><?php echo $activity['id']; ?></td>
                                    <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getActivityBadgeClass($activity['activity_type']); ?>">
                                            <?php echo getActivityTypeName($activity['activity_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['subject_code']); ?></td>
                                    <td>
                                        <a href="../teacher/module_activities.php?module_id=<?php echo $activity['module_id']; ?>">
                                            <?php echo htmlspecialchars($activity['module_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($activity['due_date']) {
                                            $dueDate = new DateTime($activity['due_date']);
                                            $now = new DateTime();
                                            $isPastDue = $dueDate < $now;
                                            
                                            echo '<span class="' . ($isPastDue ? 'text-danger' : '') . '">' . 
                                                 $dueDate->format('M j, Y') . 
                                                 '</span>';
                                        } else {
                                            echo '<span class="text-muted">No due date</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo $activity['submission_count']; ?> submissions
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($activity['is_published']): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../teacher/edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../teacher/view_submissions.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-primary" title="View Submissions">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No activities found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Total: <span id="activity-count"><?php echo count($activities); ?></span> activities
                </div>
                <div>
                    <button type="button" id="export-csv" class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtering functionality
    const filterType = document.getElementById('filter-type');
    const filterSubject = document.getElementById('filter-subject');
    const filterTeacher = document.getElementById('filter-teacher');
    const searchInput = document.getElementById('search-activities');
    const table = document.getElementById('activities-table');
    const rows = table.querySelectorAll('tbody tr');
    const activityCountSpan = document.getElementById('activity-count');
    
    function filterActivities() {
        const typeValue = filterType.value;
        const subjectValue = filterSubject.value;
        const teacherValue = filterTeacher.value;
        const searchValue = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const type = row.dataset.type;
            const subject = row.dataset.subject;
            const teacher = row.dataset.teacher;
            const title = row.cells[1].textContent.toLowerCase();
            const module = row.cells[4].textContent.toLowerCase();
            
            // Check if matches filters
            const matchesType = !typeValue || type === typeValue;
            const matchesSubject = !subjectValue || subject === subjectValue;
            const matchesTeacher = !teacherValue || teacher === teacherValue;
            const matchesSearch = title.includes(searchValue) || 
                                module.includes(searchValue);
            
            // Show/hide row based on filters
            const isVisible = matchesType && matchesSubject && matchesTeacher && matchesSearch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
            }
        });
        
        activityCountSpan.textContent = visibleCount;
    }
    
    filterType.addEventListener('change', filterActivities);
    filterSubject.addEventListener('change', filterActivities);
    filterTeacher.addEventListener('change', filterActivities);
    searchInput.addEventListener('input', filterActivities);
    
    // Export to CSV
    document.getElementById('export-csv').addEventListener('click', function() {
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        
        // Create CSV content
        let csvContent = 'data:text/csv;charset=utf-8,ID,Title,Type,Subject,Module,Due Date,Submissions,Status\n';
        
        visibleRows.forEach(row => {
            const id = row.cells[0].textContent;
            const title = row.cells[1].textContent.replace(/,/g, ' ').replace(/"/g, '""');
            const type = row.cells[2].textContent.trim();
            const subject = row.cells[3].textContent;
            const module = row.cells[4].textContent.replace(/,/g, ' ').replace(/"/g, '""').trim();
            const dueDate = row.cells[5].textContent.trim();
            const submissions = row.cells[6].textContent.trim();
            const status = row.cells[7].textContent.trim();
            
            csvContent += `${id},"${title}","${type}","${subject}","${module}","${dueDate}","${submissions}","${status}"\n`;
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'activities_export.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
