<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['manager_id']) || $_SESSION['user_type'] !== 'manager') {
    header('Location: login.php');
    exit();
}

$manager_name = $_SESSION['manager_name'];
$manager_department = $_SESSION['manager_department'];

// Date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search_emp = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Fetch team members for filter
$emp_stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE department = ? ORDER BY first_name ASC");
$emp_stmt->bind_param("s", $manager_department);
$emp_stmt->execute();
$team_members = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$emp_stmt->close();

// Fetch attendance records
$query = "
    SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_code
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE e.department = ? AND a.date BETWEEN ? AND ?
";

if (!empty($search_emp)) {
    $query .= " AND e.id = " . intval($search_emp);
}

$query .= " ORDER BY a.date DESC, a.clock_in DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $manager_department, $start_date, $end_date);
$stmt->execute();
$attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate Summary
$total_present = count($attendance_records);
$total_ot = 0;
foreach($attendance_records as $rec) {
    if($rec['overtime_hours'] > 0) $total_ot += $rec['overtime_hours'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Attendance - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <link rel="stylesheet" href="css/attendance.css">
    <style>
        .filter-panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: flex-end;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn-filter {
            padding: 10px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-filter:hover { background: #1d4ed8; }
        .summary-mini {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }
        .summary-item .label { font-size: 13px; color: #64748b; }
        .summary-item .value { font-size: 18px; font-weight: 700; color: #1e293b; }
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
            <nav class="sidebar-nav">
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
                <a href="holidays.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Holidays</span></a>
                <a href="leave_requests.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Leave Requests</span></a>
                <a href="team_attendance.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                    </svg>
                    <span class="nav-text">Attendance</span>
                </a>
                <a href="projects.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg><span class="nav-text">Projects</span></a>
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
                    <h1 class="page-title">Team Attendance Report</h1>
                </div>
            </header>

            <div class="content">
                <div class="filter-panel">
                    <form method="GET" class="filter-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="form-group">
                            <label>Team Member</label>
                            <select name="employee_id" class="form-control">
                                <option value="">All Members</option>
                                <?php foreach($team_members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>" <?php echo $search_emp == $member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn-filter">Apply Filters</button>
                        </div>
                    </form>
                    
                    <div class="summary-mini">
                        <div class="summary-item">
                            <div class="label">Total Check-ins</div>
                            <div class="value"><?php echo $total_present; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Total Overtime</div>
                            <div class="value"><?php echo number_format($total_ot, 1); ?> hrs</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="attendance-table-container">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Total Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="6" class="no-data">No attendance records found for the selected period.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('D, M d, Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
                                                <small style="color:#64748b;">#<?php echo htmlspecialchars($record['emp_code']); ?></small>
                                            </td>
                                            <td><?php echo $record['clock_in'] ? date('h:i A', strtotime($record['clock_in'])) : '-'; ?></td>
                                            <td><?php echo $record['clock_out'] ? date('h:i A', strtotime($record['clock_out'])) : '-'; ?></td>
                                            <td>
                                                <?php echo number_format($record['total_hours'], 2); ?> hrs
                                                <?php if($record['overtime_hours'] > 0): ?>
                                                    <span class="ot-badge" style="display:inline-block; margin-left:4px;">OT: <?php echo number_format($record['overtime_hours'], 1); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
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
