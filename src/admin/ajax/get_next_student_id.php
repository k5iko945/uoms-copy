<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if admin is logged in
check_permission('admin');

// Generate next student ID
$next_student_id = generate_student_id();

// Return as JSON
echo json_encode(['student_id' => $next_student_id]);