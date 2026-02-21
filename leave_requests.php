<?php
/**
 * Leave Requests Page
 * Requirement: Leave Request module
 * Purpose: Employees can submit leaves; All roles can view their history
 */

session_start();
require_once 'config.php';
require_once 'includes/dropdown_helper.php';

// Check if logged in
if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$viewer_name = $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? $_SESSION['employee_name'];
$viewer_id = $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['employee_id'];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Approval Actions (Admins and Managers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($_POST['submit_leave'])) {
    if ($user_type === 'admin' || $user_type === 'manager') {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action']; // 'approved' or 'rejected'
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        // Security check: If manager, ensure the request belongs to their department
        $can_proceed = true;
        if ($user_type === 'manager') {
            $stmt = $conn->prepare("SELECT e.department FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $dept_res = $stmt->get_result()->fetch_assoc();
            if (!$dept_res || $dept_res['department'] !== $_SESSION['manager_department']) {
                $can_proceed = false;
                $error = "You do not have permission to manage this request.";
            }
            $stmt->close();
        }
        
        if ($can_proceed) {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approved_by = ?, approved_at = CURRENT_TIMESTAMP, rejection_reason = ? WHERE id = ?");
            $stmt->bind_param("sisi", $action, $viewer_id, $rejection_reason, $request_id);
            
            if ($stmt->execute()) {
                $msg = "Leave request " . ($action === 'approved' ? 'approved' : 'rejected') . " successfully!";
                header("Location: leave_requests.php?success=" . urlencode($msg));
                exit();
            } else {
                $error = "Error updating request: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle Submit Request (Employees only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    if ($user_type !== 'employee') {
        $error = "Only employees can submit leave requests.";
    } else {
        $leave_type = $_POST['leave_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = trim($_POST['reason']);
        
        // Simple day calculation (exclusive of weekends could be added later)
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $leave_days = $interval->days + 1; // +1 to include both start and end date
        
        if ($start > $end) {
            $error = "Start date cannot be after end date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, leave_days, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("isssds", $viewer_id, $leave_type, $start_date, $end_date, $leave_days, $reason);
            
            if ($stmt->execute()) {
                $success = "Leave request submitted successfully!";
            } else {
                $error = "Error submitting request: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch requests based on role
$requests = [];
if ($user_type === 'admin') {
    $sql = "SELECT lr.*, e.first_name, e.last_name, e.department 
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.id 
            ORDER BY lr.created_at DESC";
    $result = $conn->query($sql);
} elseif ($user_type === 'manager') {
    $dept = $_SESSION['manager_department'];
    $sql = "SELECT lr.*, e.first_name, e.last_name, e.department 
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.id 
            WHERE e.department = '$dept'
            ORDER BY lr.created_at DESC";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT lr.*, 'You' as first_name, '' as last_name, '' as department 
            FROM leave_requests lr 
            WHERE employee_id = $viewer_id 
            ORDER BY lr.created_at DESC";
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Get notification count
$count_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'active'");
$notification_count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <link rel="stylesheet" href="css/add_employee.css">
    <style>
        .leave-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <?php if ($user_type === 'admin'): ?>
                    <a href="dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employees.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                        <span class="nav-text">Employees</span>
                    </a>
                    <a href="managers.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                        <span class="nav-text">Managers</span>
                    </a>
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Attendance</span>
                    </a>
                    <a href="holidays.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Holidays</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="projects.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <span class="nav-text">Projects</span>
                    </a>
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/></svg>
                        <span class="nav-text">Payroll</span>
                    </a>
                    <a href="dropdown_management.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        <span class="nav-text">Settings</span>
                    </a>
                <?php elseif ($user_type === 'manager'): ?>
                    <a href="manager_dashboard.php" class="nav-item">
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
                    <a href="holidays.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Holidays</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="team_attendance.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                        <span class="nav-text">Attendance</span>
                    </a>
                    <a href="projects.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        <span class="nav-text">Projects</span>
                    </a>
                <?php else: ?>
                    <a href="employee_dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="attendance.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">My Attendance</span>
                    </a>
                    <a href="holidays.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Holidays</span>
                    </a>
                    <a href="leave_requests.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="my_projects.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <span class="nav-text">My Projects</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item logout"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z"/></svg><span class="nav-text">Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                    </button>
                    <h1 class="page-title">Leave Requests</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($viewer_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($viewer_name); ?></div>
                                <div class="user-role"><?php echo ucfirst($user_type); ?></div>
                            </div>
                        </button>
                    </div>
                </div>
            </header>

            <div class="content">
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

                <div class="holiday-header">
                    <h2 style="font-size: 18px; color: #374151;">My Leave History</h2>
                    <?php if ($user_type === 'employee'): ?>
                    <button class="btn btn-primary" onclick="openModal()">Request Leave</button>
                    <?php endif; ?>
                    <?php if ($user_type === 'admin' || $user_type === 'manager'): ?>
                    <a href="manage_leaves.php" class="btn btn-secondary" style="text-decoration:none;">Manage Approvals</a>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="employees-table">
                            <thead>
                                <tr>
                                    <?php if ($user_type !== 'employee') echo "<th>Employee</th>"; ?>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <?php if ($user_type !== 'employee'): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr><td colspan="7" style="text-align:center; padding: 20px;">No leave requests found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <?php if ($user_type !== 'employee'): ?>
                                        <td>
                                            <div style="font-weight:500;"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></div>
                                            <div style="font-size:12px; color:#6b7280;"><?php echo htmlspecialchars($req['department']); ?></div>
                                        </td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($req['leave_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($req['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($req['end_date'])); ?></td>
                                        <td><?php echo floatval($req['leave_days']); ?></td>
                                        <td><span class="leave-status status-<?php echo $req['status']; ?>"><?php echo $req['status']; ?></span></td>
                                        <td title="<?php echo htmlspecialchars($req['reason']); ?>"><?php echo substr(htmlspecialchars($req['reason']), 0, 30) . (strlen($req['reason']) > 30 ? '...' : ''); ?></td>
                                        <?php if ($user_type !== 'employee'): ?>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <div style="display:flex; gap:8px;">
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                        <input type="hidden" name="action" value="approved">
                                                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;">Approve</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; border-color:#ef4444; color:#ef4444;" onclick="openRejectModal(<?php echo $req['id']; ?>)">Reject</button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#6b7280; font-size:12px;">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Request Modal -->
    <div class="modal" id="leaveModal">
        <div class="modal-content">
            <h3 class="step-title">Request New Leave</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Leave Type <span class="required">*</span></label>
                    <select name="leave_type" class="form-input" required>
                        <?php echo renderDropdownOptions($conn, 'leave_type', '', true); ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex; gap:12px;">
                    <div style="flex:1;">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" name="start_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label">End Date <span class="required">*</span></label>
                        <input type="date" name="end_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason <span class="required">*</span></label>
                    <textarea name="reason" class="form-input" rows="3" required placeholder="Brief reason for leave"></textarea>
                </div>
                <div style="display:flex; gap:12px; margin-top:24px;">
                    <button type="submit" name="submit_leave" class="btn btn-primary" style="flex:1;">Submit Request</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h3 class="step-title">Reject Leave Request</h3>
            <p style="font-size:14px; color:#6b7280; margin-bottom: 20px;">Please provide a reason for rejection.</p>
            <form method="POST" action="">
                <input type="hidden" name="request_id" id="reject_id">
                <input type="hidden" name="action" value="rejected">
                <div class="form-group">
                    <textarea name="rejection_reason" class="form-input" rows="4" required placeholder="e.g., Department understaffed during these dates."></textarea>
                </div>
                <div style="display:flex; gap:12px; margin-top:24px;">
                    <button type="submit" class="btn btn-primary" style="flex:1; background:#ef4444; border-color:#ef4444;">Confirm Reject</button>
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script>
        function openModal() { document.getElementById('leaveModal').classList.add('active'); }
        function closeModal() { document.getElementById('leaveModal').classList.remove('active'); }
        
        function openRejectModal(id) { 
            document.getElementById('reject_id').value = id;
            document.getElementById('rejectModal').classList.add('active'); 
        }
        function closeRejectModal() { document.getElementById('rejectModal').classList.remove('active'); }
    </script>
</body>
</html>
