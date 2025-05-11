<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uom_system');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set
mysqli_set_charset($conn, "utf8");

// Session start
session_start();

// Base URL (modify as needed)
define('BASE_URL', 'http://localhost:8081/uoms/src/');
?> 