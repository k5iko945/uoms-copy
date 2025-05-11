<!-- Add the permits link to the sidebar after dashboard -->

<!-- Dashboard -->
<li class="nav-item">
    <a class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
</li>

<!-- Permits -->
<li class="nav-item">
    <a class="sidebar-link <?php echo $current_page == 'permits.php' ? 'active' : ''; ?>" href="permits.php">
        <i class="fas fa-id-card"></i> Permits
    </a>
</li> 