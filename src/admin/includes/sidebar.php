<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-primary">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <div class="sidebar-brand-text text-white">
            
        </div>
        <button id="sidebarToggle" class="btn btn-link d-md-none text-white">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-user my-3 text-center">
        <img src="<?php echo !empty($_SESSION['profile_image']) ? '../' . $_SESSION['profile_image'] : 'https://via.placeholder.com/80'; ?>" class="rounded-circle border border-3 border-white" width="80" height="80">
        <div class="text-white mt-2">
            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            <div class="small">Administrator</div>
        </div>
    </div>
    
    <hr class="my-2 bg-light">
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="sidebar-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="sidebar-link <?php echo $current_page == 'audit.php' ? 'active' : ''; ?>" href="audit.php">
                <i class="fas fa-history"></i> Audit
            </a>
        </li>
        <li class="nav-item">
            <a class="sidebar-link <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>" href="accounts.php">
                <i class="fas fa-users"></i> Membership
            </a>
        </li>
        <li class="nav-item">
            <a class="sidebar-link <?php echo $current_page == 'compliance.php' ? 'active' : ''; ?>" href="compliance.php">
                <i class="fas fa-file-contract"></i> Compliance
            </a>
        </li>
        <li class="nav-item">

        </li>
    </ul>
</div> 