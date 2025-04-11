<?php
/**
 * Database Configuration
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'bsit_labspace');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get database connection
 * @return PDO|null PDO object if connection successful, null otherwise
 */
function getDbConnection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database Connection Error: ' . $e->getMessage());
        return null;
    }
}