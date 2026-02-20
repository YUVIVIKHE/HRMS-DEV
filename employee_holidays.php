<?php
/**
 * Employee Holiday View Page
 * Requirement: Holiday List 2026 module
 * Purpose: Employees can view holidays (read-only)
 */

session_start();
require_once 'config.php';

// Check if user is employee
if (!isset($_SESSION['employee_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$employee_name = $_SESSION['employee_name'];

// Fetch holidays for current year
$year_filter = $_GET['year'] ?? date('Y');
$result = $conn->query("SELECT * FROM holidays WHERE year = $year_filter AND status = 'active' ORDER BY holiday_date ASC");
$holidays = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $holidays[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidays - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/employees.css">
    <style>
        .holiday-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid #0078D4;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .holiday-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .holiday-date {
            font-size: 18px;
            font-weight: 600;
            color: #0078D4;
        }
        .holiday-day {
            font-size: 14px;
            color: #6b7280;
        }
        .holiday-name {
            font-size: 16px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .holiday-type-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .holiday-type-national {
            background: #fef3c7;
            color: #92400e;
        }
        .holiday-type-optional {
            background: #dbeafe;
            color: #1e40af;
        }
        .holiday-type-company {
            background: #d1fae5;
            color: #065f46;
        }
        .year-selector {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 24px;
        }
        .year-selector select {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
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
            <nav class="sidebar-nav">
                <a href="employee_dashboard.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="attendance.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                    </svg>
                    <span class="nav-text">My Attendance</span>
                </a>
                <a href="employee_holidays.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                    </svg>
                    <span class="nav-text">Holidays</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                    </svg>
                    <span class="nav-text">Leave Requests</span>
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
                    <h1 class="page-title">Company Holidays</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar"><?php echo strtoupper(substr($employee_name, 0, 1)); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($employee_name); ?></div>
                                <div class="user-role">Employee</div>
                            </div>
                        </button>
                    </div>
                </div>
            </header>
            
            <div class="content">
                <div class="year-selector">
                    <label>Year:</label>
                    <select onchange="window.location.href='employee_holidays.php?year='+this.value">
                        <?php for ($y = 2024; $y <= 2030; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $year_filter) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <?php if (count($holidays) > 0): ?>
                    <?php foreach ($holidays as $holiday): ?>
                    <div class="holiday-card">
                        <div class="holiday-card-header">
                            <div>
                                <div class="holiday-date"><?php echo date('F d, Y', strtotime($holiday['holiday_date'])); ?></div>
                                <div class="holiday-day"><?php echo $holiday['day_of_week']; ?></div>
                            </div>
                            <span class="holiday-type-badge holiday-type-<?php echo strtolower($holiday['holiday_type']); ?>">
                                <?php echo $holiday['holiday_type']; ?>
                            </span>
                        </div>
                        <div class="holiday-name"><?php echo htmlspecialchars($holiday['holiday_name']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="no-data-message">
                                <p>No holidays found for <?php echo $year_filter; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
