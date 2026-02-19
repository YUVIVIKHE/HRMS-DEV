-- Create database
CREATE DATABASE IF NOT EXISTS hrms_db;
USE hrms_db;

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
    department VARCHAR(100),
    user_code VARCHAR(50),
    employee_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Leave requests table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

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

-- Insert default admin (password: admin123)
INSERT INTO admins (name, email, password) VALUES 
('Admin User', 'admin@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample employees
INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, job_title, date_of_joining, gender, status) VALUES
('EMP001', 'John', 'Doe', 'john.doe@company.com', '1234567890', 'IT', 'Software Engineer', '2023-01-15', 'Male', 'active'),
('EMP002', 'Jane', 'Smith', 'jane.smith@company.com', '0987654321', 'HR', 'HR Manager', '2022-06-20', 'Female', 'active'),
('EMP003', 'Mike', 'Johnson', 'mike.johnson@company.com', '5551234567', 'Finance', 'Accountant', '2023-03-10', 'Male', 'active');
