<?php
// Get the currently active page
$current_page = basename($_SERVER['PHP_SELF']);

// Check for pending permits count for notifications
$sql_permits = "SELECT COUNT(*) as pending_permits FROM permits WHERE status = 'pending'";
$result_permits = mysqli_query($conn, $sql_permits);
$row_permits = mysqli_fetch_assoc($result_permits);
$pending_permits = $row_permits['pending_permits'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico" type="image/x-icon">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?> | Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS if needed -->
    <?php if (isset($use_datatables) && $use_datatables): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">
            <!-- Mobile Nav Toggle -->
            <button id="mobileToggle" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Branding -->
            <div class="d-none d-sm-inline-block">
                                            <a href="dashboard.php" style="text-decoration: none;">

                <img src="../assets/images/logo/wsa.jpg" alt="WSA Logo" class="rounded-circle me-2" width="32" height="32">
    </a>
<span class="h5 mb-0 text-gray-800">Working Scholars Association</span>
            </div>
            
            <!-- Topbar Navigation -->
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle hover-scale" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-fw"></i>
                        <span class="badge rounded-pill bg-danger"><?php echo $pending_permits; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="alertsDropdown">
                        <h6 class="dropdown-header">Notifications</h6>
                        <?php if ($pending_permits > 0): ?>
                            <li><a class="dropdown-item" href="permits.php"><?php echo $pending_permits; ?> pending permit requests</a></li>
                        <?php else: ?>
                            <li><span class="dropdown-item text-center small text-gray-500">No new notifications</span></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small text-primary" href="audit.php">Show All</a></li>
                    </ul>
                </li>

                <div class="topbar-divider d-none d-sm-block"></div>

                <!-- User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo !empty($_SESSION['profile_image']) ? '../' . $_SESSION['profile_image'] : 'https://via.placeholder.com/32'; ?>" class="rounded-circle border border-2 border-primary" width="32" height="32" alt="Profile">
                        <span class="d-none d-md-inline-block ms-2 text-gray-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog fa-sm fa-fw me-2 text-gray-400"></i> Settings</a></li>
                        <li><a class="dropdown-item" href="audit.php"><i class="fas fa-list fa-sm fa-fw me-2 text-gray-400"></i> Activity Log</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php" onclick="return confirmAction('Are you sure you want to log out?');"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        
        <!-- Page Content Container -->
        <div class="container-fluid fade-in"> 