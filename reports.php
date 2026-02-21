<?php
/**
 * Reports Page
 * Requirement: Comprehensive Reporting (Admin & Manager)
 * Purpose: Centralized hub for attendance, leave, and project reports.
 */

session_start();
require_once 'config.php';

// Check if logged in and has appropriate role
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'manager')) {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$user_name = $_SESSION['admin_name'] ?? $_SESSION['manager_name'];
$user_dept = $_SESSION['manager_department'] ?? null;

// Filter Parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$current_filter_date = "$year-$month";

// 1. Attendance Summary
$attendance_query = "
    SELECT 
        e.first_name, 
        e.last_name, 
        e.employee_id,
        COUNT(a.id) as days_present,
        SUM(a.total_hours) as total_hours,
        SUM(a.overtime_hours) as total_ot
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND DATE_FORMAT(a.date, '%Y-%m') = ?
    WHERE e.status = 'active'
";
if ($user_type === 'manager') {
    $attendance_query .= " AND e.department = ?";
}
$attendance_query .= " GROUP BY e.id ORDER BY e.first_name ASC";

$stmt = $conn->prepare($attendance_query);
if ($user_type === 'manager') {
    $stmt->bind_param("ss", $current_filter_date, $user_dept);
} else {
    $stmt->bind_param("s", $current_filter_date);
}
$stmt->execute();
$attendance_report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2. Leave Analytics
$leave_query = "
    SELECT 
        lr.leave_type, 
        COUNT(*) as request_count,
        SUM(lr.leave_days) as total_days
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE DATE_FORMAT(lr.start_date, '%Y-%m') = ? AND lr.status = 'approved'
";
if ($user_type === 'manager') {
    $leave_query .= " AND e.department = ?";
}
$leave_query .= " GROUP BY lr.leave_type";

$stmt = $conn->prepare($leave_query);
if ($user_type === 'manager') {
    $stmt->bind_param("ss", $current_filter_date, $user_dept);
} else {
    $stmt->bind_param("s", $current_filter_date);
}
$stmt->execute();
$leave_report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Project Utilization
$project_query = "
    SELECT project_name, budget_amount, utilized_amount, status
    FROM projects
";
if ($user_type === 'manager') {
    // Managers only see projects they are lead on or have team members in
    $project_query = "
        SELECT DISTINCT p.project_name, p.budget_amount, p.utilized_amount, p.status
        FROM projects p
        JOIN project_assignments pa ON p.id = pa.project_id
        JOIN employees e ON pa.employee_id = e.id
        WHERE e.department = ?
    ";
}
$stmt = $conn->prepare($project_query);
if ($user_type === 'manager') {
    $stmt->bind_param("s", $user_dept);
}
$stmt->execute();
$project_report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: flex-end;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .report-table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #f3f4f6;
            border-radius: 4px;
            margin-top: 4px;
        }
        .progress-bar {
            height: 100%;
            background: #0078D4;
            border-radius: 4px;
        }
        .progress-bar.warning { background: #f59e0b; }
        .progress-bar.danger { background: #ef4444; }
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
                <?php if ($user_type === 'admin'): ?>
                    <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employees.php" class="nav-item <?php echo $current_page == 'employees.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                        <span class="nav-text">Employees</span>
                    </a>
                    <a href="managers.php" class="nav-item <?php echo $current_page == 'managers.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        <span class="nav-text">Managers</span>
                    </a>
                <?php else: ?>
                    <a href="manager_dashboard.php" class="nav-item <?php echo $current_page == 'manager_dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="team_list.php" class="nav-item <?php echo $current_page == 'team_list.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                        <span class="nav-text">Team</span>
                    </a>
                <?php endif; ?>

                <a href="projects.php" class="nav-item <?php echo in_array($current_page, ['projects.php', 'add_project.php', 'project_details.php', 'assign_project.php']) ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                    <span class="nav-text">Projects</span>
                </a>
                <a href="attendance.php" class="nav-item <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    <span class="nav-text">Attendance</span>
                </a>
                <a href="manage_leaves.php" class="nav-item <?php echo $current_page == 'manage_leaves.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    <span class="nav-text">Leaves</span>
                </a>
                <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="nav-text">Reports</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item logout">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    </button>
                    <h1 class="page-title">Management Reports</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                                <div class="user-role"><?php echo ucfirst($user_type); ?></div>
                            </div>
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
                <!-- Filters -->
                <form class="filter-section" method="GET">
                    <div class="form-group">
                        <label>Month</label>
                        <select name="month" class="filter-select">
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?php echo sprintf("%02d", $m); ?>" <?php echo $month == sprintf("%02d", $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year" class="filter-select">
                            <?php for($y=date('Y')-2; $y<=date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
                </form>

                <div class="report-grid">
                    <!-- Attendance Report -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Attendance Summary - <?php echo date('F Y', mktime(0,0,0,$month,1,$year)); ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="report-table-container">
                                <table class="employee-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Present</th>
                                            <th>Total Hours</th>
                                            <th>OT Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attendance_report)): ?>
                                            <tr><td colspan="4" style="text-align:center;">No attendance records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($attendance_report as $row): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                                                        <small style="color:#666;"><?php echo $row['employee_id']; ?></small>
                                                    </td>
                                                    <td><?php echo $row['days_present']; ?> Days</td>
                                                    <td><?php echo number_format($row['total_hours'], 1); ?> hr</td>
                                                    <td><?php echo number_format($row['total_ot'], 1); ?> hr</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Analytics -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Leave Distribution</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($leave_report)): ?>
                                <p style="text-align:center; padding: 20px; color:#666;">No leave data for this period.</p>
                            <?php else: ?>
                                <?php foreach ($leave_report as $row): ?>
                                    <div style="margin-bottom:16px;">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                            <span style="font-size:14px;"><?php echo htmlspecialchars($row['leave_type']); ?></span>
                                            <span style="font-weight:600; font-size:14px;"><?php echo $row['total_days']; ?> Days</span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?php echo min(100, ($row['total_days'] / 10) * 100); ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Budget Utilization -->
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header">
                            <h3 class="card-title">Project Budget Utilization</h3>
                        </div>
                        <div class="card-body">
                            <table class="employee-table">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Status</th>
                                        <th>Total Budget</th>
                                        <th>Utilized</th>
                                        <th>Usage %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($project_report)): ?>
                                        <tr><td colspan="5" style="text-align:center;">No projects found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($project_report as $row): 
                                            $percent = $row['budget_amount'] > 0 ? ($row['utilized_amount'] / $row['budget_amount']) * 100 : 0;
                                            $color_class = $percent > 90 ? 'danger' : ($percent > 75 ? 'warning' : '');
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($row['project_name']); ?></strong></td>
                                                <td><span class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>"><?php echo $row['status']; ?></span></td>
                                                <td>$<?php echo number_format($row['budget_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($row['utilized_amount'], 2); ?></td>
                                                <td>
                                                    <div style="display:flex; align-items:center; gap:8px;">
                                                        <div class="progress-bar-container" style="flex:1;">
                                                            <div class="progress-bar <?php echo $color_class; ?>" style="width: <?php echo min(100, $percent); ?>%;"></div>
                                                        </div>
                                                        <span style="font-size:12px; min-width:35px;"><?php echo round($percent); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
</body>
</html>
