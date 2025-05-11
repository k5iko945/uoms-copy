<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if admin is logged in
check_permission('admin');

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

// Return JSON response
echo json_encode([
    'users' => $user_stats,
    'clearance' => $clearance_stats
]);