<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if user_id is provided
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// Clean input
$user_id = clean_input($_POST['user_id']);

// Fetch user details from the database
$sql = "SELECT 
            department,
            date_of_birth,
            address,
            phone_number,
            gender,
            nationality,
            emergency_contact_name,
            emergency_contact_phone,
            emergency_contact_relation
        FROM users
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Format date of birth if not null
    if ($row['date_of_birth']) {
        $row['date_of_birth'] = date('F j, Y', strtotime($row['date_of_birth']));
    }
    
    // Send response
    header('Content-Type: application/json');
    echo json_encode($row);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
} 