<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'manager')) {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$viewer_name = $_SESSION[$user_type . '_name'];

if (!isset($_GET['id'])) {
    header('Location: projects.php');
    exit();
}

$project_id = intval($_GET['id']);

// Fetch Project Details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Handle Expense Deletion (Admin only)
if (isset($_GET['delete_expense']) && $user_type === 'admin') {
    $del_stmt = $conn->prepare("DELETE FROM project_expenses WHERE id = ? AND project_id = ?");
    $del_stmt->bind_param("ii", $_GET['delete_expense'], $project_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: project_details.php?id=$project_id&expense_deleted=1");
    exit();
}

// Handle Assignment Deletion (Admin only)
if (isset($_GET['remove_member']) && $user_type === 'admin') {
    $del_stmt = $conn->prepare("DELETE FROM project_assignments WHERE id = ? AND project_id = ?");
    $del_stmt->bind_param("ii", $_GET['remove_member'], $project_id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: project_details.php?id=$project_id&member_removed=1");
    exit();
}

// Fetch Assigned Employees
$team = [];
$team_stmt = $conn->prepare("
    SELECT pa.*, e.first_name, e.last_name, e.designation, e.department
    FROM project_assignments pa
    JOIN employees e ON pa.employee_id = e.id
    WHERE pa.project_id = ?
    ORDER BY pa.start_date DESC
");
$team_stmt->bind_param("i", $project_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
while ($row = $team_result->fetch_assoc()) {
    $team[] = $row;
}
$team_stmt->close();

// Fetch Expenses
$expenses = [];
$total_expense = 0;
$exp_stmt = $conn->prepare("
    SELECT *
    FROM project_expenses
    WHERE project_id = ?
    ORDER BY expense_date DESC
");
$exp_stmt->bind_param("i", $project_id);
$exp_stmt->execute();
$exp_result = $exp_stmt->get_result();
while ($row = $exp_result->fetch_assoc()) {
    $expenses[] = $row;
    $total_expense += floatval($row['amount']);
}
$exp_stmt->close();

$budget = floatval($project['budget_amount'] ?? 0);
$remaining = $budget - $total_expense;
$percentage = $budget > 0 ? ($total_expense / $budget) * 100 : 0;
$progressClass = $percentage > 90 ? 'danger' : ($percentage > 75 ? 'warning' : '');
$statusClass = strtolower(str_replace(' ', '-', $project['status']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <style>
        .page-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .header-left-title h2 {
            margin: 0 0 4px 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 600;
        }
        .header-left-title p {
            margin: 0;
            color: #6b7280;
            font-family: monospace;
            font-size: 14px;
        }
        
        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) {
            .grid-2-col {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-header h3 {
            margin: 0;
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-list li:last-child {
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

        .budget-card {
            background: linear-gradient(135deg, #0078D4 0%, #0053A0 100%);
            color: white;
            border-radius: 12px;
            padding: 32px 24px;
            box-shadow: 0 4px 6px rgba(0, 120, 212, 0.2);
        }
        .budget-amount {
            font-size: 36px;
            font-weight: 700;
            margin: 8px 0 24px 0;
        }
        .budget-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            opacity: 0.9;
        }
        .budget-progress-container {
            height: 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .budget-progress-fill {
            height: 100%;
            background: white;
            transition: width 0.5s ease-out;
        }
        .budget-progress-fill.warning { background: #fcd34d; }
        .budget-progress-fill.danger { background: #fca5a5; }
        
        .section-mt { margin-top: 32px; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        .status-badge.planning { background: #e0f2fe; color: #0369a1; }
        .status-badge.active { background: #dcfce7; color: #15803d; }
        .status-badge.on-hold { background: #ffedd5; color: #c2410c; }
        .status-badge.completed { background: #f3e8ff; color: #7e22ce; }
        .status-badge.cancelled { background: #fee2e2; color: #b91c1c; }

        .empty-text {
            color: #6b7280;
            text-align: center;
            padding: 16px;
            font-size: 14px;
            font-style: italic;
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
                        <defs><linearGradient id="gradient" x1="0" y1="0" x2="64" y2="64"><stop offset="0%" stop-color="#0078D4"/><stop offset="100%" stop-color="#0053A0"/></linearGradient></defs>
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
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                        <span class="nav-text">Leave Requests</span>
                    </a>
                    <a href="projects.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                        <span class="nav-text">Projects</span>
                    </a>
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/>
                        </svg>
                        <span class="nav-text">Payroll</span>
                    </a>
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        <span class="nav-text">Reports</span>
                    </a>
                    <a href="dropdown_management.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        <span class="nav-text">Settings</span>
                    </a>
                <?php elseif ($user_type === 'manager'): ?>
                    <a href="manager_dashboard.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg><span class="nav-text">Dashboard</span></a>
                    <a href="#" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg><span class="nav-text">Team Members</span></a>
                    <a href="projects.php" class="nav-item active"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg><span class="nav-text">Projects</span></a>
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
                    <a href="projects.php" style="color: #6b7280; text-decoration: none; margin-right: 8px; display:flex; align-items:center;">
                         <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px; height:16px; margin-right:4px;"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"/></svg>
                         Back
                    </a>
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
                <div class="page-header-actions">
                    <div class="header-left-title">
                        <h2><?php echo htmlspecialchars($project['project_name']); ?></h2>
                        <p><?php echo htmlspecialchars($project['project_code']); ?></p>
                    </div>
                    <div>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                        <?php if ($user_type === 'admin'): ?>
                        <a href="edit_project.php?id=<?php echo $project_id; ?>" class="btn-secondary" style="margin-left:8px; text-decoration:none;">
                            Edit Project
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid-2-col">
                    <!-- Basic Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Project Details</h3>
                        </div>
                        <ul class="info-list">
                            <li><span class="info-label">Start Date</span> <span class="info-value"><?php echo date('M d, Y', strtotime($project['start_date'])); ?></span></li>
                            <li><span class="info-label">End Date</span> <span class="info-value"><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '-'; ?></span></li>
                            <li><span class="info-label">Description</span></li>
                            <li><span class="info-value" style="font-weight: 400;"><?php echo nl2br(htmlspecialchars($project['description'] ?? 'No description provided.')); ?></span></li>
                        </ul>
                    </div>

                    <!-- Budget Overview -->
                    <div class="budget-card">
                        <div style="font-size: 14px; opacity: 0.9;">Total Budget Allocated</div>
                        <div class="budget-amount">₹<?php echo number_format($budget, 2); ?></div>
                        
                        <div class="budget-stats">
                            <span>Utilized: ₹<?php echo number_format($total_expense, 2); ?></span>
                            <span>Remaining: ₹<?php echo number_format($remaining, 2); ?></span>
                        </div>
                        
                        <div class="budget-progress-container">
                            <div class="budget-progress-fill <?php echo $progressClass; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; opacity: 0.8;">
                            <span>0%</span>
                            <span><?php echo number_format($percentage, 1); ?>% Used</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-2-col section-mt">
                    <!-- Team Members -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Team Members (<?php echo count($team); ?>)</h3>
                            <?php if ($user_type === 'admin'): ?>
                            <a href="assign_project.php?id=<?php echo $project_id; ?>" class="btn-primary btn-sm" style="text-decoration:none;">+ Assign Member</a>
                            <?php endif; ?>
                        </div>
                        <?php if (count($team) > 0): ?>
                        <table class="employees-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th>Alloc.</th>
                                    <?php if ($user_type === 'admin') echo "<th>Actions</th>"; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($team as $member): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:500; color:#1f2937;"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                                        <div style="font-size:12px; color:#6b7280;"><?php echo htmlspecialchars($member['department']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['role'] ?? '-'); ?></td>
                                    <td><?php echo floatval($member['allocation_percentage']); ?>%</td>
                                    <?php if ($user_type === 'admin'): ?>
                                    <td>
                                        <a href="project_details.php?id=<?php echo $project_id; ?>&remove_member=<?php echo $member['id']; ?>" class="btn-icon" style="color:#ef4444;" onclick="return confirm('Remove this member?');">
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <div class="empty-text">No members assigned to this project yet.</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Expenses -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Expenses</h3>
                            <?php if ($user_type === 'admin'): ?>
                            <a href="add_expense.php?project_id=<?php echo $project_id; ?>" class="btn-primary btn-sm" style="text-decoration:none;">+ Add Expense</a>
                            <?php endif; ?>
                        </div>
                        <?php if (count($expenses) > 0): ?>
                        <table class="employees-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <?php if ($user_type === 'admin') echo "<th>Actions</th>"; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($expenses as $exp): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($exp['expense_type'] ?? '-'); ?></td>
                                    <td style="font-weight:500;">₹<?php echo number_format($exp['amount'], 2); ?></td>
                                    <?php if ($user_type === 'admin'): ?>
                                    <td>
                                        <a href="project_details.php?id=<?php echo $project_id; ?>&delete_expense=<?php echo $exp['id']; ?>" class="btn-icon" style="color:#ef4444;" onclick="return confirm('Delete this expense?');">
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"/></svg>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <div class="empty-text">No expenses recorded for this project yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
    </script>
</body>
</html>
