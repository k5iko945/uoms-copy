<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if the site is in maintenance mode
$maintenance_mode = get_setting('maintenance_mode', 0);
$site_name = get_setting('site_name', 'Working Scholars Association');

// Check if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        // If site is in maintenance mode and user is not admin, log them out
        if ($maintenance_mode && !is_admin()) {
            session_destroy();
            redirect_with_message('login.php', 'error', 'The system is currently under maintenance. Please try again later.');
        } else {
            redirect('user/dashboard.php');
        }
    }
}

// Get error or success messages
$error = get_flash_message('error');
$success = get_flash_message('success');

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the site is in maintenance mode and the user is not trying to log in as admin
    if ($maintenance_mode && $_POST['student_id'] !== 'admin') {
        redirect_with_message('login.php', 'error', 'The system is currently under maintenance. Please try again later.');
    } else {
        // Validate CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            redirect_with_message('login.php', 'error', 'Invalid request, please try again.');
        } else {
            // Get and clean form inputs
            $student_id = clean_input($_POST['student_id']);
            $password = $_POST['password']; // Don't clean password

            // Validate required fields
            if (empty($student_id) || empty($password)) {
                redirect_with_message('login.php', 'error', 'Please enter both Student ID and Password.');
            } else {
                // Special handling for admin user
                if ($student_id === 'admin' && $password === 'Fifth_#5') {
                    // Get admin user from database
                    $sql = "SELECT * FROM users WHERE student_id = 'admin' AND role = 'admin'";
                    $result = mysqli_query($conn, $sql);
                    
                    if ($user = mysqli_fetch_assoc($result)) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['profile_image'] = $user['profile_image'];
                        
                        // Log the login action
                        $action = 'Admin Login';
                        $description = 'Admin logged in successfully';
                        log_audit_event($user['id'], $action, $description);
                        
                        // Redirect to admin dashboard
                        redirect('admin/dashboard.php');
                    }
                }
            
                // Check user in database (normal flow for non-admin users)
                $sql = "SELECT * FROM users WHERE student_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $student_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($user = mysqli_fetch_assoc($result)) {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Check if site is in maintenance mode and user is not admin
                        if ($maintenance_mode && $user['role'] !== 'admin') {
                            redirect_with_message('login.php', 'error', 'The system is currently under maintenance. Please try again later.');
                        } else {
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['student_id'] = $user['student_id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                            $_SESSION['profile_image'] = $user['profile_image'];
                            
                            // Log the login action
                            $action = 'User Login';
                            $description = 'User logged in successfully';
                            log_audit_event($user['id'], $action, $description);
                            
                            // Redirect based on role
                            if ($user['role'] === 'admin') {
                                redirect('admin/dashboard.php');
                            } else {
                                redirect('user/dashboard.php');
                            }
                        }
                    } else {
                        redirect_with_message('login.php', 'error', 'Invalid password. Please try again.');
                    }
                } else {
                    redirect_with_message('login.php', 'error', 'Student ID not found.');
                }
            }
        }
    }
}

// Generate new CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo htmlspecialchars($site_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
            background: linear-gradient(135deg, rgba(184, 134, 11, 0.1) 0%, rgba(0, 0, 0, 0.05) 100%);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(184, 134, 11, 0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            transition: all 0.3s;
        }
        .login-card:hover {
            box-shadow: 0 15px 35px rgba(184, 134, 11, 0.15);
            transform: translateY(-5px);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
            border-radius: 50%;
            padding: 5px;
            border: 2px solid var(--primary-color);
            background-color: white;
            transition: transform 0.3s;
        }
        .logo:hover {
            transform: scale(1.05);
        }
        .university-name {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: bold;
        }
        .system-name {
            font-size: 20px;
            color: var(--quaternary-color);
            margin-bottom: 20px;
            font-weight: 500;
        }
        .login-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            width: 100%;
            padding: 10px;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        .login-btn:hover {
            background-color: #9A7209; /* Darker bronze-gold */
            border-color: #9A7209;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .login-footer {
            font-size: 12px;
            text-align: right;
            margin-top: 15px;
            color: #6c757d;
        }
        .form-note {
            font-size: 13px;
            color: #6c757d;
        }
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: var(--quaternary-color);
            text-decoration: none;
        }
        .forgot-password:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card bg-white">
        <div class="logo-container">
            <img src="assets/images/logo/wsa.jpg" alt="Working Scholars Association Logo" class="logo">
            <h1 class="university-name">Working Scholars Association</h1>
            <h2 class="system-name">Organization Account</h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($maintenance_mode && !isset($error)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-tools me-2"></i> The system is currently under maintenance. Only administrators can login at this time.
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID Number</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required 
                    placeholder="Ex. 20****">
                <div class="form-note mt-1">Don't use your Google Workspace email here.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required 
                    placeholder="Enter password">
                <div class="form-note mt-1">Never share your password with anyone.</div>
            </div>

            <button type="submit" class="btn btn-primary login-btn">
                Log In
            </button>
            
            <a href="#" class="forgot-password">Forgot or expired password? Reset password here</a>
        </form>
        
        <div class="login-footer">
            v1.0.2025
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 