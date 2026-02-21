<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: login.php');
    exit();
}

// Set timezone (you can make this dynamic based on employee's timezone from database)
date_default_timezone_set('Asia/Kolkata');

$employee_name = $_SESSION['employee_name'];
$employee_id = $_SESSION['employee_id'];
$user_type = $_SESSION['user_type'];
$success = '';
$error = '';

// Get employee shift details
$emp_stmt = $conn->prepare("SELECT shift_type, required_hours FROM employees WHERE id = ?");
$emp_stmt->bind_param("i", $employee_id);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$emp_data = $emp_result->fetch_assoc();
$shift_type = $emp_data['shift_type'] ?? 'fixed';
$required_hours = $emp_data['required_hours'] ?? 8.00;
$emp_stmt->close();

// Get today's date
$today = date('Y-m-d');

// Handle Clock IN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clock_in') {
    // Check if already clocked in today
    $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
    $check_stmt->bind_param("is", $employee_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = 'You have already clocked in today.';
    } else {
        $clock_in_time = date('H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, clock_in, status) VALUES (?, ?, ?, 'present')");
        $stmt->bind_param("iss", $employee_id, $today, $clock_in_time);
        
        if ($stmt->execute()) {
            $success = 'Clocked in successfully at ' . date('h:i A', strtotime($clock_in_time));
        } else {
            $error = 'Error clocking in. Please try again.';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Handle Clock OUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clock_out') {
    $clock_out_time = date('H:i:s');
    
    // Get today's attendance record
    $stmt = $conn->prepare("SELECT id, clock_in FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();
        $clock_in = $attendance['clock_in'];
        
        // Calculate total hours
        $clock_in_timestamp = strtotime($clock_in);
        $clock_out_timestamp = strtotime($clock_out_time);
        $total_seconds = $clock_out_timestamp - $clock_in_timestamp;
        $total_hours = $total_seconds / 3600;
        
        // Calculate overtime based on employee's required hours
        $overtime_hours = max(0, $total_hours - $required_hours);
        
        // Update attendance
        $update_stmt = $conn->prepare("UPDATE attendance SET clock_out = ?, total_hours = ?, overtime_hours = ? WHERE id = ?");
        $update_stmt->bind_param("sddi", $clock_out_time, $total_hours, $overtime_hours, $attendance['id']);
        
        if ($update_stmt->execute()) {
            $success = 'Clocked out successfully at ' . date('h:i A', strtotime($clock_out_time)) . '. Total hours: ' . number_format($total_hours, 2);
        } else {
            $error = 'Error clocking out.';
        }
        $update_stmt->close();
    } else {
        $error = 'Please clock in first.';
    }
    $stmt->close();
}

// Get today's attendance
$today_attendance = null;
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $employee_id, $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $today_attendance = $result->fetch_assoc();
}
$stmt->close();

// Get this month's attendance records
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ? ORDER BY date DESC");
$stmt->bind_param("is", $employee_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = [];
while ($row = $result->fetch_assoc()) {
    $attendance_records[] = $row;
}
$stmt->close();

// Calculate monthly stats
$total_days_present = 0;
$total_working_hours = 0;
$total_ot_hours = 0;

foreach ($attendance_records as $record) {
    if ($record['status'] === 'present') {
        $total_days_present++;
        $total_working_hours += $record['total_hours'];
        $total_ot_hours += $record['overtime_hours'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/attendance.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="64" height="64" rx="12" fill="url(#gradient)"/>
                        <path d="M20 24h24M20 32h24M20 40h16" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="gradient" x1="0" y1="0" x2="64" y2="64">
                                <stop offset="0%" stop-color="#0078D4"/>
                                <stop offset="100%" stop-color="#0053A0"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h1 class="sidebar-title">HRMS</h1>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
            </div>
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <nav class="sidebar-nav">
                <?php if ($user_type === 'admin'): ?>
                    <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employees.php" class="nav-item <?php echo in_array($current_page, ['employees.php', 'add_employee.php', 'edit_employee.php', 'employee_profile.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span class="nav-text">Employees</span>
                    </a>
                    <a href="managers.php" class="nav-item <?php echo in_array($current_page, ['managers.php', 'add_manager.php', 'edit_manager.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <span class="nav-text">Managers</span>
                    </a>
                    <a href="team_attendance.php" class="nav-item <?php echo $current_page == 'team_attendance.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <span class="nav-text">Attendance</span>
                    </a>
                    <a href="holidays.php" class="nav-item <?php echo $current_page == 'holidays.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <span class="nav-text">Holidays</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item <?php echo $current_page == 'leave_requests.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="manage_leaves.php" class="nav-item <?php echo $current_page == 'manage_leaves.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        <span class="nav-text">Approve Leaves</span>
                    </a>
                    <a href="manage_overtime.php" class="nav-item <?php echo $current_page == 'manage_overtime.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                        <span class="nav-text">Approve OT</span>
                    </a>
                    <a href="projects.php" class="nav-item <?php echo in_array($current_page, ['projects.php', 'add_project.php', 'project_details.php', 'assign_project.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        <span class="nav-text">Projects</span>
                    </a>
                    <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="nav-text">Reports</span>
                    </a>
                    <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Profile</span>
                    </a>
                    <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Account Settings</span>
                    </a>
                    <a href="dropdown_management.php" class="nav-item <?php echo $current_page == 'dropdown_management.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span class="nav-text">Master Data</span>
                    </a>
                <?php elseif ($user_type === 'manager'): ?>
                    <a href="manager_dashboard.php" class="nav-item <?php echo $current_page == 'manager_dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="team_list.php" class="nav-item <?php echo in_array($current_page, ['team_list.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span class="nav-text">Team Members</span>
                    </a>
                    <a href="holidays.php" class="nav-item <?php echo $current_page == 'holidays.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Holidays</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item <?php echo $current_page == 'leave_requests.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="manage_leaves.php" class="nav-item <?php echo $current_page == 'manage_leaves.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        <span class="nav-text">Approve Leaves</span>
                    </a>
                    <a href="manage_overtime.php" class="nav-item <?php echo $current_page == 'manage_overtime.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                        <span class="nav-text">Approve OT</span>
                    </a>
                    <a href="team_attendance.php" class="nav-item <?php echo $current_page == 'team_attendance.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                        <span class="nav-text">Attendance</span>
                    </a>
                    <a href="projects.php" class="nav-item <?php echo in_array($current_page, ['projects.php', 'project_details.php']) ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <span class="nav-text">Projects</span>
                    </a>
                    <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Profile</span>
                    </a>
                    <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Account Settings</span>
                    </a>
                <?php else: ?>
                    <a href="employee_dashboard.php" class="nav-item <?php echo $current_page == 'employee_dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">My Profile</span>
                    </a>
                    <a href="attendance.php" class="nav-item <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/></svg>
                        <span class="nav-text">My Attendance</span>
                    </a>
                    <a href="holidays.php" class="nav-item <?php echo $current_page == 'holidays.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Holiday List</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item <?php echo $current_page == 'leave_requests.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Account Settings</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item logout">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z"/>
                    </svg>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                        </svg>
                    </button>
                    <h1 class="page-title">My Attendance</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($employee_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($employee_name); ?></div>
                                <div class="user-role">Employee</div>
                            </div>
                        </button>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <!-- Clock IN/OUT Section -->
                <div class="clock-section">
                    <div class="clock-card">
                        <div class="current-time" id="currentTime"></div>
                        <div class="current-date" id="currentDate"><?php echo date('l, F j, Y'); ?></div>
                        
                        <?php if ($today_attendance): ?>
                            <div class="attendance-status">
                                <?php if ($today_attendance['clock_in'] && !$today_attendance['clock_out']): ?>
                                    <div class="status-badge clocked-in">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                        </svg>
                                        Clocked In
                                    </div>
                                    <div class="clock-time">
                                        Clock In: <?php echo date('h:i A', strtotime($today_attendance['clock_in'])); ?>
                                    </div>
                                    <form method="POST" class="clock-form">
                                        <input type="hidden" name="action" value="clock_out">
                                        <button type="submit" class="btn-clock btn-clock-out">
                                            <svg viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                            </svg>
                                            Clock Out
                                        </button>
                                    </form>
                                <?php elseif ($today_attendance['clock_in'] && $today_attendance['clock_out']): ?>
                                    <div class="status-badge completed">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                        </svg>
                                        Completed
                                    </div>
                                    <div class="clock-times">
                                        <div>Clock In: <?php echo date('h:i A', strtotime($today_attendance['clock_in'])); ?></div>
                                        <div>Clock Out: <?php echo date('h:i A', strtotime($today_attendance['clock_out'])); ?></div>
                                        <div class="total-hours">Total: <?php echo number_format($today_attendance['total_hours'], 2); ?> hrs</div>
                                        <?php if ($today_attendance['overtime_hours'] > 0): ?>
                                        <div class="ot-hours">OT: <?php echo number_format($today_attendance['overtime_hours'], 2); ?> hrs</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="attendance-status">
                                <div class="status-badge not-clocked">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                    </svg>
                                    Not Clocked In
                                </div>
                                <form method="POST" class="clock-form">
                                    <input type="hidden" name="action" value="clock_in">
                                    <button type="submit" class="btn-clock btn-clock-in">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                        </svg>
                                        Clock In
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Monthly Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_days_present; ?></div>
                            <div class="stat-label">Days Present</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($total_working_hours, 1); ?></div>
                            <div class="stat-label">Working Hours</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($required_hours, 0); ?></div>
                            <div class="stat-label">Required Hours/Day</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($total_ot_hours, 1); ?></div>
                            <div class="stat-label">Overtime Hours</div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Attendance History - <?php echo date('F Y'); ?></h3>
                        <button class="btn-regularize" onclick="window.location.href='regularization.php'">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            Request Regularization
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="attendance-table-container">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Total Hours</th>
                                        <th>OT Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($attendance_records) > 0): ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('D, M d, Y', strtotime($record['date'])); ?></td>
                                            <td><?php echo $record['clock_in'] ? date('h:i A', strtotime($record['clock_in'])) : '-'; ?></td>
                                            <td><?php echo $record['clock_out'] ? date('h:i A', strtotime($record['clock_out'])) : '-'; ?></td>
                                            <td><?php echo number_format($record['total_hours'], 2); ?> hrs</td>
                                            <td>
                                                <?php if ($record['overtime_hours'] > 0): ?>
                                                    <span class="ot-badge"><?php echo number_format($record['overtime_hours'], 2); ?> hrs</span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="no-data">No attendance records for this month</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script src="js/attendance.js"></script>
</body>
</html>
