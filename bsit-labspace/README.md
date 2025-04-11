# BSIT-LabSpace

A laboratory management system for BSIT students.

## Project Structure

```
bsit-labspace/
├── admin/              # Admin area
├── activities/         # Learning activities
├── assets/             # Static assets
│   ├── css/            # CSS files
│   ├── images/         # Image files
│   └── js/             # JavaScript files
├── classes/            # Classes management
├── includes/           # Includes (shared code)
│   ├── db/             # Database related files
│   └── functions/      # Helper functions
├── modules/            # Learning modules
├── student/            # Student area
├── submissions/        # Student submissions
├── teacher/            # Teacher area
├── tests/              # Unit tests
└── index.php           # Entry point
```

## Getting Started

1. Clone this repository into your XAMPP htdocs folder
2. Create a new MySQL database named "bsit_labspace"
3. Import the database schema from `includes/db/schema.sql`
4. Configure database connection in `includes/db/config.php`
5. Access the application at http://localhost/bsit-labspace/

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or equivalent)

## Features

- User management with role-based access control
- Class management for teachers
- Assignment and activity management
- Code execution environment for programming tasks
- Student progress tracking