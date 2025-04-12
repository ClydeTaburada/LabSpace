<?php
/**
 * Class management functions
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Get all subjects from the database
 * 
 * @return array Array of subjects
 */
function getAllSubjects() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT id, code, name, description, is_programming FROM subjects ORDER BY code");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching subjects: ' . $e->getMessage());
        return [];
    }
}

/**
 * Create a new class
 * 
 * @param int $teacherId Teacher's user ID
 * @param int $subjectId Subject ID
 * @param string $section Class section
 * @param int $yearLevel Year level
 * @param string $schoolYear School year (e.g., 2023-2024)
 * @param int $semester Semester number
 * @return array Result with success flag, message and class_code
 */
function createClass($teacherId, $subjectId, $section, $yearLevel, $schoolYear, $semester) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get subject code for creating the class code
        $stmt = $pdo->prepare("SELECT code FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$subject) {
            return ['success' => false, 'message' => 'Subject not found'];
        }
        
        // Generate a unique class code
        $classCode = generateClassCode($subject['code'], $yearLevel, $section, $schoolYear);
        
        // Insert into classes table
        $stmt = $pdo->prepare("
            INSERT INTO classes (subject_id, teacher_id, class_code, section, year_level, school_year, semester, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$subjectId, $teacherId, $classCode, $section, $yearLevel, $schoolYear, $semester]);
        
        // Get the new class ID
        $classId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Class created successfully',
            'class_id' => $classId,
            'class_code' => $classCode
        ];
    } catch (PDOException $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        error_log('Error creating class: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create class: ' . $e->getMessage()];
    }
}

/**
 * Generate a unique class code
 * 
 * @param string $subjectCode Subject code (e.g., CS101)
 * @param int $yearLevel Year level
 * @param string $section Section
 * @param string $schoolYear School year (e.g., 2023-2024)
 * @return string Unique class code
 */
function generateClassCode($subjectCode, $yearLevel, $section, $schoolYear) {
    // Extract the year part from school year (e.g., 23 from 2023-2024)
    $yearParts = explode('-', $schoolYear);
    $yearSuffix = substr($yearParts[0], -2);
    
    // Base code format: SUBJECT-YEARLEVEL-SECTION-YEARSUFFIX
    $baseCode = strtoupper($subjectCode) . '-' . $yearLevel . $section . '-' . $yearSuffix;
    
    // Add random characters to ensure uniqueness
    $randomChar = strtoupper(substr(md5(uniqid(rand(), true)), 0, 3));
    
    return $baseCode . $randomChar;
}

/**
 * Get classes for a teacher
 * 
 * @param int $teacherId Teacher's user ID
 * @param int $limit Optional limit for number of classes to return (0 for all)
 * @return array Array of classes with subject and enrollment info
 */
function getTeacherClasses($teacherId, $limit = 0) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                c.id, c.class_code, c.section, c.year_level, c.school_year, c.semester, c.is_active,
                s.id AS subject_id, s.code AS subject_code, s.name AS subject_name,
                COUNT(e.id) AS student_count
            FROM 
                classes c
                JOIN subjects s ON c.subject_id = s.id
                LEFT JOIN class_enrollments e ON c.id = e.class_id
            WHERE 
                c.teacher_id = ?
            GROUP BY 
                c.id
            ORDER BY 
                c.is_active DESC, c.school_year DESC, c.semester ASC, s.code ASC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$teacherId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching teacher classes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Toggle class active status
 * 
 * @param int $classId Class ID
 * @param int $status New status (1 = active, 0 = inactive)
 * @param int $teacherId Teacher ID to verify ownership
 * @return bool True if status was updated successfully
 */
function toggleClassStatus($classId, $status, $teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // Verify teacher owns this class
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$classId, $teacherId]);
        
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE classes SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $classId]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Error toggling class status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get a class by ID with related information
 * 
 * @param int $classId Class ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @return array|null Class data or null if not found
 */
function getClassById($classId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $query = "
            SELECT 
                c.id, c.class_code, c.section, c.year_level, c.school_year, c.semester, c.is_active,
                s.id AS subject_id, s.code AS subject_code, s.name AS subject_name,
                u.id AS teacher_id, u.first_name AS teacher_first_name, u.last_name AS teacher_last_name,
                COUNT(DISTINCT e.id) AS student_count,
                COUNT(DISTINCT m.id) AS module_count
            FROM 
                classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN users u ON c.teacher_id = u.id
                LEFT JOIN class_enrollments e ON c.id = e.class_id
                LEFT JOIN modules m ON c.id = m.class_id
            WHERE 
                c.id = ?
        ";
        
        $params = [$classId];
        
        // Add teacher check if provided
        if ($teacherId !== null) {
            $query .= " AND c.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        $query .= " GROUP BY c.id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting class by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get a class by ID for a student with verification of enrollment
 * 
 * @param int $classId Class ID
 * @param int $studentId Student ID
 * @return array|null Class data or null if not found or student not enrolled
 */
function getStudentClassById($classId, $studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $sql = "SELECT c.*, 
                u.first_name, u.last_name, 
                CONCAT(u.first_name, ' ', u.last_name) AS instructor_name,
                s.code AS subject_code, s.name AS subject_name,
                c.section, c.school_year AS academic_term,
                c.year_level, c.semester, c.is_active
                FROM classes c 
                JOIN class_enrollments e ON c.id = e.class_id
                JOIN subjects s ON c.subject_id = s.id
                JOIN users u ON c.teacher_id = u.id
                WHERE c.id = ? AND e.student_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$classId, $studentId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting student class: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get deadlines for activities in a class
 * 
 * @param int $classId Class ID
 * @param int $limit Maximum number of deadlines to return
 * @return array Array of deadlines
 */
function getClassDeadlines($classId, $limit = 5) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                a.id, a.title, a.due_date, a.activity_type,
                m.id AS module_id, m.title AS module_title
            FROM 
                activities a
                JOIN modules m ON a.module_id = m.id
            WHERE 
                m.class_id = ? AND
                a.is_published = 1 AND
                m.is_published = 1 AND
                a.due_date IS NOT NULL AND
                a.due_date >= CURRENT_DATE
            ORDER BY 
                a.due_date ASC
            LIMIT ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$classId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting class deadlines: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get semester name from semester number
 * 
 * @param int $semesterNum Semester number (1, 2, or 3)
 * @return string Semester name
 */
function getSemesterName($semesterNum) {
    switch ($semesterNum) {
        case 1:
            return '1st Semester';
        case 2:
            return '2nd Semester';
        case 3:
            return 'Summer';
        default:
            return 'Unknown';
    }
}

/**
 * Get class by class code
 * 
 * @param string $classCode Class code
 * @return array|null Class data or null if not found
 */
function getClassByCode($classCode) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*, s.code AS subject_code, s.name AS subject_name,
                CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
            FROM 
                classes c
                JOIN subjects s ON c.subject_id = s.id
                JOIN users u ON c.teacher_id = u.id
            WHERE 
                c.class_code = ?
        ");
        $stmt->execute([$classCode]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting class by code: ' . $e->getMessage());
        return null;
    }
}

/**
 * Enroll a student in a class
 * 
 * @param int $classId Class ID
 * @param int $studentId Student ID
 * @return array Result with success flag and message
 */
function enrollStudent($classId, $studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Check if class exists and is active
        $stmt = $pdo->prepare("SELECT id, is_active FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            return ['success' => false, 'message' => 'Class not found'];
        }
        
        if (!$class['is_active']) {
            return ['success' => false, 'message' => 'This class is not active for enrollment'];
        }
        
        // Check if student is already enrolled
        $stmt = $pdo->prepare("SELECT id FROM class_enrollments WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$classId, $studentId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You are already enrolled in this class'];
        }
        
        // Enroll the student
        $stmt = $pdo->prepare("INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)");
        $stmt->execute([$classId, $studentId]);
        
        return ['success' => true, 'message' => 'Enrollment successful'];
    } catch (PDOException $e) {
        error_log('Error enrolling student: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Enrollment failed: ' . $e->getMessage()];
    }
}

/**
 * Get enrolled students for a class
 *
 * @param int $classId Class ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @return array Array of enrolled students
 */
function getEnrolledStudents($classId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Build the query
        $query = "
            SELECT 
                u.id, u.first_name, u.last_name, u.email, u.created_at,
                sp.year_level, sp.section, sp.student_number,
                ce.enrollment_date
            FROM 
                class_enrollments ce
                JOIN users u ON ce.student_id = u.id
                JOIN student_profiles sp ON u.id = sp.student_id
            WHERE 
                ce.class_id = ?
        ";
        
        $params = [$classId];
        
        // If teacher ID is provided, verify ownership
        if ($teacherId !== null) {
            $query .= " AND EXISTS (
                SELECT 1 FROM classes c
                WHERE c.id = ce.class_id AND c.teacher_id = ?
            )";
            $params[] = $teacherId;
        }
        
        $query .= " ORDER BY u.last_name, u.first_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting enrolled students: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get classes that a student is enrolled in
 * 
 * @param int $studentId Student ID
 * @return array Array of classes with subject info and teacher name
 */
function getStudentClasses($studentId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                c.id, c.class_code, c.section, c.year_level, c.school_year, c.semester, c.is_active,
                s.id AS subject_id, s.code AS subject_code, s.name AS subject_name, s.is_programming,
                CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
                ce.enrollment_date
            FROM 
                class_enrollments ce
                JOIN classes c ON ce.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
                JOIN users u ON c.teacher_id = u.id
            WHERE 
                ce.student_id = ? AND c.is_active = 1
            ORDER BY 
                ce.enrollment_date DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$studentId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching student classes: ' . $e->getMessage());
        return [];
    }
}
