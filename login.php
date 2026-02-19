<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // First check if it's an admin
        $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['user_type'] = 'admin';
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            // Check if it's a manager
            $stmt = $conn->prepare("SELECT id, name, email, password, department FROM managers WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $manager = $result->fetch_assoc();
                
                if (password_verify($password, $manager['password'])) {
                    $_SESSION['manager_id'] = $manager['id'];
                    $_SESSION['manager_name'] = $manager['name'];
                    $_SESSION['manager_email'] = $manager['email'];
                    $_SESSION['manager_department'] = $manager['department'];
                    $_SESSION['user_type'] = 'manager';
                    
                    header('Location: manager_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                // Check if it's an employee
                $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM employees WHERE email = ? AND status = 'active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $employee = $result->fetch_assoc();
                    
                    if (password_verify($password, $employee['password'])) {
                        $_SESSION['employee_id'] = $employee['id'];
                        $_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
                        $_SESSION['employee_email'] = $employee['email'];
                        $_SESSION['user_type'] = 'employee';
                        
                        header('Location: employee_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid email or password';
                    }
                } else {
                    $error = 'Invalid email or password';
                }
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Admin Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand-section">
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
                <h1 class="brand-title">HRMS Portal</h1>
                <p class="brand-subtitle">Human Resource Management System</p>
            </div>
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <div class="feature-text">Secure Authentication</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <div class="feature-text">Real-time Analytics</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <div class="feature-text">Cloud-based Solution</div>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-box">
                <h2 class="login-title">Sign In</h2>
                <p class="login-subtitle">Enter your credentials to access your account</p>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="admin@company.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/>
                            </svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <svg class="eye-icon" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" class="checkbox">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <span>Sign In</span>
                        <svg class="btn-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"/>
                        </svg>
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>© 2026 HRMS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/login.js"></script>
</body>
</html>
