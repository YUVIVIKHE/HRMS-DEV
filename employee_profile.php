<?php
session_start();
require_once 'config.php';
require_once 'includes/dropdown_helper.php';

if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$viewer_id = $_SESSION[$user_type . '_id'];
$viewer_name = $_SESSION[$user_type . '_name'];

$profile_id = null;

// Determine whose profile to view
if (isset($_GET['id'])) {
    if ($user_type === 'admin' || $user_type === 'manager') {
        $profile_id = $_GET['id'];
    } elseif ($user_type === 'employee') {
        // Employees can only view themselves
        if ($_GET['id'] != $_SESSION['employee_id']) {
            header('Location: employee_profile.php'); // Redirect to self without param
            exit();
        }
        $profile_id = $_SESSION['employee_id'];
    }
} else {
    // No ID provided, default to self if employee
    if ($user_type === 'employee') {
        $profile_id = $_SESSION['employee_id'];
    } else {
        // Admin/Manager without ID -> Redirect to appropriate list or dashboard
        if ($user_type === 'admin') {
            header('Location: employees.php');
            exit();
        } else {
            header('Location: manager_dashboard.php'); // Or team list if available
            exit();
        }
    }
}

// Fetch employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    // Handle case where employee not found
    header('Location: dashboard.php'); // Fallback
    exit();
}

// Helper to safely show value
function showVal($val) {
    return htmlspecialchars($val ?? '-');
}

// Helper to format date
function showDate($date) {
    return $date ? date('M d, Y', strtotime($date)) : '-';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/add_employee.css">
    <style>
        .form-input {
            background-color: #f9fafb;
            cursor: default;
            color: #374151;
        }
        .form-input:focus {
            border-color: #e5e7eb;
            box-shadow: none;
        }
        .profile-header-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .profile-avatar-xl {
            width: 100px;
            height: 100px;
            background: #0078D4;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 600;
        }
        .profile-info h2 {
            margin: 0 0 8px 0;
            color: #1f2937;
        }
        .profile-info p {
            margin: 0 0 4px 0;
            color: #6b7280;
        }
        .status-badge-lg {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .edit-btn-wrapper {
            margin-left: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Dynamic Sidebar -->
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
                <?php if ($user_type === 'admin'): ?>
                    <!-- Admin Sidebar Links -->
                    <a href="dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employees.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span class="nav-text">Employees</span>
                    </a>
                    <a href="projects.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                        <span class="nav-text">Projects</span>
                    </a>
                    <a href="dropdown_management.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span class="nav-text">Settings</span>
                    </a>

                <?php elseif ($user_type === 'manager'): ?>
                    <!-- Manager Sidebar Links -->
                    <a href="manager_dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="#" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span class="nav-text">Team Members</span>
                    </a>

                <?php else: ?>
                    <!-- Employee Sidebar Links -->
                    <a href="employee_dashboard.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <a href="employee_profile.php" class="nav-item active">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                        </svg>
                        <span class="nav-text">My Profile</span>
                    </a>
                    <a href="attendance.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <span class="nav-text">My Attendance</span>
                    </a>
                     <a href="employee_holidays.php" class="nav-item">
                        <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                        </svg>
                        <span class="nav-text">Holidays</span>
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
                    <h1 class="page-title">Employee Profile</h1>
                </div>
                <!-- User Menu -->
                 <div class="header-right">
                    <div class="user-menu">
                         <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($viewer_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($viewer_name); ?></div>
                                <div class="user-role"><?php echo ucfirst($user_type); ?></div>
                            </div>
                            <svg class="chevron" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                             <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="profile-header-card">
                    <div class="profile-avatar-xl">
                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo showVal($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                        <p><?php echo showVal($employee['designation'] ?? $employee['job_title']); ?> • <?php echo showVal($employee['department']); ?></p>
                        <p><?php echo showVal($employee['email']); ?> • <?php echo showVal($employee['phone']); ?></p>
                        <span class="status-badge-lg status-<?php echo strtolower($employee['status']); ?>">
                            <?php echo ucfirst($employee['status']); ?>
                        </span>
                    </div>
                    <?php if ($user_type === 'admin'): ?>
                    <div class="edit-btn-wrapper">
                        <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                            <svg style="width:16px;height:16px;margin-right:8px;" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            Edit Profile
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-container">
                    <!-- Sections identical to Edit but using disabled inputs -->
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        
                        <!-- Personal -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header"><h3 class="card-title">Personal Information</h3></div>
                            <div class="card-body" style="padding: 24px;">
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="form-input" value="<?php echo showVal($employee['first_name'] . ' ' . $employee['last_name']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Date of Birth</label><input type="text" class="form-input" value="<?php echo showDate($employee['date_of_birth']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Gender</label><input type="text" class="form-input" value="<?php echo showVal($employee['gender']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Marital Status</label><input type="text" class="form-input" value="<?php echo showVal($employee['marital_status']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Blood Group</label><input type="text" class="form-input" value="<?php echo showVal($employee['blood_group']); ?>" disabled></div>
                                </div>
                            </div>
                        </div>

                         <!-- Contact -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header"><h3 class="card-title">Contact Information</h3></div>
                            <div class="card-body" style="padding: 24px;">
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Email</label><input type="text" class="form-input" value="<?php echo showVal($employee['email']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Phone</label><input type="text" class="form-input" value="<?php echo showVal($employee['phone']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Address</label><input type="text" class="form-input" value="<?php echo showVal($employee['address_line1'] . ', ' . $employee['city'] . ', ' . $employee['state']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Emergency Contact</label><input type="text" class="form-input" value="<?php echo showVal($employee['emergency_contact']); ?>" disabled></div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header"><h3 class="card-title">Employment Details</h3></div>
                            <div class="card-body" style="padding: 24px;">
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Employee ID</label><input type="text" class="form-input" value="<?php echo showVal($employee['employee_id']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Department</label><input type="text" class="form-input" value="<?php echo showVal($employee['department']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Designation</label><input type="text" class="form-input" value="<?php echo showVal($employee['designation'] ?? $employee['job_title']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Date of Joining</label><input type="text" class="form-input" value="<?php echo showDate($employee['date_of_joining']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Direct Manager</label><input type="text" class="form-input" value="<?php echo showVal($employee['direct_manager']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" value="<?php echo showVal($employee['location']); ?>" disabled></div>
                                </div>
                            </div>
                        </div>
                        
                         <!-- Bank & Tax -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header"><h3 class="card-title">Bank & Tax Information</h3></div>
                            <div class="card-body" style="padding: 24px;">
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label">Account Type</label><input type="text" class="form-input" value="<?php echo showVal($employee['account_type']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Account Number</label><input type="text" class="form-input" value="<?php echo showVal($employee['account_number']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">IFSC Code</label><input type="text" class="form-input" value="<?php echo showVal($employee['ifsc_code']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">PAN</label><input type="text" class="form-input" value="<?php echo showVal($employee['pan']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">Aadhar No</label><input type="text" class="form-input" value="<?php echo showVal($employee['aadhar_no']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">PF Account</label><input type="text" class="form-input" value="<?php echo showVal($employee['pf_account_number']); ?>" disabled></div>
                                    <div class="form-group"><label class="form-label">UAN</label><input type="text" class="form-input" value="<?php echo showVal($employee['uan_number']); ?>" disabled></div>
                                </div>
                            </div>
                        </div>

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
