<?php
session_start();
require_once 'config.php';
require_once 'includes/dropdown_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'];
$upload_message = '';
$upload_error = '';

// Get notification count
$count_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'active'");
$notification_count = $count_result ? $count_result->fetch_assoc()['count'] : 0;

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle); // Skip header row
            
            $success_count = 0;
            $error_count = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle)) !== false) {
                // Generate random password for bulk upload
                $generated_password = bin2hex(random_bytes(4)); // 8 character password
                $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                
                // Determine shift type and required hours based on country (index 16)
                $country = $data[16];
                $shift_type = 'fixed';
                $required_hours = 8.00;
                
                // If country is not India, set flexible shift with 9 hours
                if (strtolower(trim($country)) !== 'india') {
                    $shift_type = 'flexible';
                    $required_hours = 9.00;
                }
                
                // Map CSV columns to database fields
                $stmt = $conn->prepare("INSERT INTO employees (
                    employee_id, first_name, last_name, email, password, phone, job_title, date_of_birth, 
                    gender, marital_status, date_of_joining, date_of_confirmation, address_line1, 
                    address_line2, state, city, zip_code, country, status, account_type, ifsc_code, 
                    account_number, pan, uan_number, pf_account_number, epf, professional_tax, 
                    tax_exempt, esi_number, direct_manager, location, place_of_issue, 
                    passport_expiry, passport_issue, place_of_birth, nationality, passport_no, 
                    emergency_contact, blood_group, aadhar_no, perm_zip_code, perm_state, 
                    perm_city, perm_address_line2, perm_address_line1, personal_email, 
                    country_code, base_location, department, user_code, employee_type, shift_type, required_hours
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Bind parameters (52 fields: 50 from CSV + password + shift_type + required_hours)
                $stmt->bind_param("ssssssssssssssssssssssssssssssssssssssssssssssssssssd",
                    $data[0], $data[1], $data[2], $data[3], $hashed_password, $data[4], $data[5], $data[6], $data[7],
                    $data[8], $data[9], $data[10], $data[11], $data[12], $data[13], $data[14],
                    $data[15], $data[16], $data[17], $data[18], $data[19], $data[20], $data[21],
                    $data[22], $data[23], $data[24], $data[25], $data[26], $data[27], $data[28],
                    $data[29], $data[30], $data[31], $data[32], $data[33], $data[34], $data[35],
                    $data[36], $data[37], $data[38], $data[39], $data[40], $data[41], $data[42],
                    $data[43], $data[44], $data[45], $data[46], $data[47], $data[48], $data[49], $shift_type, $required_hours
                );
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Row " . ($success_count + $error_count) . ": " . $stmt->error;
                }
                $stmt->close();
            }
            
            fclose($handle);
            
            if ($success_count > 0) {
                $upload_message = "Successfully imported $success_count employee(s).";
            }
            if ($error_count > 0) {
                $upload_error = "Failed to import $error_count employee(s). " . implode(', ', array_slice($errors, 0, 3));
            }
        } else {
            $upload_error = 'Please upload a valid CSV file.';
        }
    } else {
        $upload_error = 'Error uploading file. Please try again.';
    }
}

// Fetch all employees
$result = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
$employees = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
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
            <!-- Sidebar Search Removed for clarity as there is a main search box -->
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <nav class="sidebar-nav">
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
                    <span class="nav-text">Settings</span>
                </a>
                <a href="dropdown_management.php" class="nav-item <?php echo $current_page == 'dropdown_management.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/>
                    </svg>
                    <span class="nav-text">Master Data</span>
                </a>
                <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
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
                    <h1 class="page-title">Employees</h1>
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
                <?php if ($upload_message): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php echo $upload_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($upload_error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <?php echo $upload_error; ?>
                </div>
                <?php endif; ?>
                
                <!-- Bulk Upload Section -->
                <div class="bulk-upload-section" id="bulkUploadSection">
                    <div class="bulk-upload-header">
                        <h3>Bulk Upload Employees</h3>
                        <button class="btn-close" onclick="toggleBulkUpload()">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="bulk-upload-content">
                        <div class="upload-instructions">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/>
                            </svg>
                            <div>
                                <h4>Instructions:</h4>
                                <ul>
                                    <li>Download the sample CSV template below</li>
                                    <li>Fill in employee data following the template format</li>
                                    <li>Upload the completed CSV file</li>
                                    <li>All required fields must be filled</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="upload-actions">
                            <a href="sample_employee_template.csv" class="btn-download" download>
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                                </svg>
                                Download Sample CSV
                            </a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" name="csv_file" id="csvFile" accept=".csv" required onchange="handleFileSelect(event)">
                                <div class="upload-placeholder">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"/>
                                    </svg>
                                    <p><strong>Click to upload</strong> or drag and drop</p>
                                    <p class="file-info">CSV file only</p>
                                </div>
                                <div class="file-selected" id="fileSelected" style="display: none;">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                    </svg>
                                    <span id="fileName"></span>
                                    <button type="button" class="btn-remove" onclick="removeFile()">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn-upload">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"/>
                                </svg>
                                Upload CSV
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="employees-header">
                    <div class="search-box">
                        <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                        </svg>
                        <input type="text" class="search-input" placeholder="Search employees..." id="searchInput" onkeyup="searchEmployees()">
                    </div>
                    <div class="header-actions">
                        <button class="btn-bulk-upload" onclick="toggleBulkUpload()">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"/>
                            </svg>
                            Bulk Upload
                        </button>
                        <a href="add_employee.php" class="btn-add-employee">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                            </svg>
                            Add Employee
                        </a>
                    </div>
                </div>
                
                <div class="employees-table-container">
                    <div class="filter-box">
                        <select class="filter-select" id="deptFilter" onchange="filterEmployees()">
                            <option value="">All Departments</option>
                            <?php 
                            $departments = getDropdownValues($conn, 'department');
                            foreach ($departments as $dept) {
                                echo '<option value="' . htmlspecialchars($dept['value_text']) . '">' . htmlspecialchars($dept['value_text']) . '</option>';
                            }
                            ?>
                        </select>
                        <select class="filter-select" id="statusFilter" onchange="filterEmployees()">
                            <option value="">All Status</option>
                            <?php 
                            $statuses = getDropdownValues($conn, 'employee_status');
                            foreach ($statuses as $status) {
                                echo '<option value="' . htmlspecialchars($status['value_text']) . '">' . htmlspecialchars($status['value_text']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <table class="employees-table" id="employeesTable">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Designation</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($employees) > 0): ?>
                                <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                    <td>
                                        <div class="employee-name">
                                            <div class="employee-avatar">
                                                <?php 
                                                $firstName = $employee['first_name'] ?? '';
                                                echo strtoupper(substr($firstName, 0, 1)); 
                                                ?>
                                            </div>
                                            <span><?php 
                                                $firstName = $employee['first_name'] ?? '';
                                                $lastName = $employee['last_name'] ?? '';
                                                echo htmlspecialchars($firstName . ' ' . $lastName); 
                                            ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['job_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['designation'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($employee['status']); ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="profile.php?id=<?php echo $employee['id']; ?>" class="btn-icon" title="View">
                                                <svg viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                </svg>
                                            </a>
                                            <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn-icon" title="Edit">
                                                <svg viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                </svg>
                                            </a>
                                            <button class="btn-icon btn-delete" title="Delete">
                                                <svg viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-data">
                                        <div class="no-data-message">
                                            <svg viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                            </svg>
                                            <p>No employees found</p>
                                            <a href="add_employee.php" class="btn-primary">Add First Employee</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script src="js/employees.js"></script>
</body>
</html>
