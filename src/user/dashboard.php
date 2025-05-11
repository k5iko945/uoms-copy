<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Start or resume session
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Clear any existing session data
    session_unset();
    session_destroy();
    redirect_with_message('../login.php', 'error', 'Please login to access the dashboard.');
}

// Add cache control headers to prevent back button access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Fri, 01 Jan 1990 00:00:00 GMT");

// Verify session timeout (e.g., 30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Clear session and redirect to login
    session_unset();
    session_destroy();
    redirect_with_message('../login.php', 'error', 'Your session has expired. Please login again.');
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

// Check if user is logged in, redirect if not
check_permission('user');

// Check if site is in maintenance mode and user is not admin
$maintenance_mode = get_setting('maintenance_mode', 0);
$site_name = get_setting('site_name', 'Working Scholars Association');

// Get error or success messages
$error = get_flash_message('error');
$success = get_flash_message('success');

if ($maintenance_mode && !is_admin()) {
    // Log the access attempt
    log_audit_event($_SESSION['user_id'], 'Maintenance Mode Access', 'User attempted to access dashboard during maintenance mode');
    
    // Clear session and redirect to login
    session_destroy();
    redirect_with_message('../login.php', 'error', 'The system is currently under maintenance. Please try again later.');
}

// Get the currently active page
$current_page = basename($_SERVER['PHP_SELF']);

// Add this after your existing requires
$user_id = $_SESSION['user_id'];

// Get user's recent activities
$sql_activity = "SELECT * FROM audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql_activity);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_activity = mysqli_stmt_get_result($stmt);
$recent_activities = [];
while ($row = mysqli_fetch_assoc($result_activity)) {
    $recent_activities[] = $row;
}

// Get user's clearance statistics
$sql_clearance = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM permits WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql_clearance);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_clearance = mysqli_stmt_get_result($stmt);
$clearance_stats = mysqli_fetch_assoc($result_clearance);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | <?php echo htmlspecialchars($site_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/user-style.css">

    <!-- Chart.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <!-- Chart.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/images/logo/wsa.jpg" alt="WSA Logo" class="rounded-circle me-2" width="32" height="32">
                <span class="fw-bold">WSA Dashboard</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Main Navigation -->
            <div class="collapse navbar-collapse" id="topNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'ledger.php' ? 'active' : ''; ?>" href="ledger.php">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Ledger
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'billing.php' ? 'active' : ''; ?>" href="billing.php">
                            <i class="fas fa-receipt me-1"></i> Billing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'permits.php' ? 'active' : ''; ?>" href="permits.php">
                            <i class="fas fa-clipboard-check me-1"></i> Permits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'card.php' ? 'active' : ''; ?>" href="card.php">
                            <i class="fas fa-id-card me-1"></i> Card
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" href="shop.php">
                            <i class="fas fa-shopping-cart me-1"></i> Shop
                        </a>
                    </li>
                </ul>

                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" data-bs-toggle="dropdown">
                            <img src="<?php echo !empty($_SESSION['profile_image']) ? '../' . $_SESSION['profile_image'] : 'https://via.placeholder.com/32'; ?>" 
                                 class="rounded-circle me-2" width="32" height="32" alt="Profile">
                            <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header"><?php echo htmlspecialchars($_SESSION['student_id']); ?></h6></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php" onclick="return confirmAction('Are you sure you want to log out?');">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid py-4">
        <!-- Page heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        </div>
        
        <!-- Dashboard content -->
        <div class="container-fluid">
            <!-- Content Row -->
            <div class="row">
                <!-- Account Overview Chart -->
                <div class="col-xl-4 col-lg-1 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Account Overview</h6>
                            <div class="dropdown no-arrow">
                                <button class="btn btn-link btn-sm" type="button" id="accountDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">View Profile</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="accountChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Clearance Status Chart -->
                <div class="col-xl-6 col-lg-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Clearance Status</h6>
                            <div class="dropdown no-arrow">
                                <button class="btn btn-link btn-sm" type="button" id="clearanceDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="permits.php">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="clearanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Quick Actions -->
            <div class="row">
                <!-- Recent Activity -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow hover-lift">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless">
                                    <tbody>
                                        <?php if (empty($recent_activities)): ?>
                                            <tr><td colspan="3" class="text-center text-muted">No recent activity found</td></tr>
                                        <?php else: foreach ($recent_activities as $activity): ?>
                                            <tr class="fade-in">
                                                <td width="60">
                                                    <div class="icon-circle bg-primary text-white">
                                                        <i class="fas fa-<?php 
                                                            switch($activity['action']) {
                                                                case 'Login': echo 'sign-in-alt'; break;
                                                                case 'Update': echo 'edit'; break;
                                                                default: echo 'history';
                                                            }
                                                        ?>"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small text-gray-500"><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></div>
                                                    <span><?php echo htmlspecialchars($activity['description']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow hover-lift">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="profile.php" class="btn btn-primary hover-scale">
                                    <i class="fas fa-user-edit me-2"></i> Update Profile
                                </a>
                                <a href="permits.php" class="btn btn-primary hover-scale">
                                    <i class="fas fa-clipboard-list me-2"></i> View Permits
                                </a>
                                <a href="ledger.php" class="btn btn-primary hover-scale">
                                    <i class="fas fa-file-invoice-dollar me-2"></i> Check Balance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Confirmation for actions
        function confirmAction(message) {
            return confirm(message);
        }
        
        $(document).ready(function() {
            // Account Overview Chart
            const accountCtx = document.getElementById('accountChart').getContext('2d');
            const accountChart = new Chart(accountCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Balance', 'Points', 'Permits'],
                    datasets: [{
                        data: [
                            <?php echo $user_balance ?? 0; ?>,
                            <?php echo $user_points ?? 0; ?>,
                            <?php echo $user_permits ?? 0; ?>
                        ],
                        backgroundColor: [
                            '#4e73df', // Primary blue
                            '#1cc88a', // Success green
                            '#f6c23e'  // Warning yellow
                        ],
                        borderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Account Statistics'
                        }
                    }
                }
            });

            // Clearance Status Chart
            const clearanceCtx = document.getElementById('clearanceChart').getContext('2d');
            const clearanceChart = new Chart(clearanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Pending', 'Approved', 'Rejected'],
                    datasets: [{
                        label: 'Clearance Status',
                        data: [
                            <?php echo $clearance_stats['pending'] ?? 0; ?>,
                            <?php echo $clearance_stats['approved'] ?? 0; ?>,
                            <?php echo $clearance_stats['rejected'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            '#FFA500', // Orange for pending
                            '#28A745', // Green for approved
                            '#DC3545'  // Red for rejected
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Permit Applications'
                        }
                    }
                }
            });
        });

        // Prevent going back to authenticated pages after logout
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
        };

        // Force reload on browser back button
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            location.reload();
        }

        // Session timeout warning
        let sessionTimeout;
        const warningTime = 1700000; // 28.33 minutes
        const redirectTime = 1800000; // 30 minutes

        function startSessionTimer() {
            sessionTimeout = setTimeout(function() {
                const warning = confirm('Your session will expire in 2 minutes. Would you like to continue?');
                if (warning) {
                    // Reset session timer via AJAX call
                    fetch('../includes/refresh_session.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                startSessionTimer();
                            } else {
                                window.location.href = '../logout.php';
                            }
                        });
                } else {
                    window.location.href = '../logout.php';
                }
            }, warningTime);
        }

        startSessionTimer();

        // Reset timer on user activity
        document.addEventListener('mousemove', function() {
            clearTimeout(sessionTimeout);
            startSessionTimer();
        });
    </script>
</body>
</html>