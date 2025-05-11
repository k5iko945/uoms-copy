<?php
require_once 'config.php';

// Clean user inputs
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Enhanced redirect function with flash messages for PRG pattern
function redirect_with_message($url, $message_type = null, $message = null) {
    if ($message_type && $message) {
        $_SESSION[$message_type . '_message'] = $message;
    }
    header("Location: " . $url);
    exit();
}

// Get and clear flash message
function get_flash_message($message_type) {
    $message = isset($_SESSION[$message_type . '_message']) ? $_SESSION[$message_type . '_message'] : null;
    unset($_SESSION[$message_type . '_message']);
    return $message;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if current user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Function to check permissions
function check_permission($required_role) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Add cache control headers
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Check if user is logged in
    if (!is_logged_in()) {
        $_SESSION['error_message'] = 'You’re logged out. Please log in again.';
        redirect(BASE_URL . 'login.php');
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity'])) {  // Added closing parenthesis here
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > 1800) { // 30 minutes in seconds
        session_unset();
        session_destroy();
        $_SESSION['error_message'] = 'Your session has expired due to inactivity.';
        redirect(BASE_URL . 'login.php');
    }
}
    $_SESSION['last_activity'] = time(); // Update last activity time
    
    // Check role permissions
    if ($required_role == 'admin' && !is_admin()) {
        $_SESSION['error_message'] = 'You do not have permission to access this page.';
        redirect(BASE_URL . 'user/dashboard.php');
    }
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Get a system setting by key
function get_setting($key, $default = null) {
    global $conn;
    
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['setting_value'];
    }
    
    return $default;
}

// Update a system setting
function update_setting($key, $value) {
    global $conn;
    
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    
    $sql = "UPDATE settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
    return mysqli_query($conn, $sql);
}

// Safe audit logging function
function log_audit_event($user_id, $action, $description, $ip = null) {
    global $conn;
    
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // If user_id is provided, verify it exists in the database
    if ($user_id !== null) {
        $check_sql = "SELECT id FROM users WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // User exists, insert with user_id
            $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "isss", $user_id, $action, $description, $ip);
        } else {
            // User doesn't exist, insert without user_id
            $log_sql = "INSERT INTO audit_log (action, description, ip_address) VALUES (?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "sss", $action, $description, $ip);
        }
    } else {
        // No user_id provided, insert without it
        $log_sql = "INSERT INTO audit_log (action, description, ip_address) VALUES (?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "sss", $action, $description, $ip);
    }
    
    return mysqli_stmt_execute($log_stmt);
}

// Get financial transactions with optional filters
function get_financial_transactions($filters = []) {
    global $conn;
    
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Build where clauses and parameters based on filters
    if (!empty($filters['student_id'])) {
        $where_clauses[] = "u.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "s";
    }
    
    if (!empty($filters['transaction_type'])) {
        $where_clauses[] = "ft.transaction_type = ?";
        $params[] = $filters['transaction_type'];
        $types .= "s";
    }
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where_clauses[] = "DATE(ft.created_at) BETWEEN ? AND ?";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $types .= "ss";
    } elseif (!empty($filters['start_date'])) {
        $where_clauses[] = "DATE(ft.created_at) >= ?";
        $params[] = $filters['start_date'];
        $types .= "s";
    } elseif (!empty($filters['end_date'])) {
        $where_clauses[] = "DATE(ft.created_at) <= ?";
        $params[] = $filters['end_date'];
        $types .= "s";
    }
    
    // Create WHERE clause string
    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Prepare SQL statement
    $sql = "SELECT ft.*, 
            u.student_id, u.first_name, u.last_name,
            a.first_name as admin_first_name, a.last_name as admin_last_name
            FROM financial_transactions ft
            LEFT JOIN users u ON ft.user_id = u.id
            LEFT JOIN users a ON ft.admin_id = a.id
            $where_sql
            ORDER BY ft.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    // Bind parameters if any
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

// Get expenses with optional filters
function get_expenses($filters = []) {
    global $conn;
    
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Build where clauses and parameters based on filters
    if (!empty($filters['category'])) {
        $where_clauses[] = "e.category = ?";
        $params[] = $filters['category'];
        $types .= "s";
    }
    
    if (!empty($filters['department'])) {
        $where_clauses[] = "e.department = ?";
        $params[] = $filters['department'];
        $types .= "s";
    }
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where_clauses[] = "e.expense_date BETWEEN ? AND ?";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
        $types .= "ss";
    } elseif (!empty($filters['start_date'])) {
        $where_clauses[] = "e.expense_date >= ?";
        $params[] = $filters['start_date'];
        $types .= "s";
    } elseif (!empty($filters['end_date'])) {
        $where_clauses[] = "e.expense_date <= ?";
        $params[] = $filters['end_date'];
        $types .= "s";
    }
    
    // Create WHERE clause string
    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Prepare SQL statement
    $sql = "SELECT e.*, u.first_name, u.last_name
            FROM expenses e
            LEFT JOIN users u ON e.admin_id = u.id
            $where_sql
            ORDER BY e.expense_date DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    // Bind parameters if any
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $expenses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $expenses[] = $row;
    }
    
    return $expenses;
}

// Add a new expense
function add_expense($admin_id, $amount, $category, $department, $description, $expense_date, $receipt_image = null) {
    global $conn;
    
    $sql = "INSERT INTO expenses (admin_id, amount, category, department, description, expense_date, receipt_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "idssss", $admin_id, $amount, $category, $department, $description, $expense_date, $receipt_image);
    
    $success = mysqli_stmt_execute($stmt);
    
    if ($success) {
        // Log audit event
        log_audit_event($admin_id, 'add_expense', "Added expense of $amount for $category");
        return mysqli_insert_id($conn);
    }
    
    return false;
}

// Record a financial transaction
function record_financial_transaction($user_id, $admin_id, $amount, $transaction_type, $reference_id = null, $reference_type = null, $description = null, $receipt_image = null) {
    global $conn;
    
    // Convert empty string to NULL for user_id to avoid foreign key constraint issues
    if ($user_id === '') {
        $user_id = null;
    }
    
    $sql = "INSERT INTO financial_transactions (user_id, admin_id, amount, transaction_type, reference_id, reference_type, description, receipt_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iidsisss", $user_id, $admin_id, $amount, $transaction_type, $reference_id, $reference_type, $description, $receipt_image);
    
    $success = mysqli_stmt_execute($stmt);
    
    if ($success) {
        // Log audit event
        $log_message = "Recorded $transaction_type transaction of $amount";
        if ($user_id) {
            $log_message .= " for user ID $user_id";
        }
        log_audit_event($admin_id, 'record_transaction', $log_message);
        return mysqli_insert_id($conn);
    }
    
    return false;
}

/**
 * Generate an Excel/CSV file as a fallback when PHPSpreadsheet is not available
 * 
 * @param array $data Array of data rows
 * @param array $headers Array of column headers
 * @param string $filename Filename for the generated file
 * @return string|bool Path to generated file or false on failure
 */
function generate_excel($data, $headers, $filename) {
    try {
        // Try to use PHPSpreadsheet if available
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // PHPSpreadsheet implementation would go here
            // This will be replaced when PHPSpreadsheet is properly installed
            throw new Exception("PHPSpreadsheet implementation pending");
        }
        
        // Fallback to CSV if PHPSpreadsheet not available
        $upload_dir = '../uploads/exports/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Create a unique filename
        $base_name = pathinfo($filename, PATHINFO_FILENAME);
        $csv_filename = $base_name . '_' . date('YmdHis') . '.csv';
        $file_path = $upload_dir . $csv_filename;
        
        // Open file for writing
        $file = fopen($file_path, 'w');
        if (!$file) {
            throw new Exception("Could not open file for writing");
        }
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($file, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        // Close file
        fclose($file);
        
        // Return the path to the file
        return 'uploads/exports/' . $csv_filename;
    } catch (Exception $e) {
        error_log("Excel generation error: " . $e->getMessage());
        return false;
    }
}

// Get summary statistics for audit dashboard
function get_audit_summary($timeframe = 'month') {
    global $conn;
    
    // Determine date range based on timeframe
    $date_condition = "";
    if ($timeframe == 'today') {
        $date_condition = "DATE(created_at) = CURDATE()";
    } elseif ($timeframe == 'week') {
        $date_condition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($timeframe == 'month') {
        $date_condition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
    } elseif ($timeframe == 'year') {
        $date_condition = "YEAR(created_at) = YEAR(CURDATE())";
    }
    
    // Get total income
    $income_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM financial_transactions 
                  WHERE amount > 0 AND $date_condition";
    $income_result = mysqli_query($conn, $income_sql);
    $income_row = mysqli_fetch_assoc($income_result);
    $total_income = $income_row['total'];
    
    // Get total expenses
    $expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                    WHERE $date_condition";
    $expenses_result = mysqli_query($conn, $expenses_sql);
    $expenses_row = mysqli_fetch_assoc($expenses_result);
    $total_expenses = $expenses_row['total'];
    
    // Get transaction counts by type
    $transaction_counts = [];
    $types = ['tuition', 'shop', 'permit', 'fine', 'other'];
    
    foreach ($types as $type) {
        $count_sql = "SELECT COUNT(*) as count FROM financial_transactions 
                     WHERE transaction_type = '$type' AND $date_condition";
        $count_result = mysqli_query($conn, $count_sql);
        $count_row = mysqli_fetch_assoc($count_result);
        $transaction_counts[$type] = $count_row['count'];
    }
    
    return [
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net' => $total_income - $total_expenses,
        'transaction_counts' => $transaction_counts
    ];
}

// Delete a financial transaction
function delete_financial_transaction($transaction_id, $admin_id) {
    global $conn;
    
    // Validate transaction ID
    $transaction_id = intval($transaction_id);
    if (!$transaction_id) {
        return false;
    }
    
    // First get the transaction details for logging
    $sql = "SELECT amount, transaction_type, user_id FROM financial_transactions WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $transaction_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Start transaction to ensure integrity
        mysqli_begin_transaction($conn);
        
        try {
            // Check for foreign key constraints that might prevent deletion
            // If any exist, handle them here
            
            // Delete the transaction
            $delete_sql = "DELETE FROM financial_transactions WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $transaction_id);
            $success = mysqli_stmt_execute($delete_stmt);
            
            if (!$success) {
                // Get the specific MySQL error
                throw new Exception(mysqli_error($conn));
            }
            
            // Log the deletion
            $log_message = "Deleted {$row['transaction_type']} transaction #{$transaction_id} of {$row['amount']}";
            if ($row['user_id']) {
                $log_message .= " for user ID {$row['user_id']}";
            }
            log_audit_event($admin_id, 'delete_transaction', $log_message);
            
            // Commit the transaction
            mysqli_commit($conn);
            return true;
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            
            // Log the error for debugging
            error_log("Error deleting transaction #$transaction_id: " . $e->getMessage());
            return false;
        }
    } else {
        // Transaction not found
        error_log("Transaction #$transaction_id not found for deletion");
        return false;
    }
}

// Delete an expense
function delete_expense($expense_id, $admin_id) {
    global $conn;
    
    // Validate expense ID
    $expense_id = intval($expense_id);
    if (!$expense_id) {
        return false;
    }
    
    // First get the expense details for logging
    $sql = "SELECT amount, category FROM expenses WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $expense_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Start transaction to ensure integrity
        mysqli_begin_transaction($conn);
        
        try {
            // Delete the expense
            $delete_sql = "DELETE FROM expenses WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $expense_id);
            $success = mysqli_stmt_execute($delete_stmt);
            
            if (!$success) {
                // Get the specific MySQL error
                throw new Exception(mysqli_error($conn));
            }
            
            // Log the deletion
            $log_message = "Deleted {$row['category']} expense #{$expense_id} of {$row['amount']}";
            log_audit_event($admin_id, 'delete_expense', $log_message);
            
            // Commit the transaction
            mysqli_commit($conn);
            return true;
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            
            // Log the error for debugging
            error_log("Error deleting expense #$expense_id: " . $e->getMessage());
            return false;
        }
    } else {
        // Expense not found
        error_log("Expense #$expense_id not found for deletion");
        return false;
    }
}

// Delete audit log entry (administrative function)
function delete_audit_log($log_id, $admin_id) {
    global $conn;
    
    // Validate log ID
    $log_id = intval($log_id);
    if (!$log_id) {
        return false;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the audit log entry
        $sql = "DELETE FROM audit_log WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $log_id);
        $success = mysqli_stmt_execute($stmt);
        
        if (!$success) {
            // Get the specific MySQL error
            throw new Exception(mysqli_error($conn));
        }
        
        // Log the deletion (but don't create infinite loop)
        $log_message = "Deleted audit log entry #{$log_id}";
        log_audit_event($admin_id, 'delete_audit_log', $log_message);
        
        // Commit the transaction
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        // Log the error for debugging
        error_log("Error deleting audit log #$log_id: " . $e->getMessage());
        return false;
    }
}

// Generate a new student ID
function generate_student_id() {
    global $conn;
    
    // Get current year
    $year = date('Y');
    
    // Get the last student number for this year
    $sql = "SELECT student_id FROM users 
            WHERE student_id LIKE ? 
            ORDER BY student_id DESC 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    $year_pattern = $year . '%';
    mysqli_stmt_bind_param($stmt, "s", $year_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Extract the sequence number from the last student ID
        $last_sequence = intval(substr($row['student_id'], -3));
        $new_sequence = $last_sequence + 1;
    } else {
        // No students yet for this year
        $new_sequence = 1;
    }
    
    // Format the new student ID (YYYY### - padding sequence with zeros)
    $student_id = $year . str_pad($new_sequence, 3, '0', STR_PAD_LEFT);
    
    return $student_id;
}

// Generate role-based ID
function generate_role_based_id($role) {
    global $conn;
    
    switch(strtolower($role)) {
        case 'admin':
            return generate_admin_id();
        case 'staff':
            return generate_staff_id();
        default:
            return generate_student_id();
    }
}

function generate_admin_id() {
    global $conn;
    
    // Get the last admin number
    $sql = "SELECT student_id FROM users 
            WHERE role = 'admin' AND student_id LIKE 'admin%' 
            ORDER BY student_id DESC 
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Extract the number from adminX format
        $last_num = intval(substr($row['student_id'], 5));
        $new_num = $last_num + 1;
    } else {
        // First admin
        $new_num = 1;
    }
    
    return 'admin' . $new_num;
}

function generate_staff_id() {
    global $conn;
    
    // Get the last staff number
    $sql = "SELECT student_id FROM users 
            WHERE role = 'staff' AND student_id LIKE 'staff%' 
            ORDER BY student_id DESC 
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Extract the number from staffX format
        $last_num = intval(substr($row['student_id'], 5));
        $new_num = $last_num + 1;
    } else {
        // First staff
        $new_num = 1;
    }
    
    return 'staff' . $new_num;
}

// Get default avatar based on role
function get_default_avatar($role) {
    switch(strtolower($role)) {
        case 'admin':
            return 'assets/images/admin-avatar.jpg';
        case 'staff':
            return 'assets/images/staff-avatar.jpg';
        default:
            return 'assets/images/user-avatar.jpg';
    }
}
function logout() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page with cache control headers
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: " . BASE_URL . 'login.php');
    exit();
}
?>