<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in, redirect if not
check_permission('admin');

// Redirect to accounts.php with tab=students parameter
header('Location: accounts.php?tab=students');
exit;
?> 