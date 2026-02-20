<?php
session_start();
require_once 'config.php';
require_once 'includes/dropdown_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: employees.php');
    exit();
}

$success = '';
$error = '';

// Fetch existing employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    header('Location: employees.php');
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine shift type and required hours based on country
    $country = $_POST['country'];
    $shift_type = 'fixed';
    $required_hours = 8.00;
    
    // If country is not India, set flexible shift with 9 hours
    if (strtolower(trim($country)) !== 'india') {
        $shift_type = 'flexible';
        $required_hours = 9.00;
    }
    
    // Update Query
    $update_query = "UPDATE employees SET 
        first_name=?, last_name=?, email=?, phone=?, job_title=?, designation=?, date_of_birth=?, 
        gender=?, marital_status=?, date_of_joining=?, date_of_confirmation=?, address_line1=?, 
        address_line2=?, state=?, city=?, zip_code=?, country=?, status=?, account_type=?, ifsc_code=?, 
        account_number=?, pan=?, uan_number=?, pf_account_number=?, epf=?, professional_tax=?, 
        tax_exempt=?, esi_number=?, direct_manager=?, location=?, place_of_issue=?, 
        passport_expiry=?, passport_issue=?, place_of_birth=?, nationality=?, passport_no=?, 
        emergency_contact=?, blood_group=?, aadhar_no=?, perm_zip_code=?, perm_state=?, 
        perm_city=?, perm_address_line2=?, perm_address_line1=?, personal_email=?, 
        country_code=?, base_location=?, department=?, user_code=?, employee_type=?, shift_type=?, required_hours=?
        WHERE id=?";
        
    $stmt = $conn->prepare($update_query);
    
    $stmt->bind_param("ssssssssssssssssssssssssssssssssssssssssssssssssssssdi",
        $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], 
        $_POST['job_title'], $_POST['designation'], $_POST['date_of_birth'], $_POST['gender'],
        $_POST['marital_status'], $_POST['date_of_joining'], $_POST['date_of_confirmation'],
        $_POST['address_line1'], $_POST['address_line2'], $_POST['state'], $_POST['city'],
        $_POST['zip_code'], $_POST['country'], $_POST['status'], $_POST['account_type'],
        $_POST['ifsc_code'], $_POST['account_number'], $_POST['pan'], $_POST['uan_number'],
        $_POST['pf_account_number'], $_POST['epf'], $_POST['professional_tax'], $_POST['tax_exempt'],
        $_POST['esi_number'], $_POST['direct_manager'], $_POST['location'], $_POST['place_of_issue'],
        $_POST['passport_expiry'], $_POST['passport_issue'], $_POST['place_of_birth'],
        $_POST['nationality'], $_POST['passport_no'], $_POST['emergency_contact'],
        $_POST['blood_group'], $_POST['aadhar_no'], $_POST['perm_zip_code'], $_POST['perm_state'],
        $_POST['perm_city'], $_POST['perm_address_line2'], $_POST['perm_address_line1'],
        $_POST['personal_email'], $_POST['country_code'], $_POST['base_location'],
        $_POST['department'], $_POST['user_code'], $_POST['employee_type'], $shift_type, $required_hours,
        $id
    );
    
    if ($stmt->execute()) {
        $success = 'Employee updated successfully!';
        // Refresh data
        $stmt_refresh = $conn->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt_refresh->bind_param("i", $id);
        $stmt_refresh->execute();
        $employee = $stmt_refresh->get_result()->fetch_assoc();
        $stmt_refresh->close();
    } else {
        $error = 'Error updating employee: ' . $conn->error;
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
    <title>Edit Employee - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/add_employee.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Same as add_employee.php) -->
        <aside class="sidebar" id="sidebar">
            <!-- Include Sidebar content or copy-paste. For now copy-paste for standalone file -->
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
                <a href="employees.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span class="nav-text">Employees</span>
                </a>
                <!-- Other nav items... abbreviated for brevity in this response but will include in file -->
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
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                     <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                        </svg>
                    </button>
                    <h1 class="page-title">Edit Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
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
                
                <div class="form-container">
                    <div class="progress-bar">
                         <div class="progress-step active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Personal Info</div>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Contact Details</div>
                        </div>
                         <div class="progress-line"></div>
                        <div class="progress-step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Employment</div>
                        </div>
                         <div class="progress-line"></div>
                        <div class="progress-step" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Bank & Tax</div>
                        </div>
                         <div class="progress-line"></div>
                        <div class="progress-step" data-step="5">
                            <div class="step-number">5</div>
                            <div class="step-label">Documents</div>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="employeeForm" class="employee-form">
                        <!-- Step 1: Personal Information -->
                        <div class="form-step active" data-step="1">
                            <h3 class="step-title">Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Birth <span class="required">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($employee['date_of_birth']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender <span class="required">*</span></label>
                                    <select name="gender" class="form-input" required>
                                        <?php echo renderDropdownOptions($conn, 'gender', $employee['gender']); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marital Status</label>
                                    <select name="marital_status" class="form-input">
                                        <?php echo renderDropdownOptions($conn, 'marital_status', $employee['marital_status']); ?>
                                    </select>
                                </div>
                                 <div class="form-group">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group" class="form-input">
                                        <?php echo renderDropdownOptions($conn, 'blood_group', $employee['blood_group']); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Place of Birth</label>
                                    <input type="text" name="place_of_birth" class="form-input" value="<?php echo htmlspecialchars($employee['place_of_birth']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-input" value="<?php echo htmlspecialchars($employee['nationality']); ?>">
                                </div>
                            </div>
                        </div>
                        
                         <!-- Step 2: Contact Details -->
                        <div class="form-step" data-step="2">
                             <h3 class="step-title">Contact Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Email <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Personal Email</label>
                                    <input type="email" name="personal_email" class="form-input" value="<?php echo htmlspecialchars($employee['personal_email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Country Code</label>
                                    <input type="text" name="country_code" class="form-input" value="<?php echo htmlspecialchars($employee['country_code']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone <span class="required">*</span></label>
                                    <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Emergency Contact No</label>
                                    <input type="tel" name="emergency_contact" class="form-input" value="<?php echo htmlspecialchars($employee['emergency_contact']); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address Line 1 <span class="required">*</span></label>
                                    <input type="text" name="address_line1" class="form-input" value="<?php echo htmlspecialchars($employee['address_line1']); ?>" required>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address Line 2</label>
                                    <input type="text" name="address_line2" class="form-input" value="<?php echo htmlspecialchars($employee['address_line2']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">City <span class="required">*</span></label>
                                    <input type="text" name="city" class="form-input" value="<?php echo htmlspecialchars($employee['city']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">State <span class="required">*</span></label>
                                    <input type="text" name="state" class="form-input" value="<?php echo htmlspecialchars($employee['state']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Zip Code <span class="required">*</span></label>
                                    <input type="text" name="zip_code" class="form-input" value="<?php echo htmlspecialchars($employee['zip_code']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Country <span class="required">*</span></label>
                                    <input type="text" name="country" class="form-input" value="<?php echo htmlspecialchars($employee['country']); ?>" required>
                                </div>
                                 <!-- Permanent Address -->
                                <div class="form-group full-width">
                                     <h4 class="subsection-title">Permanent Address</h4>
                                </div>
                                 <div class="form-group full-width">
                                    <label class="form-label">Permanent Address Line 1</label>
                                    <input type="text" name="perm_address_line1" class="form-input" value="<?php echo htmlspecialchars($employee['perm_address_line1']); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Permanent Address Line 2</label>
                                    <input type="text" name="perm_address_line2" class="form-input" value="<?php echo htmlspecialchars($employee['perm_address_line2']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Permanent Address City</label>
                                    <input type="text" name="perm_city" class="form-input" value="<?php echo htmlspecialchars($employee['perm_city']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Permanent Address State</label>
                                    <input type="text" name="perm_state" class="form-input" value="<?php echo htmlspecialchars($employee['perm_state']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Permanent Address Zip Code</label>
                                    <input type="text" name="perm_zip_code" class="form-input" value="<?php echo htmlspecialchars($employee['perm_zip_code']); ?>">
                                </div>
                            </div>
                        </div>

                         <!-- Step 3: Employment Details -->
                        <div class="form-step" data-step="3">
                            <h3 class="step-title">Employment Details</h3>
                             <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Employee ID <span class="required">*</span></label>
                                    <input type="text" name="employee_id" class="form-input" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" readonly style="background: #f3f3f3; cursor: not-allowed;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">User Code</label>
                                    <input type="text" name="user_code" class="form-input" value="<?php echo htmlspecialchars($employee['user_code']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Job Title <span class="required">*</span></label>
                                    <input type="text" name="job_title" class="form-input" value="<?php echo htmlspecialchars($employee['job_title']); ?>" required>
                                </div>
                                 <div class="form-group">
                                    <label class="form-label">Designation <span class="required">*</span></label>
                                    <select name="designation" class="form-input" required>
                                        <?php echo renderDropdownOptions($conn, 'designation', $employee['designation']); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department <span class="required">*</span></label>
                                    <select name="department" class="form-input" required>
                                        <?php echo renderDropdownOptions($conn, 'department', $employee['department']); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Employee Type</label>
                                     <select name="employee_type" class="form-input">
                                        <?php echo renderDropdownOptions($conn, 'employment_type', $employee['employee_type']); ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Joining <span class="required">*</span></label>
                                    <input type="date" name="date_of_joining" class="form-input" value="<?php echo htmlspecialchars($employee['date_of_joining']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Confirmation</label>
                                    <input type="date" name="date_of_confirmation" class="form-input" value="<?php echo htmlspecialchars($employee['date_of_confirmation']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Direct Manager Name</label>
                                    <input type="text" name="direct_manager" class="form-input" value="<?php echo htmlspecialchars($employee['direct_manager']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="location" class="form-input" value="<?php echo htmlspecialchars($employee['location']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Base Location</label>
                                    <input type="text" name="base_location" class="form-input" value="<?php echo htmlspecialchars($employee['base_location']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status <span class="required">*</span></label>
                                     <select name="status" class="form-input" required>
                                        <?php echo renderDropdownOptions($conn, 'employee_status', $employee['status']); ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 4: Bank & Tax Information -->
                        <div class="form-step" data-step="4">
                             <h3 class="step-title">Bank & Tax Information</h3>
                             <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Account Type</label>
                                    <select name="account_type" class="form-input">
                                        <option value="">Select account type</option>
                                        <option value="Savings" <?php echo ($employee['account_type'] == 'Savings') ? 'selected' : ''; ?>>Savings</option>
                                        <option value="Current" <?php echo ($employee['account_type'] == 'Current') ? 'selected' : ''; ?>>Current</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" name="account_number" class="form-input" value="<?php echo htmlspecialchars($employee['account_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">IFSC Code</label>
                                    <input type="text" name="ifsc_code" class="form-input" value="<?php echo htmlspecialchars($employee['ifsc_code']); ?>">
                                </div>

                                <div class="form-group full-width">
                                    <h4 class="subsection-title">Tax & Statutory Information</h4>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PAN</label>
                                    <input type="text" name="pan" class="form-input" value="<?php echo htmlspecialchars($employee['pan']); ?>" maxlength="10">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Aadhar No</label>
                                    <input type="text" name="aadhar_no" class="form-input" value="<?php echo htmlspecialchars($employee['aadhar_no']); ?>" maxlength="12">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">UAN Number</label>
                                    <input type="text" name="uan_number" class="form-input" value="<?php echo htmlspecialchars($employee['uan_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PF Account Number</label>
                                    <input type="text" name="pf_account_number" class="form-input" value="<?php echo htmlspecialchars($employee['pf_account_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Employee Provident Fund</label>
                                    <input type="text" name="epf" class="form-input" value="<?php echo htmlspecialchars($employee['epf']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ESI Number</label>
                                    <input type="text" name="esi_number" class="form-input" value="<?php echo htmlspecialchars($employee['esi_number']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Professional Tax</label>
                                    <input type="text" name="professional_tax" class="form-input" value="<?php echo htmlspecialchars($employee['professional_tax']); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="tax_exempt" value="1" <?php echo ($employee['tax_exempt']) ? 'checked' : ''; ?>>
                                        <span>Exempt Employee from Tax Calculation</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                         <!-- Step 5: Documents -->
                        <div class="form-step" data-step="5">
                             <h3 class="step-title">Document Information</h3>
                             <div class="form-grid">
                                 <div class="form-group">
                                    <label class="form-label">Passport No</label>
                                    <input type="text" name="passport_no" class="form-input" value="<?php echo htmlspecialchars($employee['passport_no']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Place of Issue</label>
                                    <input type="text" name="place_of_issue" class="form-input" value="<?php echo htmlspecialchars($employee['place_of_issue']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Passport Date of Issue</label>
                                    <input type="date" name="passport_issue" class="form-input" value="<?php echo htmlspecialchars($employee['passport_issue']); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Passport Date of Expiry</label>
                                    <input type="date" name="passport_expiry" class="form-input" value="<?php echo htmlspecialchars($employee['passport_expiry']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)">Previous</button>
                            <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">Next</button>
                            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">Update Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="js/dashboard.js"></script>
    <script src="js/add_employee.js"></script>
</body>
</html>
