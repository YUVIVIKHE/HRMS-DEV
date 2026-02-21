<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['manager_id']) || $_SESSION['user_type'] !== 'manager') {
    header('Location: login.php');
    exit();
}

$manager_name = $_SESSION['manager_name'];
$manager_department = $_SESSION['manager_department'];
$manager_id = $_SESSION['manager_id'];

// Get department employees count
$dept_stmt = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE department = ? AND status = 'active'");
$dept_stmt->bind_param("s", $manager_department);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$dept_employee_count = $dept_result->fetch_assoc()['count'];
$dept_stmt->close();

// Get pending leave requests count for department
$leave_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    WHERE lr.status = 'pending' AND e.department = ?
");
$leave_stmt->bind_param("s", $manager_department);
$leave_stmt->execute();
$leave_count = $leave_stmt->get_result()->fetch_assoc()['count'];
$leave_stmt->close();

// Get pending OT requests count for department
$ot_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM overtime_requests orq 
    JOIN employees e ON orq.employee_id = e.id 
    WHERE orq.status = 'pending' AND e.department = ?
");
$ot_stmt->bind_param("s", $manager_department);
$ot_stmt->execute();
$ot_count = $ot_stmt->get_result()->fetch_assoc()['count'];
$ot_stmt->close();

// Get Today's Attendance Stats for Team
$today = date('Y-m-d');
$presence_stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN lr.id IS NOT NULL THEN 1 END) as on_leave
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
    LEFT JOIN leave_requests lr ON e.id = lr.employee_id 
        AND lr.status = 'approved' 
        AND ? BETWEEN lr.start_date AND lr.end_date
    WHERE e.department = ? AND e.status = 'active'
");
$presence_stmt->bind_param("sss", $today, $today, $manager_department);
$presence_stmt->execute();
$presence_result = $presence_stmt->get_result()->fetch_assoc();
$team_present = $presence_result['present'];
$team_on_leave = $presence_result['on_leave'];
$presence_stmt->close();

// Get Recent Team Activity
$activities = [];
$activity_stmt = $conn->prepare("
    (SELECT 'attendance' as type, e.first_name, e.last_name, a.clock_in as time, 'clocked in' as action 
     FROM attendance a JOIN employees e ON a.employee_id = e.id 
     WHERE e.department = ? AND a.date = ? ORDER BY a.clock_in DESC LIMIT 5)
    UNION
    (SELECT 'leave' as type, e.first_name, e.last_name, lr.created_at as time, 'requested leave' as action 
     FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id 
     WHERE e.department = ? ORDER BY lr.created_at DESC LIMIT 5)
    ORDER BY time DESC LIMIT 8
");
$activity_stmt->bind_param("ssss", $manager_department, $today, $manager_department);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();
while($row = $activity_result->fetch_assoc()) {
    $activities[] = $row;
}
$activity_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
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
            <nav class="sidebar-nav">
                <a href="manager_dashboard.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="team_list.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span class="nav-text">Team Members</span>
                </a>
                <a href="holidays.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Holidays</span></a>
                <a href="leave_requests.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Leave Requests</span></a>
                <a href="team_attendance.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                    </svg>
                    <span class="nav-text">Attendance</span>
                </a>
                <a href="projects.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg><span class="nav-text">Projects</span></a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <span class="nav-text">Reports</span>
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
                    <h1 class="page-title">Manager Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($manager_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($manager_name); ?></div>
                                <div class="user-role">Manager - <?php echo htmlspecialchars($manager_department); ?></div>
                            </div>
                            <svg class="chevron" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="#" class="dropdown-item">Profile</a>
                            <a href="#" class="dropdown-item">Settings</a>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="welcome-card">
                    <h2>Welcome back, <?php echo htmlspecialchars($manager_name); ?>!</h2>
                    <p>Here's what's happening in your department today.</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $dept_employee_count; ?></div>
                            <div class="stat-label">Team Members</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 10a5 5 0 1110 0 5 5 0 01-10 0zm5-3a3 3 0 100 6 3 3 0 000-6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $team_present; ?></div>
                            <div class="stat-label">Present Today</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $team_on_leave; ?></div>
                            <div class="stat-label">On Leave</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $leave_count; ?></div>
                            <div class="stat-label">Pending Leaves</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Team Activity</h3>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <?php if (empty($activities)): ?>
                                    <div class="no-data" style="padding: 20px; text-align: center; color: #666;">
                                        No recent activity to show.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon <?php echo ($activity['type'] == 'attendance' ? 'blue' : 'orange'); ?>">
                                                <svg viewBox="0 0 20 20" fill="currentColor">
                                                    <?php if ($activity['type'] == 'attendance'): ?>
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                                    <?php else: ?>
                                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                                    <?php endif; ?>
                                                </svg>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title">
                                                    <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong> 
                                                    <?php echo $activity['action']; ?>
                                                </div>
                                                <div class="activity-time"><?php echo date('h:i A', strtotime($activity['time'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="team_list.php" class="action-btn">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                    View Team
                                </a>
                                <a href="manage_leaves.php" class="action-btn">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                    </svg>
                                    Approve Leaves
                                </a>
                                <a href="team_attendance.php" class="action-btn">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                    </svg>
                                    Team Attendance
                                </a>
                                <a href="projects.php" class="action-btn">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                    </svg>
                                    Project Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
