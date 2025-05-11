<!-- logout.php -->
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log the logout event if user was logged in
if (isset($_SESSION['user_id'])) {
    log_audit_event($_SESSION['user_id'], 'Logout', 'User logged out');
}

// Perform logout
logout();
?>