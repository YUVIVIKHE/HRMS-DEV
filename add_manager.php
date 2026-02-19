<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';
$generated_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate random password
    $generated_password = bin2hex(random_bytes(4)); // 8 character password
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO managers (name, email, password, department, phone, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", 
        $_POST['name'], 
        $_POST['email'], 
        $hashed_password, 
        $_POST['department'], 
        $_POST['phone'], 
        $_SESSION['admin_id']
    );
    
    if ($stmt->execute()) {
        $success = 'Manager added successfully!';
    } else {
        $error = 'Error adding manager: ' . $conn->error;
        $generated_password = '';
    }
    $stmt->close();
}

$admin_name = $_SESSION['admin_name'];

// Get notification count
$count_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'active'");
$notification_count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Manager - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/add_employee.css">
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
                <a href="dashboard.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="employees.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span class="nav-text">Employees</span>
                </a>
                <a href="managers.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                    <span class="nav-text">Managers</span>
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
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"/>
                    </svg>
                    <span class="nav-text">Settings</span>
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
                    <h1 class="page-title">Add Manager</h1>
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
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php echo $success; ?>
                </div>
                
                <?php if ($generated_password): ?>
                <div class="password-display">
                    <div class="password-header">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z"/>
                        </svg>
                        <h3>Manager Login Credentials</h3>
                    </div>
                    <div class="credentials">
                        <div class="credential-item">
                            <label>Username (Email):</label>
                            <div class="credential-value">
                                <span id="username"><?php echo htmlspecialchars($_POST['email']); ?></span>
                                <button onclick="copyToClipboard('username')" class="btn-copy">Copy</button>
                            </div>
                        </div>
                        <div class="credential-item">
                            <label>Password:</label>
                            <div class="credential-value">
                                <span id="password"><?php echo htmlspecialchars($generated_password); ?></span>
                                <button onclick="copyToClipboard('password')" class="btn-copy">Copy</button>
                            </div>
                        </div>
                    </div>
                    <p class="password-note">⚠️ Save these credentials now. The password will not be shown again.</p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-card">
                    <form method="POST" id="managerForm">
                        <div class="form-section active">
                            <h3 class="section-title">Manager Information</h3>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Full Name <span class="required">*</span></label>
                                    <input type="text" id="name" name="name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone">
                                </div>
                                
                                <div class="form-group">
                                    <label for="department">Department <span class="required">*</span></label>
                                    <select id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="IT">IT</option>
                                        <option value="HR">HR</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Operations">Operations</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="managers.php" class="btn-secondary">Cancel</a>
                                <button type="submit" class="btn-primary">Create Manager</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const button = element.nextElementSibling;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.style.background = '#10b981';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
