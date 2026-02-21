<?php
/**
 * Employee Dashboard
 * Requirement: Employee List & Profile Management (Employee view)
 * Purpose: Complete employee dashboard with profile, stats, and quick actions
 */

session_start();
require_once 'config.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$employee_name = $_SESSION['employee_name'];
$employee_email = $_SESSION['employee_email'];
$employee_id = $_SESSION['employee_id'];

// Fetch complete employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

// Get attendance statistics for current month
$current_month = date('Y-m');
$attendance_stats = [
    'days_present' => 0,
    'total_hours' => 0,
    'overtime_hours' => 0,
    'required_hours' => $employee['required_hours'] ?? 8.00
];

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as days_present,
        SUM(total_hours) as total_hours,
        SUM(overtime_hours) as overtime_hours
    FROM attendance 
    WHERE employee_id = ? 
    AND DATE_FORMAT(date, '%Y-%m') = ?
    AND status = 'present'
");
$stmt->bind_param("is", $employee_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $attendance_stats['days_present'] = $row['days_present'] ?? 0;
    $attendance_stats['total_hours'] = $row['total_hours'] ?? 0;
    $attendance_stats['overtime_hours'] = $row['overtime_hours'] ?? 0;
}
$stmt->close();

// Get today's attendance status
$today = date('Y-m-d');
$today_attendance = null;
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $employee_id, $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $today_attendance = $result->fetch_assoc();
}
$stmt->close();

// Get pending leave requests
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM leave_requests WHERE employee_id = ? AND status = 'pending'");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_leaves = $result->fetch_assoc()['pending'] ?? 0;
$stmt->close();

// Get upcoming holidays (next 3)
$upcoming_holidays = [];
$result = $conn->query("
    SELECT * FROM holidays 
    WHERE holiday_date >= CURDATE() 
    AND status = 'active'
    ORDER BY holiday_date ASC 
    LIMIT 3
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $upcoming_holidays[] = $row;
    }
}

// Get assigned projects
$active_projects_list = [];
$projects_result = $conn->query("
    SELECT p.*, pa.role as project_role, pa.allocation_percentage
    FROM projects p
    JOIN project_assignments pa ON p.id = pa.project_id
    WHERE pa.employee_id = $employee_id 
    AND pa.status = 'active'
    ORDER BY p.start_date DESC
");
if ($projects_result) {
    while ($row = $projects_result->fetch_assoc()) {
        $active_projects_list[] = $row;
    }
}
$assigned_projects = count($active_projects_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #0078D4 0%, #0053A0 100%);
            color: white;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
        }
        .welcome-text p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card-small {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #0078D4;
        }
        .stat-card-small.green { border-left-color: #10b981; }
        .stat-card-small.orange { border-left-color: #f59e0b; }
        .stat-card-small.purple { border-left-color: #8b5cf6; }
        .stat-value-large {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .stat-label-small {
            font-size: 14px;
            color: #6b7280;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            color: #1f2937;
            font-weight: 500;
            font-size: 14px;
        }
        .holiday-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .holiday-date {
            font-weight: 600;
            color: #0078D4;
            font-size: 14px;
        }
        .holiday-name {
            font-size: 14px;
            color: #1f2937;
        }
        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.2s;
        }
        .quick-action-btn:hover {
            background: #f3f4f6;
            border-color: #0078D4;
            transform: translateY(-2px);
        }
        .quick-action-icon {
            width: 40px;
            height: 40px;
            background: #0078D4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        .quick-action-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }
        .quick-action-text {
            font-size: 14px;
            font-weight: 500;
        }
        .attendance-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .attendance-status-badge.clocked-in {
            background: #d1fae5;
            color: #065f46;
        }
        .attendance-status-badge.not-clocked {
            background: #fee2e2;
            color: #991b1b;
        }
        .attendance-status-badge.completed {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
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
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                    </svg>
                    <span class="nav-text">My Attendance</span>
                </a>
                <a href="holidays.php" class="nav-item <?php echo $current_page == 'holidays.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                    <span class="nav-text">Holiday List</span>
                </a>
                <a href="leave_requests.php" class="nav-item <?php echo $current_page == 'leave_requests.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                    </svg>
                    <span class="nav-text">Leave Requests</span>
                </a>
                <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                    <span class="nav-text">Account Settings</span>
                </a>
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
                    <h1 class="page-title">Employee Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($employee_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($employee_name); ?></div>
                                <div class="user-role">Employee</div>
                            </div>
                            <svg class="chevron" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="profile.php" class="dropdown-item">Profile</a>
                            <a href="settings.php" class="dropdown-item">Settings</a>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h2>Welcome back, <?php echo htmlspecialchars($employee['first_name']); ?>!</h2>
                        <p><?php echo date('l, F j, Y'); ?> • <?php echo htmlspecialchars($employee['department'] ?? 'Employee'); ?></p>
                    </div>
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card-small">
                        <div class="stat-value-large"><?php echo $attendance_stats['days_present']; ?></div>
                        <div class="stat-label-small">Days Present (This Month)</div>
                    </div>
                    <div class="stat-card-small green">
                        <div class="stat-value-large"><?php echo number_format($attendance_stats['total_hours'], 1); ?>h</div>
                        <div class="stat-label-small">Total Hours Worked</div>
                    </div>
                    <div class="stat-card-small orange">
                        <div class="stat-value-large"><?php echo $pending_leaves; ?></div>
                        <div class="stat-label-small">Pending Leave Requests</div>
                    </div>
                    <div class="stat-card-small purple">
                        <div class="stat-value-large"><?php echo $assigned_projects; ?></div>
                        <div class="stat-label-small">Active Projects</div>
                    </div>
                </div>

                <!-- Today's Attendance Status -->
                <?php if ($today_attendance): ?>
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-body" style="padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Today's Attendance:</strong>
                                    <?php if ($today_attendance['clock_out_time']): ?>
                                        <span class="attendance-status-badge completed">
                                            ✓ Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="attendance-status-badge clocked-in">
                                            ● Clocked In
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: right; font-size: 14px; color: #6b7280;">
                                    <div>Clock In: <strong><?php echo date('g:i A', strtotime($today_attendance['clock_in_time'])); ?></strong></div>
                                    <?php if ($today_attendance['clock_out_time']): ?>
                                        <div>Clock Out: <strong><?php echo date('g:i A', strtotime($today_attendance['clock_out_time'])); ?></strong></div>
                                        <div>Total: <strong><?php echo number_format($today_attendance['total_hours'], 2); ?> hours</strong></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-body" style="padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Today's Attendance:</strong>
                                    <span class="attendance-status-badge not-clocked">
                                        ✕ Not Clocked In
                                    </span>
                                </div>
                                <a href="attendance.php" class="btn btn-primary" style="text-decoration: none;">Clock In Now</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Profile Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Employee ID</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['first_name'] . ' ' . ($employee['last_name'] ?? '')); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Department</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Designation</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['designation'] ?? $employee['job_title'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Employment Type</span>
                                <span class="info-value"><?php echo htmlspecialchars($employee['employment_type'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date of Joining</span>
                                <span class="info-value"><?php echo $employee['date_of_joining'] ? date('M d, Y', strtotime($employee['date_of_joining'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Holidays Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Upcoming Holidays</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($upcoming_holidays) > 0): ?>
                                <?php foreach ($upcoming_holidays as $holiday): ?>
                                    <div class="holiday-item">
                                        <div>
                                            <div class="holiday-date">
                                                <?php echo date('M d, Y', strtotime($holiday['holiday_date'])); ?>
                                            </div>
                                            <div class="holiday-name"><?php echo htmlspecialchars($holiday['holiday_name']); ?></div>
                                        </div>
                                        <span style="font-size: 12px; color: #6b7280;">
                                            <?php echo htmlspecialchars($holiday['day_of_week']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <a href="holidays.php" style="display: block; text-align: center; margin-top: 12px; color: #0078D4; text-decoration: none; font-size: 14px;">
                                    View All Holidays →
                                </a>
                            <?php else: ?>
                                <p style="text-align: center; color: #6b7280; padding: 20px;">No upcoming holidays</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- My Active Projects Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">My Active Projects</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($assigned_projects > 0): ?>
                                <?php foreach ($active_projects_list as $proj): ?>
                                    <div class="holiday-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                                        <div style="display: flex; justify-content: space-between; width: 100%;">
                                            <div class="holiday-name" style="font-weight: 600;"><?php echo htmlspecialchars($proj['project_name']); ?></div>
                                            <span class="attendance-status-badge <?php echo strtolower($proj['status']) == 'active' ? 'clocked-in' : 'completed'; ?>" style="font-size: 10px; padding: 2px 8px;">
                                                <?php echo htmlspecialchars($proj['status']); ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 12px; color: #6b7280;">
                                            Role: <strong><?php echo htmlspecialchars($proj['project_role']); ?></strong> 
                                            <span style="margin-left:8px;">Allocation: <strong><?php echo $proj['allocation_percentage']; ?>%</strong></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: #6b7280; padding: 20px;">No active projects assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-action-grid">
                            <a href="attendance.php" class="quick-action-btn">
                                <div class="quick-action-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                    </svg>
                                </div>
                                <span class="quick-action-text">Clock In/Out</span>
                            </a>
                            <a href="attendance.php" class="quick-action-btn">
                                <div class="quick-action-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                    </svg>
                                </div>
                                <span class="quick-action-text">View Attendance</span>
                            </a>
                            <a href="leave_requests.php" class="quick-action-btn">
                                <div class="quick-action-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/>
                                    </svg>
                                </div>
                                <span class="quick-action-text">Request Leave</span>
                            </a>
                            <a href="profile.php" class="quick-action-btn">
                                <div class="quick-action-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                    </svg>
                                </div>
                                <span class="quick-action-text">View Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
