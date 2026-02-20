-- ============================================
-- HRMS EXTENSION - DATABASE SCHEMA UPDATES
-- ============================================
-- This script adds new tables and columns for:
-- 1. Holiday Management
-- 2. Master Dropdown Management
-- 3. Employee Profile Enhancements
-- 4. Project Budgeting
-- ============================================

USE hrms_db;

-- ============================================
-- 1. HOLIDAY MANAGEMENT
-- ============================================
-- Requirement: Holiday List 2026 module
-- Purpose: Store company holidays for employees to view

CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('National', 'Optional', 'Company') DEFAULT 'Company',
    year INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_holiday_date (holiday_date),
    INDEX idx_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. MASTER DROPDOWN MANAGEMENT
-- ============================================
-- Requirement: Master Drop-down Management module
-- Purpose: Centralized dropdown management to avoid hardcoding

-- Master table for dropdown categories
CREATE TABLE IF NOT EXISTS dropdown_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_label VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Values table for each dropdown category
CREATE TABLE IF NOT EXISTS dropdown_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    value_text VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES dropdown_categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default dropdown categories
INSERT INTO dropdown_categories (category_name, category_label, description) VALUES
('department', 'Department', 'Employee departments'),
('designation', 'Designation', 'Job titles and designations'),
('employment_type', 'Employment Type', 'Full-time, Part-time, Contract, etc.'),
('location', 'Location', 'Office locations'),
('project_status', 'Project Status', 'Project status values'),
('employee_status', 'Employee Status', 'Employee status values'),
('marital_status', 'Marital Status', 'Marital status options'),
('blood_group', 'Blood Group', 'Blood group types'),
('gender', 'Gender', 'Gender options')
ON DUPLICATE KEY UPDATE category_label=VALUES(category_label);

-- Insert default dropdown values
-- Departments
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'IT', 1 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'HR', 2 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Finance', 3 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Marketing', 4 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Sales', 5 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Operations', 6 FROM dropdown_categories WHERE category_name = 'department';

-- Designations
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Software Engineer', 1 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Senior Software Engineer', 2 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Project Manager', 3 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'HR Manager', 4 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'HR Executive', 5 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Finance Manager', 6 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Finance Analyst', 7 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Marketing Executive', 8 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Sales Representative', 9 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Operations Manager', 10 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Team Lead', 11 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Architect', 12 FROM dropdown_categories WHERE category_name = 'designation';

-- Employment Types
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Full-time', 1 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Part-time', 2 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Contract', 3 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Intern', 4 FROM dropdown_categories WHERE category_name = 'employment_type';

-- Employee Status
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Active', 1 FROM dropdown_categories WHERE category_name = 'employee_status'
UNION ALL SELECT id, 'Inactive', 2 FROM dropdown_categories WHERE category_name = 'employee_status'
UNION ALL SELECT id, 'On Leave', 3 FROM dropdown_categories WHERE category_name = 'employee_status'
UNION ALL SELECT id, 'Terminated', 4 FROM dropdown_categories WHERE category_name = 'employee_status';

-- Marital Status
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Single', 1 FROM dropdown_categories WHERE category_name = 'marital_status'
UNION ALL SELECT id, 'Married', 2 FROM dropdown_categories WHERE category_name = 'marital_status'
UNION ALL SELECT id, 'Divorced', 3 FROM dropdown_categories WHERE category_name = 'marital_status'
UNION ALL SELECT id, 'Widowed', 4 FROM dropdown_categories WHERE category_name = 'marital_status';

-- Blood Groups
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'A+', 1 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'A-', 2 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'B+', 3 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'B-', 4 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'O+', 5 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'O-', 6 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'AB+', 7 FROM dropdown_categories WHERE category_name = 'blood_group'
UNION ALL SELECT id, 'AB-', 8 FROM dropdown_categories WHERE category_name = 'blood_group';

-- Gender
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Male', 1 FROM dropdown_categories WHERE category_name = 'gender'
UNION ALL SELECT id, 'Female', 2 FROM dropdown_categories WHERE category_name = 'gender'
UNION ALL SELECT id, 'Other', 3 FROM dropdown_categories WHERE category_name = 'gender';

-- ============================================
-- 3. EMPLOYEE TABLE ENHANCEMENTS
-- ============================================
-- Requirement: Employee List & Profile Management
-- Purpose: Add missing fields to existing employees table

-- Add designation column if not exists
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS designation VARCHAR(100) AFTER job_title;

-- Add shift_type and required_hours if not exists (for attendance module)
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS shift_type ENUM('fixed', 'flexible') DEFAULT 'fixed' AFTER employee_type;

ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS required_hours DECIMAL(4,2) DEFAULT 8.00 AFTER shift_type;

-- ============================================
-- 4. PROJECT BUDGETING
-- ============================================
-- Requirement: Project Budgeting module
-- Purpose: Track projects, budgets, and employee assignments

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE,
    budget_amount DECIMAL(15,2) DEFAULT 0.00,
    utilized_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('Planning', 'Active', 'On Hold', 'Completed', 'Cancelled') DEFAULT 'Planning',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_project_code (project_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Project employee assignments
CREATE TABLE IF NOT EXISTS project_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    employee_id INT NOT NULL,
    role VARCHAR(100),
    allocation_percentage DECIMAL(5,2) DEFAULT 100.00,
    start_date DATE NOT NULL,
    end_date DATE,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_employee_id (employee_id),
    UNIQUE KEY unique_assignment (project_id, employee_id, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Project budget tracking (optional - for detailed tracking)
CREATE TABLE IF NOT EXISTS project_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    expense_date DATE NOT NULL,
    expense_type VARCHAR(100),
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these to verify tables were created successfully

-- SELECT 'Holidays table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'holidays';
-- SELECT 'Dropdown categories table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'dropdown_categories';
-- SELECT 'Dropdown values table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'dropdown_values';
-- SELECT 'Projects table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'projects';
-- SELECT 'Project assignments table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'project_assignments';
-- SELECT 'Project expenses table' as 'Table', COUNT(*) as 'Exists' FROM information_schema.tables WHERE table_schema = 'hrms_db' AND table_name = 'project_expenses';

-- ============================================
-- 5. LEAVE REQUEST ENHANCEMENTS
-- ============================================
-- Purpose: Support advanced leave management features

-- Alter leave_requests table to add missing fields
ALTER TABLE leave_requests 
ADD COLUMN IF NOT EXISTS leave_days DECIMAL(4,1) DEFAULT 1.0 AFTER end_date,
ADD COLUMN IF NOT EXISTS approved_by INT AFTER status,
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT AFTER approved_at;

-- Insert leave types into master dropdowns
INSERT INTO dropdown_categories (category_name, category_label, description) VALUES
('leave_type', 'Leave Type', 'Types of employee leave')
ON DUPLICATE KEY UPDATE category_label=VALUES(category_label);

INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Casual Leave', 1 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Sick Leave', 2 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Earned Leave', 3 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Maternity Leave', 4 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Paternity Leave', 5 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'LWP (Leave Without Pay)', 6 FROM dropdown_categories WHERE category_name = 'leave_type';

-- ============================================
-- END OF SCHEMA UPDATES
-- ============================================
