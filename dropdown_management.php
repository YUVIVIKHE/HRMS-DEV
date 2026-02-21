<?php
/**
 * Dropdown Management Page (Admin)
 * Requirement: Master Drop-down Management module
 * Purpose: Admin can manage dropdown categories and values
 */

session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'];
$user_type = $_SESSION['user_type'];
$success = '';
$error = '';

// Handle Add/Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_category') {
        $category_name = strtolower(str_replace(' ', '_', $_POST['category_name']));
        $category_label = $_POST['category_label'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO dropdown_categories (category_name, category_label, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $category_name, $category_label, $description);
        
        if ($stmt->execute()) {
            $success = 'Category added successfully!';
        } else {
            $error = 'Error adding category: ' . $conn->error;
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'add_value') {
        $category_id = $_POST['category_id'];
        $value_text = $_POST['value_text'];
        $display_order = $_POST['display_order'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO dropdown_values (category_id, value_text, display_order) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $category_id, $value_text, $display_order);
        
        if ($stmt->execute()) {
            $success = 'Value added successfully!';
        } else {
            $error = 'Error adding value: ' . $conn->error;
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'delete_value') {
        $value_id = $_POST['value_id'];
        $stmt = $conn->prepare("DELETE FROM dropdown_values WHERE id = ?");
        $stmt->bind_param("i", $value_id);
        
        if ($stmt->execute()) {
            $success = 'Value deleted successfully!';
        } else {
            $error = 'Error deleting value.';
        }
        $stmt->close();
    }
}

// Fetch all categories
$categories = [];
$result = $conn->query("SELECT * FROM dropdown_categories ORDER BY category_label ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
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
    <title>Master Data Management - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <style>
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }
        .category-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .category-description {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 16px;
        }
        .value-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .value-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 4px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .value-item:hover {
            background: #f3f4f6;
        }
        .btn-add-value {
            width: 100%;
            padding: 8px;
            background: #f3f4f6;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            margin-top: 12px;
        }
        .btn-add-value:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }
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
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .btn-close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }
        .btn-delete-value {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
        }
        .btn-delete-value:hover {
            color: #dc2626;
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
                <?php if ($user_type === 'admin'): ?>
                    <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employees.php" class="nav-item <?php echo in_array($current_page, ['employees.php', 'add_employee.php', 'edit_employee.php']) ? 'active' : ''; ?>">
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
                    <h1 class="page-title">Master Data Management</h1>
                </div>
                <div class="header-right">
                    <a href="notifications.php" class="icon-btn">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                        <?php if ($notification_count > 0): ?>
                        <span class="badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                                <div class="user-role">Administrator</div>
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
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <p style="color: #6b7280;">Manage dropdown values used across the application</p>
                    <button class="btn-add-employee" onclick="openCategoryModal()">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                        </svg>
                        Add Category
                    </button>
                </div>
                
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                    <?php
                        // Fetch values for this category
                        $cat_id = $category['id'];
                        $values_result = $conn->query("SELECT * FROM dropdown_values WHERE category_id = $cat_id ORDER BY display_order ASC, value_text ASC");
                        $values = [];
                        if ($values_result) {
                            while ($val = $values_result->fetch_assoc()) {
                                $values[] = $val;
                            }
                        }
                    ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-title"><?php echo htmlspecialchars($category['category_label']); ?></div>
                            <span style="font-size: 12px; color: #6b7280;"><?php echo count($values); ?> values</span>
                        </div>
                        <?php if ($category['description']): ?>
                        <div class="category-description"><?php echo htmlspecialchars($category['description']); ?></div>
                        <?php endif; ?>
                        <ul class="value-list">
                            <?php foreach ($values as $value): ?>
                            <li class="value-item">
                                <span><?php echo htmlspecialchars($value['value_text']); ?></span>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this value?')">
                                    <input type="hidden" name="action" value="delete_value">
                                    <input type="hidden" name="value_id" value="<?php echo $value['id']; ?>">
                                    <button type="submit" class="btn-delete-value">
                                        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"/>
                                        </svg>
                                    </button>
                                </form>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <button class="btn-add-value" onclick="openValueModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_label']); ?>')">
                            + Add Value
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <button class="btn-close-modal" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group">
                    <label class="form-label">Category Name <span class="required">*</span></label>
                    <input type="text" name="category_name" class="form-input" placeholder="e.g., Project Status" required>
                    <small style="color: #6b7280; font-size: 12px;">Will be converted to lowercase with underscores</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Label <span class="required">*</span></label>
                    <input type="text" name="category_label" class="form-input" placeholder="e.g., Project Status" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief description"></textarea>
                </div>
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn-primary" style="flex: 1;">Add Category</button>
                    <button type="button" class="btn-text" onclick="closeCategoryModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Value Modal -->
    <div class="modal" id="valueModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="valueModalTitle">Add Value</h3>
                <button class="btn-close-modal" onclick="closeValueModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_value">
                <input type="hidden" name="category_id" id="value_category_id">
                <div class="form-group">
                    <label class="form-label">Value <span class="required">*</span></label>
                    <input type="text" name="value_text" class="form-input" placeholder="Enter value" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="0" min="0">
                </div>
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn-primary" style="flex: 1;">Add Value</button>
                    <button type="button" class="btn-text" onclick="closeValueModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        function openCategoryModal() {
            document.getElementById('categoryModal').classList.add('active');
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }
        
        function openValueModal(categoryId, categoryLabel) {
            document.getElementById('valueModalTitle').textContent = 'Add Value to ' + categoryLabel;
            document.getElementById('value_category_id').value = categoryId;
            document.getElementById('valueModal').classList.add('active');
        }
        
        function closeValueModal() {
            document.getElementById('valueModal').classList.remove('active');
        }
        
        // Close modals on outside click
        document.getElementById('categoryModal').addEventListener('click', function(e) {
            if (e.target === this) closeCategoryModal();
        });
        
        document.getElementById('valueModal').addEventListener('click', function(e) {
            if (e.target === this) closeValueModal();
        });
    </script>
</body>
</html>
