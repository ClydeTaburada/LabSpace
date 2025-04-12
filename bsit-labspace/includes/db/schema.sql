-- BSIT LabSpace Database Schema

-- Create users table with common fields
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'teacher', 'admin') NOT NULL,
    force_password_change BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create student profiles table
CREATE TABLE student_profiles (
    student_id INT PRIMARY KEY,
    year_level INT NOT NULL,
    section VARCHAR(10),
    student_number VARCHAR(20) UNIQUE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create teacher profiles table
CREATE TABLE teacher_profiles (
    teacher_id INT PRIMARY KEY,
    department VARCHAR(50),
    employee_id VARCHAR(20) UNIQUE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_programming BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    section VARCHAR(20) NOT NULL,
    year_level INT NOT NULL,
    school_year VARCHAR(9) NOT NULL,
    semester INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Create class enrollments table
CREATE TABLE class_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Create modules table
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    publish_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Create activities table
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    activity_type ENUM('assignment', 'quiz', 'coding', 'lab') NOT NULL,
    instructions TEXT,
    max_score INT DEFAULT 100,
    coding_starter_code TEXT,
    test_cases TEXT,
    due_date TIMESTAMP NULL,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id)
);

-- Create submissions table
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('code', 'file', 'text') NOT NULL,
    code TEXT,
    language VARCHAR(20) DEFAULT 'unknown',
    content TEXT,
    file_path VARCHAR(255),
    score DECIMAL(5,2),
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    graded_by INT,
    FOREIGN KEY (activity_id) REFERENCES activities(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (graded_by) REFERENCES users(id)
);

-- Table for storing activity submissions
CREATE TABLE IF NOT EXISTS activity_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    student_id INT NOT NULL,
    code TEXT NOT NULL,
    language VARCHAR(20) DEFAULT 'unknown',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded BOOLEAN DEFAULT FALSE,
    grade INT NULL,
    auto_grade INT NULL,
    test_results TEXT NULL,
    feedback TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_student (activity_id, student_id)
);