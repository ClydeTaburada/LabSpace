<?php
/**
 * Module management functions
 */
require_once __DIR__ . '/../db/config.php';

/**
 * Debugging function for module loading
 * 
 * @param string $message Debug message
 * @param mixed $data Optional additional data
 */
function debug_module_loading($message, $data = null) {
    // Uncomment the next line for debugging
    // error_log('MODULE DEBUG: ' . $message . ($data ? ' - ' . json_encode($data) : ''));
}

/**
 * Get all modules for a class
 * 
 * @param int $classId Class ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @param int $limit Optional limit for the number of modules
 * @return array Array of modules
 */
function getModules($classId, $teacherId = null, $limit = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        debug_module_loading("Database connection failed in getModules");
        return [];
    }
    
    try {
        // Build the query
        $query = "
            SELECT 
                m.id, m.title, m.description, m.order_index, m.is_published, m.publish_date,
                m.created_at, m.updated_at, 
                COUNT(a.id) AS activity_count
            FROM 
                modules m
                LEFT JOIN activities a ON m.id = a.module_id
            WHERE 
                m.class_id = ?
        ";
        
        $params = [$classId];
        
        // If teacher ID is provided, verify ownership
        if ($teacherId !== null) {
            $query .= " AND EXISTS (
                SELECT 1 FROM classes c
                WHERE c.id = m.class_id AND c.teacher_id = ?
            )";
            $params[] = $teacherId;
        }
        
        $query .= " GROUP BY m.id ORDER BY m.order_index ASC, m.id ASC";
        
        // Add limit if specified
        if ($limit !== null) {
            $query .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        debug_module_loading("getModules success", ["count" => count($result)]);
        return $result;
    } catch (PDOException $e) {
        debug_module_loading("Error getting modules: " . $e->getMessage());
        error_log('Error getting modules: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all modules for a specific class
 * 
 * @param int $classId The class ID
 * @return array Array of modules
 */
function getModulesByClassId($classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, description, order_index, created_at, updated_at
            FROM modules
            WHERE class_id = ?
            ORDER BY order_index, created_at
        ");
        
        $stmt->execute([$classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting modules by class ID: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a module by ID
 * 
 * @param int $moduleId Module ID
 * @param int $teacherId Optional teacher ID to verify ownership
 * @return array|null Module data or null if not found
 */
function getModuleById($moduleId, $teacherId = null) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        // Build the query
        $query = "
            SELECT 
                m.*, c.id AS class_id, c.subject_id, c.section, c.year_level,
                s.code AS subject_code, s.name AS subject_name
            FROM 
                modules m
                JOIN classes c ON m.class_id = c.id
                JOIN subjects s ON c.subject_id = s.id
            WHERE 
                m.id = ?
        ";
        
        $params = [$moduleId];
        
        // If teacher ID is provided, verify ownership
        if ($teacherId !== null) {
            $query .= " AND c.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting module by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new module
 * 
 * @param int $classId Class ID
 * @param string $title Module title
 * @param string $description Module description
 * @param int $orderIndex Order index for sorting
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function createModule($classId, $title, $description, $orderIndex, $teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the class
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$classId, $teacherId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'You do not have permission to add modules to this class'];
        }
        
        // Insert the new module
        $stmt = $pdo->prepare("
            INSERT INTO modules (class_id, title, description, order_index, is_published)
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$classId, $title, $description, $orderIndex]);
        
        $moduleId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Module created successfully',
            'module_id' => $moduleId
        ];
    } catch (PDOException $e) {
        error_log('Error creating module: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create module: ' . $e->getMessage()];
    }
}

/**
 * Update a module
 * 
 * @param int $moduleId Module ID
 * @param string $title Module title
 * @param string $description Module description
 * @param int $orderIndex Order index for sorting
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function updateModule($moduleId, $title, $description, $orderIndex, $teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the module
        $stmt = $pdo->prepare("
            SELECT m.id FROM modules m
            JOIN classes c ON m.class_id = c.id
            WHERE m.id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$moduleId, $teacherId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'You do not have permission to edit this module'];
        }
        
        // Update the module
        $stmt = $pdo->prepare("
            UPDATE modules
            SET title = ?, description = ?, order_index = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $orderIndex, $moduleId]);
        
        return [
            'success' => true,
            'message' => 'Module updated successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error updating module: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update module: ' . $e->getMessage()];
    }
}

/**
 * Delete a module
 * 
 * @param int $moduleId Module ID
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function deleteModule($moduleId, $teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the module
        $stmt = $pdo->prepare("
            SELECT m.id, COUNT(a.id) as activity_count
            FROM modules m
            JOIN classes c ON m.class_id = c.id
            LEFT JOIN activities a ON m.id = a.module_id
            WHERE m.id = ? AND c.teacher_id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$moduleId, $teacherId]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$module) {
            return ['success' => false, 'message' => 'You do not have permission to delete this module'];
        }
        
        // Check if module has activities
        if ($module['activity_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete module that contains activities'];
        }
        
        // Delete the module
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->execute([$moduleId]);
        
        return [
            'success' => true,
            'message' => 'Module deleted successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error deleting module: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete module: ' . $e->getMessage()];
    }
}

/**
 * Toggle module publish status
 * 
 * @param int $moduleId Module ID
 * @param bool $isPublished Whether to publish or unpublish
 * @param int $teacherId Teacher ID to verify ownership
 * @return array Result with success flag and message
 */
function toggleModulePublishStatus($moduleId, $isPublished, $teacherId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Verify teacher owns the module
        $stmt = $pdo->prepare("
            SELECT m.id FROM modules m
            JOIN classes c ON m.class_id = c.id
            WHERE m.id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$moduleId, $teacherId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'You do not have permission to modify this module'];
        }
        
        // Set publish date if publishing for the first time
        $publishDate = $isPublished ? 'CURRENT_TIMESTAMP' : 'NULL';
        
        // Update the module
        $stmt = $pdo->prepare("
            UPDATE modules
            SET is_published = ?, publish_date = " . $publishDate . ", updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$isPublished ? 1 : 0, $moduleId]);
        
        return [
            'success' => true,
            'message' => $isPublished ? 'Module published successfully' : 'Module unpublished successfully'
        ];
    } catch (PDOException $e) {
        error_log('Error toggling module publish status: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update module status: ' . $e->getMessage()];
    }
}

/**
 * Get the next available order index for a new module
 * 
 * @param int $classId Class ID
 * @return int Next order index
 */
function getNextModuleOrderIndex($classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return 1; // Default to 1 if DB connection fails
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT MAX(order_index) + 1 AS next_order
            FROM modules
            WHERE class_id = ?
        ");
        $stmt->execute([$classId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['next_order'] ?? 1; // Default to 1 if no modules yet
    } catch (PDOException $e) {
        error_log('Error getting next module order index: ' . $e->getMessage());
        return 1;
    }
}

/**
 * Get published modules for a class (student view)
 * 
 * @param int $classId Class ID
 * @return array Array of modules
 */
function getPublishedModules($classId) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // Modified query to ensure we're properly counting only published activities
        $query = "
            SELECT 
                m.id, m.title, m.description, m.order_index, m.publish_date,
                (SELECT COUNT(*) 
                 FROM activities a 
                 WHERE a.module_id = m.id AND a.is_published = 1) AS activity_count
            FROM 
                modules m
            WHERE 
                m.class_id = ? AND m.is_published = 1
            ORDER BY 
                m.order_index ASC, m.id ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$classId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting published modules: ' . $e->getMessage());
        return [];
    }
}
