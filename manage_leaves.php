<?php
/**
 * Manage Leave Approvals
 * Requirement: Leave Request module
 * Purpose: Admins and Managers can approve or reject leave requests
 */

session_start();
require_once 'config.php';

// Check if logged in and has permission
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'employee') {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$viewer_name = $_SESSION['admin_name'] ?? $_SESSION['manager_name'];
$viewer_id = $_SESSION['admin_id'] ?? $_SESSION['manager_id'];
$success = '';
$error = '';

// Handle Approval Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approved' or 'rejected'
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    // Validate request
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approved_by = ?, approved_at = CURRENT_TIMESTAMP, rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("sisi", $action, $viewer_id, $rejection_reason, $request_id);
    
    if ($stmt->execute()) {
        $success = "Leave request " . ($action === 'approved' ? 'approved' : 'rejected') . " successfully!";
    } else {
        $error = "Error updating request: " . $conn->error;
    }
    $stmt->close();
}

// Fetch pending requests
$pending_requests = [];
if ($user_type === 'admin') {
    $sql = "SELECT lr.*, e.first_name, e.last_name, e.department, e.employee_id as emp_code
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.id 
            WHERE lr.status = 'pending'
            ORDER BY lr.created_at ASC";
    $result = $conn->query($sql);
} else {
    $dept = $_SESSION['manager_department'];
    $sql = "SELECT lr.*, e.first_name, e.last_name, e.department, e.employee_id as emp_code
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.id 
            WHERE lr.status = 'pending' AND e.department = '$dept'
            ORDER BY lr.created_at ASC";
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
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
    <title>Manage Leaves - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <link rel="stylesheet" href="css/add_employee.css">
    <style>
        .leave-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #0078D4;
        }
        .leave-card-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .leave-days { font-weight: bold; color: #1f2937; }
        .leave-reason { font-size: 14px; color: #4b5563; background: #f9fafb; padding: 12px; border-radius: 6px; margin: 12px 0; }
        
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
            max-width: 450px;
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
                                <stop offset="0%" stop-color="#0078D4"/><stop offset="100%" stop-color="#0053A0"/>
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
                <?php else: ?>
                    <a href="manager_dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="holidays.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Holidays</span></a>
                    <a href="leave_requests.php" class="nav-item active"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg><span class="nav-text">Leave Requests</span></a>
                    <a href="projects.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg><span class="nav-text">Projects</span></a>
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
                    <a href="leave_requests.php" style="color: #6b7280; text-decoration: none; margin-right: 8px; display:flex; align-items:center;">
                         <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px; height:16px; margin-right:4px;"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"/></svg>
                         Back
                    </a>
                    <h1 class="page-title">Manage Leave Approvals</h1>
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

                <?php if (empty($pending_requests)): ?>
                    <div class="card" style="text-align:center; padding: 48px;">
                        <svg style="width:64px; height:64px; color:#e5e7eb; margin-bottom:16px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/></svg>
                        <h3 style="color:#6b7280;">No pending leave requests found.</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_requests as $req): ?>
                    <div class="leave-card">
                        <div class="leave-card-header">
                            <div>
                                <h3 style="margin:0; font-size:18px;"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></h3>
                                <div style="color:#6b7280; font-size:12px;"><?php echo htmlspecialchars($req['emp_code']); ?> • <?php echo htmlspecialchars($req['department']); ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div class="leave-days"><?php echo floatval($req['leave_days']); ?> Days</div>
                                <div style="color:#6b7280; font-size:12px;"><?php echo htmlspecialchars($req['leave_type']); ?></div>
                            </div>
                        </div>
                        <div style="display:flex; gap:24px; color:#4b5563; font-size:14px; margin-bottom:12px;">
                            <span><strong>Start:</strong> <?php echo date('M d, Y', strtotime($req['start_date'])); ?></span>
                            <span><strong>End:</strong> <?php echo date('M d, Y', strtotime($req['end_date'])); ?></span>
                        </div>
                        <div class="leave-reason">
                            <strong>Reason:</strong><br>
                            <?php echo nl2br(htmlspecialchars($req['reason'])); ?>
                        </div>
                        <div style="display:flex; gap:12px; margin-top:20px;">
                            <form method="POST" style="flex:1;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <input type="hidden" name="action" value="approved">
                                <button type="submit" class="btn btn-primary" style="width:100%;">Approve</button>
                            </form>
                            <button onclick="openRejectModal(<?php echo $req['id']; ?>)" class="btn btn-secondary" style="flex:1; border-color:#ef4444; color:#ef4444;">Reject</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h3 class="step-title">Reject Leave Request</h3>
            <p style="font-size:14px; color:#6b7280;">Please provide a reason for rejection.</p>
            <form method="POST" action="">
                <input type="hidden" name="request_id" id="reject_id">
                <input type="hidden" name="action" value="rejected">
                <div class="form-group">
                    <textarea name="rejection_reason" class="form-input" rows="4" required placeholder="e.g., Department understaffed during these dates."></textarea>
                </div>
                <div style="display:flex; gap:12px; margin-top:24px;">
                    <button type="submit" class="btn btn-primary" style="flex:1; background:#ef4444;">Confirm Reject</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script>
        function openRejectModal(id) { 
            document.getElementById('reject_id').value = id;
            document.getElementById('rejectModal').classList.add('active'); 
        }
        function closeModal() { document.getElementById('rejectModal').classList.remove('active'); }
    </script>
</body>
</html>
