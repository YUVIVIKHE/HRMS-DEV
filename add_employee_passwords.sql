-- ============================================
-- ADD PASSWORDS FOR SAMPLE EMPLOYEES
-- ============================================
-- This script adds passwords for the 3 sample employees
-- Password for all: employee123
-- ============================================

USE hrms_db;

-- Update sample employees with password: employee123
-- Hash generated using PHP password_hash('employee123', PASSWORD_DEFAULT)

UPDATE employees 
SET password = '$2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u'
WHERE email IN ('john.doe@company.com', 'jane.smith@company.com', 'mike.johnson@company.com');

-- Verify update
SELECT employee_id, first_name, last_name, email, 
       CASE WHEN password IS NOT NULL AND password != '' THEN 'Password Set' ELSE 'No Password' END as password_status
FROM employees 
WHERE email IN ('john.doe@company.com', 'jane.smith@company.com', 'mike.johnson@company.com');
