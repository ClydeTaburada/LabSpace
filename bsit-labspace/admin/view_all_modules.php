<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/class_functions.php';
require_once '../includes/functions/module_functions.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Get all modules from database with class and subject info
$pdo = getDbConnection();
$modules = [];

try {
    $stmt = $pdo->query("
        SELECT 
            m.id, m.title, m.description, m.is_published, m.created_at,
            c.id AS class_id, c.section, c.year_level,
            s.code AS subject_code, s.name AS subject_name,
            CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
            u.id AS teacher_id,
            (SELECT COUNT(*) FROM activities a WHERE a.module_id = m.id) AS activity_count
        FROM 
            modules m
            JOIN classes c ON m.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            JOIN users u ON c.teacher_id = u.id
        ORDER BY 
            m.created_at DESC
    ");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error
    error_log('Error fetching modules: ' . $e->getMessage());
}

$pageTitle = "All Modules";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>All Modules</h1>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="filter-subject" class="form-label">Filter by Subject:</label>
                    <select id="filter-subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php
                        $subjects = [];
                        foreach ($modules as $module) {
                            $subjectKey = $module['subject_code'];
                            if (!in_array($subjectKey, $subjects)) {
                                $subjects[] = $subjectKey;
                                echo '<option value="' . htmlspecialchars($subjectKey) . '">' . 
                                     htmlspecialchars($module['subject_code'] . ' - ' . $module['subject_name']) . 
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-teacher" class="form-label">Filter by Teacher:</label>
                    <select id="filter-teacher" class="form-select">
                        <option value="">All Teachers</option>
                        <?php
                        $teachers = [];
                        foreach ($modules as $module) {
                            $teacherKey = $module['teacher_id'];
                            if (!in_array($teacherKey, $teachers)) {
                                $teachers[] = $teacherKey;
                                echo '<option value="' . $teacherKey . '">' . 
                                     htmlspecialchars($module['teacher_name']) . 
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search-modules" class="form-label">Search:</label>
                    <input type="text" id="search-modules" class="form-control" placeholder="Search modules...">
                </div>
            </div>
        </div>
    </div>

    <!-- Modules Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="modules-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Activities</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($modules): ?>
                            <?php foreach ($modules as $module): ?>
                                <tr data-subject="<?php echo htmlspecialchars($module['subject_code']); ?>" 
                                    data-teacher="<?php echo $module['teacher_id']; ?>">
                                    <td><?php echo $module['id']; ?></td>
                                    <td><?php echo htmlspecialchars($module['title']); ?></td>
                                    <td><?php echo htmlspecialchars($module['subject_code'] . ' - ' . $module['subject_name']); ?></td>
                                    <td>
                                        Section <?php echo htmlspecialchars($module['section']); ?><br>
                                        <small class="text-muted">Year <?php echo $module['year_level']; ?></small>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $module['teacher_id']; ?>">
                                            <?php echo htmlspecialchars($module['teacher_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo $module['activity_count']; ?> activities
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($module['is_published']): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($module['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../teacher/module_activities.php?module_id=<?php echo $module['id']; ?>" class="btn btn-outline-primary" title="View Activities">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <a href="../teacher/edit_module.php?id=<?php echo $module['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No modules found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Total: <span id="module-count"><?php echo count($modules); ?></span> modules
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
    const filterSubject = document.getElementById('filter-subject');
    const filterTeacher = document.getElementById('filter-teacher');
    const searchInput = document.getElementById('search-modules');
    const table = document.getElementById('modules-table');
    const rows = table.querySelectorAll('tbody tr');
    const moduleCountSpan = document.getElementById('module-count');
    
    function filterModules() {
        const subjectValue = filterSubject.value;
        const teacherValue = filterTeacher.value;
        const searchValue = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const subject = row.dataset.subject;
            const teacher = row.dataset.teacher;
            const title = row.cells[1].textContent.toLowerCase();
            const className = row.cells[3].textContent.toLowerCase();
            
            // Check if matches filters
            const matchesSubject = !subjectValue || subject === subjectValue;
            const matchesTeacher = !teacherValue || teacher === teacherValue;
            const matchesSearch = title.includes(searchValue) || 
                                className.includes(searchValue);
            
            // Show/hide row based on filters
            const isVisible = matchesSubject && matchesTeacher && matchesSearch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
            }
        });
        
        moduleCountSpan.textContent = visibleCount;
    }
    
    filterSubject.addEventListener('change', filterModules);
    filterTeacher.addEventListener('change', filterModules);
    searchInput.addEventListener('input', filterModules);
    
    // Export to CSV
    document.getElementById('export-csv').addEventListener('click', function() {
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        
        // Create CSV content
        let csvContent = 'data:text/csv;charset=utf-8,ID,Title,Subject,Class,Teacher,Activities,Status,Created\n';
        
        visibleRows.forEach(row => {
            const id = row.cells[0].textContent;
            const title = row.cells[1].textContent.replace(/,/g, ' ');
            const subject = row.cells[2].textContent.replace(/,/g, ' ');
            const className = row.cells[3].textContent.replace(/,/g, ' ').replace(/\n/g, ' ').trim();
            const teacher = row.cells[4].textContent.trim();
            const activities = row.cells[5].textContent.trim();
            const status = row.cells[6].textContent.trim();
            const created = row.cells[7].textContent;
            
            csvContent += `${id},"${title}","${subject}","${className}","${teacher}","${activities}","${status}","${created}"\n`;
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'modules_export.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
