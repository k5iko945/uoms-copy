<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in, redirect if not
check_permission('admin');

// Fetch dashboard statistics from database
// Total users count
$sql_users = "SELECT COUNT(*) as total_users FROM users";
$result_users = mysqli_query($conn, $sql_users);
$row_users = mysqli_fetch_assoc($result_users);
$total_users = $row_users['total_users'];

// Pending permits count
$sql_permits = "SELECT COUNT(*) as pending_permits FROM permits WHERE status = 'pending'";
$result_permits = mysqli_query($conn, $sql_permits);
$row_permits = mysqli_fetch_assoc($result_permits);
$pending_permits = $row_permits['pending_permits'];


// Get user statistics
$sql_user_stats = "SELECT 
    COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
    COUNT(CASE WHEN role = 'staff' THEN 1 END) as staff
    FROM users";
$result_user_stats = mysqli_query($conn, $sql_user_stats);
$user_stats = mysqli_fetch_assoc($result_user_stats);

// Get clearance statistics
$sql_clearance = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM permits";
$result_clearance = mysqli_query($conn, $sql_clearance);
$clearance_stats = mysqli_fetch_assoc($result_clearance);

// Get recent activity from audit log
$sql_activity = "SELECT a.*, u.first_name, u.last_name, u.student_id 
                FROM audit_log a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.created_at DESC LIMIT 5";
$result_activity = mysqli_query($conn, $sql_activity);
$recent_activities = [];
while ($row = mysqli_fetch_assoc($result_activity)) {
    $recent_activities[] = $row;
}

// Set page title
$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon.ico" type="image/x-icon">
    <title>Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
        <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include 'includes/header.php'; ?>
                
                <!-- Dashboard content -->
                <div class="container-fluid p-0">
                    <!-- Page heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Welcome, Administrator</h1>
                    </div>
                    
                   <!-- Content Row -->
<div class="row">
    <!-- Users Overview Chart -->
         <div class="col-xl-5 col-lg-6 mb-4">

        <div class="card shadow h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Users Overview</h6>
                <div class="dropdown no-arrow">
                    <button class="btn btn-link btn-sm" type="button" id="usersDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="usersDropdown">
                        <a class="dropdown-item" href="accounts.php">View Details</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="usersChart"></canvas>
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
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="clearanceDropdown">
                        <a class="dropdown-item" href="compliance.php">View Details</a>
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
                            <div class="card shadow mb-4 hover-lift">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                                    <a href="audit.php?tab=audit-log" class="btn btn-sm btn-primary hover-scale">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_activities)): ?>
                                        <p class="text-center text-muted my-5">No recent activity found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-borderless">
                                                <tbody>
                                                    <?php foreach ($recent_activities as $activity): ?>
                                                        <tr class="fade-in">
                                                            <td width="60">
                                                                <div class="icon-circle bg-primary text-white">
                                                                    <i class="fas fa-<?php 
                                                                        switch($activity['action']) {
                                                                            case 'Login': echo 'sign-in-alt'; break;
                                                                            case 'Create': echo 'plus'; break;
                                                                            case 'Update': echo 'edit'; break;
                                                                            case 'Delete': echo 'trash'; break;
                                                                            default: echo 'history';
                                                                        }
                                                                    ?>"></i>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="small text-gray-500"><?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?></div>
                                                                <span class="font-weight-bold"><?php echo htmlspecialchars($activity['action']); ?></span> - 
                                                                <?php echo htmlspecialchars($activity['description']); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4 hover-lift">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="accounts.php?action=add" class="btn btn-primary btn-block hover-scale">
                                            <i class="fas fa-user-plus me-2"></i> Add New User
                                        </a>
                                       
                                        <a href="audit.php" class="btn btn-primary btn-block hover-scale">
                                            <i class="fas fa-file-invoice-dollar me-2"></i> Manage Audit
                                        </a>
                                    </div>
                                </div>
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
    <script src="../js/script.js"></script>
    <script>
        $(document).ready(function() {
            // Mobile menu toggle for the sidebar
            $('#mobileToggle').on('click', function() {
                $('.sidebar').toggleClass('show');
                $('body').toggleClass('sidebar-open');
            });
            
            // Add smooth fade-in animations to cards
            setTimeout(function() {
                $('.card').each(function(index) {
                    setTimeout(function(card) {
                        $(card).addClass('fade-in');
                    }, index * 100, this);
                });
            }, 300);
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() >= 768) {
                    $('.sidebar').removeClass('show');
                    $('body').removeClass('sidebar-open');
                }
            });
            
            // Animated counters for statistics
            $('.card .text-gray-800').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                if (!isNaN(countTo) && countTo > 0) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }
                    });
                }
            });
            
            // Confirm logout
            $('a[href="../logout.php"]').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to log out?')) {
                    window.location.href = '../logout.php';
                }
            });

            // Users Overview Chart
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            const usersChart = new Chart(usersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Students', 'Admins', 'Staff'],
                    datasets: [{
                        data: [
                            <?php echo $user_stats['students']; ?>,
                            <?php echo $user_stats['admins']; ?>,
                            <?php echo $user_stats['staff']; ?>
                        ],
                        backgroundColor: [
                            '#000000', // Black Students
                            '#DC3545', // Red Admins
                            '#00008B'  // Blue Staff
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
                            text: 'User Distribution'
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
                            <?php echo $clearance_stats['pending']; ?>,
                            <?php echo $clearance_stats['approved']; ?>,
                            <?php echo $clearance_stats['rejected']; ?>
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
                            text: 'Clearance Applications'
                        }
                    }
                }
            });

            // Auto-refresh charts every 5 minutes
            setInterval(function() {
                $.ajax({
                    url: 'ajax/get_dashboard_stats.php',
                    method: 'GET',
                    success: function(response) {
                        const data = JSON.parse(response);
                        
                        // Update Users Chart
                        usersChart.data.datasets[0].data = [
                            data.users.students,
                            data.users.admins,
                            data.users.staff
                        ];
                        usersChart.update();

                        // Update Clearance Chart
                        clearanceChart.data.datasets[0].data = [
                            data.clearance.pending,
                            data.clearance.approved,
                            data.clearance.rejected
                        ];
                        clearanceChart.update();
                    }
                });
            }, 300000); // 5 minutes
        });
    </script>
</body>
</html>