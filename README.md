# HRMS - Professional Human Resource Management System

A comprehensive Human Resource Management System (HRMS) built with PHP and MySQL, featuring a modern, responsive UI and extensive administrative and employee-facing modules.

## 🚀 Quick Start (5 Minutes)

### 1. Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `hrms_db`.
3. Select `hrms_db` and click the **Import** tab.
4. Select the `database.sql` file from the project root and click **Go**.
   - This single file contains the complete schema and initial data.

### 2. Configure project
- Edit `config.php` if your MySQL port or credentials differ:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3307'); // Default for this project
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hrms_db');
```

### 3. Access Application
- Open: `http://localhost/hrms/login.php`

---

## 🔐 Default Login Credentials

| User Role | Email | Password | Access Level |
|-----------|-------|----------|--------------|
| **Admin** | `admin@hrms.com` | `admin123` | Full System Access |
| **Manager** | `manager@hrms.com` | `manager123` | Team & Dept Management |
| **Employee**| `john.doe@company.com` | `employee123` | Personal Attendance & Profile |
| **Employee**| `jane.smith@company.com` | `employee123` | Personal Attendance & Profile |

---

## ✨ Features Breakdown

### 🛡️ Administrative Modules
- **Employee Management**: Add/Edit/Delete employees with 50+ fields, bulk CSV upload, and auto-generated credentials.
- **Holiday Management**: Complete CRUD for company holidays with year filtering (2024-2030).
- **Master Data Management**: Centralized control over dropdown values (Departments, Designations, Locations, etc.) to eliminate hardcoding.
- **Project Budgeting**: Track projects, budgets, utilized amounts, and team assignments with visual progress bars.
- **Leave Approval**: Review and act on employee leave requests with mandatory rejection reasons.

### 👥 Managerial Features
- **Team Dashboard**: Real-time stats on team presence and leave status.
- **Activity Feed**: Live log of team clock-ins and leave requests.
- **Attendance Reporting**: Comprehensive history of team presence with date filtering.

### 💻 Employee Self-Service
- **Modern Dashboard**: Personal statistics for attendance, hours, and assigned projects.
- **Attendance System**: Web-based Clock IN/OUT with automatic overtime calculation.
- **Leave Requests**: Submit and track leave requests with automatic day calculation.
- **Personal Profile**: Complete view of personal, contact, employment, and bank information.

---

## 🛠️ Security & UI
- **Responsive Design**: Inspired by modern SaaS platforms, optimized for mobile, tablet, and desktop.
- **Secure Auth**: Passwords hashed using `password_hash()` (Bcrypt).
- **SQL Protection**: All database interactions use prepared statements to prevent SQL injection.
- **Smooth UX**: Collapsible sidebars, modal-based forms, and professional micro-animations.

---

## 📁 File Structure
```
hrms/
├── database.sql           # SINGLE master database schema
├── README.md              # SINGLE master documentation
├── config.php             # Database configuration
├── login.php              # Secure login portal
├── dashboard.php          # Admin dashboard
├── manager_dashboard.php   # Manager-specific view
├── employee_dashboard.php # Employee self-service
├── employees.php          # Employee directory
├── projects.php           # Project & budget management
├── holidays.php           # Holiday calendar
├── attendance.php         # Personal attendance tracking
├── team_attendance.php    # Managerial attendance reports
├── css/                   # Modern stylesheets
└── js/                    # Interactive scripts
```

---

**Version:** 1.1 (Consolidated)  
**Environment:** XAMPP / PHP 7.4+ / MySQL 5.7+
