-- HRMS DATABASE SCHEMA
-- Version: 1.1 (Consolidated)

-- Create database
CREATE DATABASE IF NOT EXISTS hrms_db;
USE hrms_db;

-- ============================================
-- 1. ACCESS CONTROL & USERS
-- ============================================

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Managers table
CREATE TABLE IF NOT EXISTS managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    personal_email VARCHAR(100),
    phone VARCHAR(20),
    country_code VARCHAR(10),
    emergency_contact VARCHAR(20),
    job_title VARCHAR(100),
    designation VARCHAR(100),
    department VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status VARCHAR(50),
    blood_group VARCHAR(10),
    place_of_birth VARCHAR(100),
    nationality VARCHAR(100),
    date_of_joining DATE,
    date_of_confirmation DATE,
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    perm_address_line1 VARCHAR(255),
    perm_address_line2 VARCHAR(255),
    perm_city VARCHAR(100),
    perm_state VARCHAR(100),
    perm_zip_code VARCHAR(20),
    status VARCHAR(50) DEFAULT 'active',
    account_type VARCHAR(50),
    ifsc_code VARCHAR(20),
    account_number VARCHAR(50),
    pan VARCHAR(20),
    aadhar_no VARCHAR(20),
    uan_number VARCHAR(50),
    pf_account_number VARCHAR(50),
    epf VARCHAR(100),
    professional_tax VARCHAR(50),
    tax_exempt BOOLEAN DEFAULT 0,
    esi_number VARCHAR(50),
    direct_manager VARCHAR(100),
    location VARCHAR(100),
    base_location VARCHAR(100),
    place_of_issue VARCHAR(100),
    passport_expiry DATE,
    passport_issue DATE,
    passport_no VARCHAR(50),
    user_code VARCHAR(50),
    employee_type VARCHAR(50),
    shift_type ENUM('fixed', 'flexible') DEFAULT 'fixed',
    required_hours DECIMAL(4,2) DEFAULT 8.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 2. ATTENDANCE & LEAVES
-- ============================================

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in TIME,
    clock_out TIME,
    total_hours DECIMAL(5,2) DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('present', 'absent', 'half-day', 'leave') DEFAULT 'present',
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date)
);

-- Attendance regularization table
CREATE TABLE IF NOT EXISTS attendance_regularization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_id INT NOT NULL,
    date DATE NOT NULL,
    type ENUM('late_in', 'early_out', 'out_of_office', 'forgot_clock_out', 'other') NOT NULL,
    requested_clock_in TIME,
    requested_clock_out TIME,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Overtime requests table
CREATE TABLE IF NOT EXISTS overtime_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_ot_hours DECIMAL(5,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Leave requests table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    leave_days DECIMAL(4,1) DEFAULT 1.0,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================
-- 3. PROJECTS & HOLIDAYS
-- ============================================

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

-- Project assignments
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

-- Project expenses
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
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Holidays table
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
-- 4. MASTER DATA & SYSTEM
-- ============================================

-- Dropdown categories
CREATE TABLE IF NOT EXISTS dropdown_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_label VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dropdown values
CREATE TABLE IF NOT EXISTS dropdown_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    value_text VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES dropdown_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'urgent') DEFAULT 'info',
    target_audience ENUM('all', 'department', 'specific') DEFAULT 'all',
    department VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- 5. INITIAL DATA SEEDING
-- ============================================

-- Default Admin (Password: admin123)
INSERT INTO admins (name, email, password) VALUES 
('Admin User', 'admin@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Dropdown Categories
INSERT INTO dropdown_categories (category_name, category_label, description) VALUES
('department', 'Department', 'Employee departments'),
('designation', 'Designation', 'Job titles and designations'),
('employment_type', 'Employment Type', 'Full-time, Part-time, Contract, etc.'),
('location', 'Location', 'Office locations'),
('project_status', 'Project Status', 'Project status values'),
('employee_status', 'Employee Status', 'Employee status values'),
('marital_status', 'Marital Status', 'Marital status options'),
('blood_group', 'Blood Group', 'Blood group types'),
('gender', 'Gender', 'Gender options'),
('leave_type', 'Leave Type', 'Types of employee leave');

-- Default Dropdown Values
INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'IT', 1 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'HR', 2 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Finance', 3 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Marketing', 4 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Sales', 5 FROM dropdown_categories WHERE category_name = 'department'
UNION ALL SELECT id, 'Operations', 6 FROM dropdown_categories WHERE category_name = 'department';

INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Software Engineer', 1 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Senior Software Engineer', 2 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Project Manager', 3 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'HR Manager', 4 FROM dropdown_categories WHERE category_name = 'designation'
UNION ALL SELECT id, 'Finance Analyst', 5 FROM dropdown_categories WHERE category_name = 'designation';

INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Full-time', 1 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Part-time', 2 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Contract', 3 FROM dropdown_categories WHERE category_name = 'employment_type'
UNION ALL SELECT id, 'Intern', 4 FROM dropdown_categories WHERE category_name = 'employment_type';

INSERT INTO dropdown_values (category_id, value_text, display_order) 
SELECT id, 'Casual Leave', 1 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Sick Leave', 2 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Earned Leave', 3 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'Maternity Leave', 4 FROM dropdown_categories WHERE category_name = 'leave_type'
UNION ALL SELECT id, 'LWP (Leave Without Pay)', 6 FROM dropdown_categories WHERE category_name = 'leave_type';

-- Sample Employees (Password for all: employee123)
-- Hash: $2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u
INSERT INTO employees (employee_id, first_name, last_name, email, password, phone, department, job_title, status) VALUES
('EMP001', 'John', 'Doe', 'john.doe@company.com', '$2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u', '1234567890', 'IT', 'Software Engineer', 'active'),
('EMP002', 'Jane', 'Smith', 'jane.smith@company.com', '$2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u', '0987654321', 'HR', 'HR Manager', 'active'),
('EMP003', 'Mike', 'Johnson', 'mike.johnson@company.com', '$2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u', '5551234567', 'Finance', 'Accountant', 'active');
