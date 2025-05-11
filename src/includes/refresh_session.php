<?php

require_once 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>