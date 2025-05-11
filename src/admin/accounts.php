<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in, redirect if not
check_permission('admin');

// Get error or success messages
$error = get_flash_message('error');
$success = get_flash_message('success');

// Set page variables
$page_title = 'Account Management';
$use_datatables = true;

// Get all users
$sql = "SELECT * FROM users ORDER BY id ASC";
$result = mysqli_query($conn, $sql);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// Check for accounts with potentially invalid roles
$invalid_roles = [];
foreach ($users as $user) {
    if (!in_array(strtolower($user['role']), ['student', 'admin', 'staff'])) {
        $invalid_roles[] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'student_id' => $user['student_id'],
            'role' => $user['role']
        ];
    }
}

// Log invalid roles if found
if (!empty($invalid_roles)) {
    $action = 'Role Validation';
    $description = "Found " . count($invalid_roles) . " accounts with invalid roles: " . json_encode($invalid_roles);
    $ip = $_SERVER['REMOTE_ADDR'];
    $admin_id = $_SESSION['user_id'];
    log_audit_event($admin_id, $action, $description, $ip);
}

// Get user roles for select dropdown
$sql_roles = "SELECT DISTINCT role FROM users";
$result_roles = mysqli_query($conn, $sql_roles);
$roles = [];
while ($row = mysqli_fetch_assoc($result_roles)) {
    $roles[] = $row['role'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new user
    if (isset($_POST['add_user'])) {
        $first_name = clean_input($_POST['first_name']);
        $last_name = clean_input($_POST['last_name']);
        $email = clean_input($_POST['email']);
        $password = password_hash(clean_input($_POST['password']), PASSWORD_DEFAULT);
        $role = clean_input($_POST['role']);
        
        // Generate ID based on role
        $student_id = generate_role_based_id($role);
        
        // Check if email already exists
        $check_email_sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_email_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $email_count = mysqli_fetch_assoc($check_result)['count'];
        
        if ($email_count > 0) {
            redirect_with_message('accounts.php', 'error', "Email '$email' is already in use.");
        } else {
            // Set default avatar based on role
            $default_avatar = 'assets/images/';
            switch($role) {
                case 'admin':
                    $default_avatar .= 'admin-avatar.jpg';
                    break;
                case 'staff':
                    $default_avatar .= 'staff-avatar.jpg';
                    break;
                default:
                    $default_avatar .= 'user-avatar.jpg';
            }

            // Insert new user with default avatar
            $sql = "INSERT INTO users (student_id, first_name, last_name, email, password, role, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", 
                $student_id, 
                $first_name, 
                $last_name, 
                $email, 
                $password, 
                $role,
                $default_avatar
            );
            
            if (mysqli_stmt_execute($stmt)) {
                // Log the action
                $action = 'Add User';
                $description = "Admin added new user: $first_name $last_name ($student_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $admin_id = $_SESSION['user_id'];
                
                log_audit_event($admin_id, $action, $description, $ip);
                
                redirect_with_message('accounts.php', 'success', 
                    "User added successfully! ID: $student_id");
            } else {
                redirect_with_message('accounts.php', 'error', 
                    "Error adding user: " . mysqli_error($conn));
            }
        }
    }
    
    // Update user
    if (isset($_POST['update_user'])) {
        $user_id = clean_input($_POST['user_id']);
        $first_name = clean_input($_POST['first_name']);
        $last_name = clean_input($_POST['last_name']);
        $student_id = clean_input($_POST['student_id']);
        $email = clean_input($_POST['email']);
        $role = clean_input($_POST['role']);
        
        // Check if student ID already exists for other users
        $check_id_sql = "SELECT COUNT(*) as count FROM users WHERE student_id = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_id_sql);
        mysqli_stmt_bind_param($check_stmt, "si", $student_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $id_count = mysqli_fetch_assoc($check_result)['count'];
        
        if ($id_count > 0) {
            redirect_with_message('accounts.php', 'error', "Student ID '$student_id' is already in use. Please use a different ID.");
        } else {
            // Check if email already exists for other users
            $check_email_sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_email_sql);
            mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $email_count = mysqli_fetch_assoc($check_result)['count'];
            
            if ($email_count > 0) {
                redirect_with_message('accounts.php', 'error', "Email '$email' is already in use. Please use a different email.");
            } else {
                // Start with base SQL and parameters
                $sql_parts = ["UPDATE users SET first_name = ?, last_name = ?, student_id = ?, email = ?, role = ?"];
                $param_types = "sssss";
                $param_values = [$first_name, $last_name, $student_id, $email, $role];
                
                // Handle password update (only if provided)
                if (!empty($_POST['password'])) {
                    $password = password_hash(clean_input($_POST['password']), PASSWORD_DEFAULT);
                    $sql_parts[] = "password = ?";
                    $param_types .= "s";
                    $param_values[] = $password;
                }
                
                // Handle profile image update
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/profiles/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = $student_id . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
                    $target_path = $upload_dir . $filename;
                    
                    $file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                    
                    // Check if image file is an actual image
                    $check = getimagesize($_FILES['profile_image']['tmp_name']);
                    if ($check === false) {
                        redirect_with_message('accounts.php', 'error', "File is not an image.");
                    }
                    
                    // Check file size (limit to 5MB)
                    else if ($_FILES['profile_image']['size'] > 5000000) {
                        redirect_with_message('accounts.php', 'error', "File is too large. Maximum size is 5MB.");
                    }
                    
                    // Allow certain file formats
                    else if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        redirect_with_message('accounts.php', 'error', "Only JPG, JPEG, PNG & GIF files are allowed.");
                    }
                    
                    // If no errors, try to upload
                    else if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                        // Get old image path to delete after update
                        $get_image_sql = "SELECT profile_image FROM users WHERE id = ?";
                        $get_image_stmt = mysqli_prepare($conn, $get_image_sql);
                        mysqli_stmt_bind_param($get_image_stmt, "i", $user_id);
                        mysqli_stmt_execute($get_image_stmt);
                        $get_image_result = mysqli_stmt_get_result($get_image_stmt);
                        $old_image_path = mysqli_fetch_assoc($get_image_result)['profile_image'];
                        
                        // Add image path to SQL update
                        $image_path = 'uploads/profiles/' . $filename;
                        $sql_parts[] = "profile_image = ?";
                        $param_types .= "s";
                        $param_values[] = $image_path;
                    } else {
                        redirect_with_message('accounts.php', 'error', "Error uploading file.");
                    }
                }
                
                // Finalize SQL query
                $sql = implode(", ", $sql_parts) . " WHERE id = ?";
                $param_types .= "i";
                $param_values[] = $user_id;
                
                // Execute update
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, $param_types, ...$param_values);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Delete old profile image if it was updated
                    if (isset($old_image_path) && !empty($old_image_path) && isset($image_path) && file_exists('../' . $old_image_path)) {
                        unlink('../' . $old_image_path);
                    }
                    
                    // Log the action
                    $action = 'User Update';
                    $description = "Admin updated user: $first_name $last_name ($student_id)";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $admin_id = $_SESSION['user_id'];
                    
                    $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $description, $ip);
                    mysqli_stmt_execute($log_stmt);
                    
                    redirect_with_message('accounts.php', 'success', "User updated successfully!");
                } else {
                    redirect_with_message('accounts.php', 'error', "Error updating user: " . mysqli_error($conn));
                }
            }
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = clean_input($_POST['user_id']);
        
        // Check if user exists
        $check_sql = "SELECT first_name, last_name, student_id, profile_image FROM users WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if ($user = mysqli_fetch_assoc($check_result)) {
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Delete profile image if exists
                if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])) {
                    unlink('../' . $user['profile_image']);
                }
                
                // Log the action
                $action = 'User Deletion';
                $description = "Admin deleted user: {$user['first_name']} {$user['last_name']} ({$user['student_id']})";
                $ip = $_SERVER['REMOTE_ADDR'];
                $admin_id = $_SESSION['user_id'];
                
                $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $description, $ip);
                mysqli_stmt_execute($log_stmt);
                
                redirect_with_message('accounts.php', 'success', "User deleted successfully!");
            } else {
                redirect_with_message('accounts.php', 'error', "Error deleting user: " . mysqli_error($conn));
            }
        } else {
            redirect_with_message('accounts.php', 'error', "User not found.");
        }
    }

    // Handle delete student
    if (isset($_POST['delete_student'])) {
        $student_id = clean_input($_POST['student_id']);
        
        // Get student details for audit log
        $get_sql = "SELECT id, first_name, last_name, student_id FROM users WHERE id = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, "i", $student_id);
        mysqli_stmt_execute($get_stmt);
        $result = mysqli_stmt_get_result($get_stmt);
        $student = mysqli_fetch_assoc($result);
        
        // Delete the student
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            $action = 'Delete Student';
            $description = "Admin deleted student account for {$student['first_name']} {$student['last_name']} ({$student['student_id']})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['user_id'];
            
            log_audit_event($admin_id, $action, $description, $ip);
            
            redirect_with_message('accounts.php', 'success', "Student deleted successfully!");
        } else {
            redirect_with_message('accounts.php', 'error', "Error deleting student: " . mysqli_error($conn));
        }
    }

    // Handle update student status
    if (isset($_POST['update_status'])) {
        $student_id = clean_input($_POST['student_id']);
        $status = clean_input($_POST['status']);
        
        // Get student details for audit log
        $get_sql = "SELECT id, first_name, last_name, student_id FROM users WHERE id = ?";
        $get_stmt = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get_stmt, "i", $student_id);
        mysqli_stmt_execute($get_stmt);
        $result = mysqli_stmt_get_result($get_stmt);
        $student = mysqli_fetch_assoc($result);
        
        // Update the student status
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            $action = 'Update Student Status';
            $description = "Admin updated status to '$status' for {$student['first_name']} {$student['last_name']} ({$student['student_id']})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['user_id'];
            
            log_audit_event($admin_id, $action, $description, $ip);
            
            redirect_with_message('accounts.php', 'success', "Student status updated successfully!");
        } else {
            redirect_with_message('accounts.php', 'error', "Error updating student status: " . mysqli_error($conn));
        }
    }
    
    // PERMIT FORM HANDLING
    // Create new permit
    if (isset($_POST['create_permit'])) {
        $student_ids = $_POST['student_ids'];
        $term = clean_input($_POST['term']);
        $semester = clean_input($_POST['semester']);
        $status = clean_input($_POST['status']);
        $approved_by = clean_input($_POST['approved_by']);
        $approval_date = clean_input($_POST['approval_date']);
        
        // Upload file if provided
        $file_path = null;
        if (isset($_FILES['permit_file']) && $_FILES['permit_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/permits/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . $_FILES['permit_file']['name'];
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['permit_file']['tmp_name'], $upload_path)) {
                $file_path = 'uploads/permits/' . $file_name;
            }
        }
        
        $success_count = 0;
        $error_count = 0;
        
        // Insert permits for each selected student
        foreach ($student_ids as $student_id) {
            $sql = "INSERT INTO student_permits (student_id, term, semester, status, approved_by, approval_date, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", $student_id, $term, $semester, $status, $approved_by, $approval_date, $file_path);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
                // Log the action
                $student_sql = "SELECT first_name, last_name FROM users WHERE student_id = ?";
                $student_stmt = mysqli_prepare($conn, $student_sql);
                mysqli_stmt_bind_param($student_stmt, "s", $student_id);
                mysqli_stmt_execute($student_stmt);
                $student_result = mysqli_stmt_get_result($student_stmt);
                $student_info = mysqli_fetch_assoc($student_result);
                
                $action = 'Permit Creation';
                $description = "Admin created a new $status permit for {$student_info['first_name']} {$student_info['last_name']} ($student_id) for $term $semester";
                $ip = $_SERVER['REMOTE_ADDR'];
                $admin_id = $_SESSION['user_id'];
                
                log_audit_event($admin_id, $action, $description, $ip);
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully created permits for $success_count student(s)";
            if ($error_count > 0) {
                $message .= ", failed for $error_count student(s)";
            }
            redirect_with_message('accounts.php?tab=permits', 'success', $message);
        } else {
            redirect_with_message('accounts.php?tab=permits', 'error', "Error creating permits: " . mysqli_error($conn));
        }
    }
    
    // Update permit status
    if (isset($_POST['update_permit'])) {
        $permit_id = clean_input($_POST['permit_id']);
        $status = clean_input($_POST['status']);
        $approved_by = clean_input($_POST['approved_by']);
        $approval_date = clean_input($_POST['approval_date']);
        
        $sql = "UPDATE student_permits SET status = ?, approved_by = ?, approval_date = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $status, $approved_by, $approval_date, $permit_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get permit info for audit log
            $sql = "SELECT sp.*, u.first_name, u.last_name 
                    FROM student_permits sp 
                    JOIN users u ON sp.student_id = u.student_id 
                    WHERE sp.id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $permit_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $permit_info = mysqli_fetch_assoc($result);
            
            // Log the action
            $action = 'Permit Update';
            $description = "Admin updated permit status to '$status' for {$permit_info['first_name']} {$permit_info['last_name']} ({$permit_info['student_id']})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['user_id'];
            
            log_audit_event($admin_id, $action, $description, $ip);
            
            redirect_with_message('accounts.php?tab=permits', 'success', "Permit updated successfully!");
        } else {
            redirect_with_message('accounts.php?tab=permits', 'error', "Error updating permit: " . mysqli_error($conn));
        }
    }
    
    // Delete permit
    if (isset($_POST['delete_permit'])) {
        $permit_id = clean_input($_POST['permit_id']);
        
        // Get permit info for audit log before deletion
        $sql = "SELECT sp.*, u.first_name, u.last_name 
                FROM student_permits sp 
                JOIN users u ON sp.student_id = u.student_id 
                WHERE sp.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $permit_info = mysqli_fetch_assoc($result);
        
        // Delete the permit
        $sql = "DELETE FROM student_permits WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            $action = 'Permit Deletion';
            $description = "Admin deleted {$permit_info['term']} {$permit_info['semester']} permit for {$permit_info['first_name']} {$permit_info['last_name']} ({$permit_info['student_id']})";
            $ip = $_SERVER['REMOTE_ADDR'];
            $admin_id = $_SESSION['user_id'];
            
            log_audit_event($admin_id, $action, $description, $ip);
            
            redirect_with_message('accounts.php?tab=permits', 'success', "Permit deleted successfully!");
        } else {
            redirect_with_message('accounts.php?tab=permits', 'error', "Error deleting permit: " . mysqli_error($conn));
        }
    }
    
    // Fix invalid roles
    if (isset($_POST['fix_roles'])) {
        $fixed_count = 0;
        
        // Find accounts with invalid roles and fix them
        foreach ($users as $user) {
            if (!in_array(strtolower($user['role']), ['student', 'admin', 'staff'])) {
                $sql = "UPDATE users SET role = 'student' WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user['id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $fixed_count++;
                    
                    // Log the action
                    $action = 'Fix Invalid Role';
                    $description = "Admin fixed invalid role '{$user['role']}' to 'student' for {$user['first_name']} {$user['last_name']} ({$user['student_id']})";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $admin_id = $_SESSION['user_id'];
                    
                    log_audit_event($admin_id, $action, $description, $ip);
                }
            }
        }
        
        if ($fixed_count > 0) {
            redirect_with_message('accounts.php', 'success', "Fixed roles for $fixed_count account(s).");
        } else {
            redirect_with_message('accounts.php', 'info', "No invalid roles found to fix.");
        }
    }
    
    // LEDGER FORM HANDLING
    // Add a new ledger entry
    if (isset($_POST['add_ledger'])) {
        $user_id = clean_input($_POST['user_id']);
        $semester = clean_input($_POST['semester']);
        $amount = clean_input($_POST['amount']);
        $description = clean_input($_POST['description']);
        $status = clean_input($_POST['status']);
        
        $sql = "INSERT INTO ledger (user_id, semester, amount, description, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isdss", $user_id, $semester, $amount, $description, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            redirect_with_message('accounts.php?tab=ledger', 'success', "Ledger entry added successfully!");
        } else {
            redirect_with_message('accounts.php?tab=ledger', 'error', "Error adding ledger entry: " . mysqli_error($conn));
        }
    }
    
    // Update ledger entry
    if (isset($_POST['update_ledger'])) {
        $ledger_id = clean_input($_POST['ledger_id']);
        $semester = clean_input($_POST['semester']);
        $amount = clean_input($_POST['amount']);
        $description = clean_input($_POST['description']);
        $status = clean_input($_POST['status']);
        
        $sql = "UPDATE ledger SET semester = ?, amount = ?, description = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sdssi", $semester, $amount, $description, $status, $ledger_id);
        
        if (mysqli_stmt_execute($stmt)) {
            redirect_with_message('accounts.php?tab=ledger', 'success', "Ledger entry updated successfully!");
        } else {
            redirect_with_message('accounts.php?tab=ledger', 'error', "Error updating ledger entry: " . mysqli_error($conn));
        }
    }
    
    // Delete ledger entry
    if (isset($_POST['delete_ledger'])) {
        $ledger_id = clean_input($_POST['ledger_id']);
        
        $sql = "DELETE FROM ledger WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $ledger_id);
        
        if (mysqli_stmt_execute($stmt)) {
            redirect_with_message('accounts.php?tab=ledger', 'success', "Ledger entry deleted successfully!");
        } else {
            redirect_with_message('accounts.php?tab=ledger', 'error', "Error deleting ledger entry: " . mysqli_error($conn));
        }
    }
}

// Page specific JS
$page_specific_js = <<<EOT
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#usersTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
        
        $('#studentsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
        
        $('#permitsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
        
        $('#ledgerTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
        
        // Role filter for users table
        $('#roleFilter').on('change', function() {
            let usersTable = $('#usersTable').DataTable();
            let selectedRole = $(this).val();
            
            // Custom filtering function for roles
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                // Only apply to users table
                if (settings.nTable.id !== 'usersTable') {
                    return true;
                }
                
                // If no role selected, show all
                if (selectedRole === '') {
                    return true;
                }
                
                // Column 5 contains the role information (check HTML table structure)
                let role = data[5].toLowerCase();
                
                // Check if the row contains the selected role
                // We need to check the text content of the badges
                if (selectedRole === 'admin' && role.includes('admin')) {
                    return true;
                } else if (selectedRole === 'staff' && role.includes('staff')) {
                    return true;
                } else if (selectedRole === 'student' && role.includes('student')) {
                    return true;
                }
                
                return false;
            });
            
            // Redraw the table
            usersTable.draw();
            
            // Remove the custom search function to avoid stacking
            $.fn.dataTable.ext.search.pop();
        });
        
        // Form validation
        $('.needs-validation').each(function() {
            $(this).on('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                $(this).addClass('was-validated');
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
        
        // Edit user modal
        $('.edit-user').click(function() {
            $('#edit_user_id').val($(this).data('id'));
            $('#edit_first_name').val($(this).data('first-name'));
            $('#edit_last_name').val($(this).data('last-name'));
            $('#edit_student_id').val($(this).data('student-id'));
            $('#edit_email').val($(this).data('email'));
            $('#edit_role').val($(this).data('role'));
        });
        
        // Delete user modal
        $('.delete-user').click(function() {
            $('#delete_user_id').val($(this).data('id'));
            $('#delete_user_name').text($(this).data('name'));
        });
        
        // View user modal
        $('.view-user').click(function() {
            const profileImage = $(this).data('profile');
            const role = $(this).data('role');
            let defaultImage;
            
            switch(role) {
                case 'admin':
                    defaultImage = '../assets/images/admin-avatar.jpg';
                    break;
                case 'staff':
                    defaultImage = '../assets/images/staff-avatar.jpg';
                    break;
                default:
                    defaultImage = '../assets/images/user-avatar.jpg';
            }
            
            $('#view_profile_image').attr('src', profileImage || defaultImage);
            $('#view_user_name').text($(this).data('first-name') + ' ' + $(this).data('last-name'));
            
            let roleText = 'Student';
            let roleBadgeClass = 'role-badge-student';
            let roleIcon = '';
            
            if (role === 'admin') {
                roleText = 'Administrator';
                roleBadgeClass = 'role-badge-admin';
                roleIcon = '<i class="fas fa-shield-alt me-1"></i> ';
            } else if (role === 'staff') {
                roleText = 'Staff';
                roleBadgeClass = 'role-badge-staff';
                roleIcon = '<i class="fas fa-briefcase me-1"></i> ';
            } else {
                roleIcon = '<i class="fas fa-user-graduate me-1"></i> ';
            }
            
            $('#view_user_role').html(roleIcon + roleText).removeClass().addClass('badge role-badge ' + roleBadgeClass + ' fs-6 py-2 px-3 rounded-pill');
            $('#view_student_id').text($(this).data('student-id'));
            $('#view_email').text($(this).data('email'));
            $('#view_date').text($(this).data('date'));
            
            // Fetch additional user information from the database
            const userId = $(this).data('id');
            $.ajax({
                url: 'ajax/get_user_details.php',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(data) {
                    // Populate department information
                    $('#view_department').text(data.department || 'Not assigned');
                    
                    // Populate personal information
                    $('#view_date_of_birth').text(data.date_of_birth ? data.date_of_birth : 'Not provided');
                    $('#view_gender').text(data.gender || 'Not provided');
                    $('#view_phone_number').text(data.phone_number || 'Not provided');
                    $('#view_nationality').text(data.nationality || 'Not provided');
                    $('#view_address').text(data.address || 'Not provided');
                    
                    // Populate emergency contact information
                    $('#view_emergency_contact_name').text(data.emergency_contact_name || 'Not provided');
                    $('#view_emergency_contact_phone').text(data.emergency_contact_phone || 'Not provided');
                    $('#view_emergency_contact_relation').text(data.emergency_contact_relation || 'Not provided');
                },
                error: function() {
                    console.error('Failed to load user details');
                    // Set default values if AJAX fails
                    $('#view_department').text('Not available');
                    $('#view_date_of_birth, #view_gender, #view_phone_number, #view_nationality, #view_address').text('Not available');
                    $('#view_emergency_contact_name, #view_emergency_contact_phone, #view_emergency_contact_relation').text('Not available');
                }
            });
            
            // Store user ID for edit button
            $('.edit-from-view').data('user-id', $(this).data('id'));
        });
        
        // Edit from view
        $('.edit-from-view').click(function() {
            const userId = $(this).data('user-id');
            $('#viewUserModal').modal('hide');
            
            // Find the edit button with the same user ID and trigger click
            setTimeout(function() {
                $('.edit-user[data-id="' + userId + '"]').click();
            }, 500);
        });

        // Handle tab parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        // If tab parameter exists, activate the corresponding tab
        if (tab === 'students') {
            $('#students-tab').tab('show');
        } else if (tab === 'permits') {
            $('#permits-tab').tab('show');
        } else if (tab === 'ledger') {
            $('#ledger-tab').tab('show');
        }
        
        // Permits functionality
        // Filter functionality for permits
        $('#apply_filter').click(function() {
            let permitTable = $('#permitsTable').DataTable();
            permitTable.columns(2).search($('#filter_semester').val()); // Semester column
            permitTable.columns(3).search($('#filter_term').val());     // Term column
            permitTable.columns(4).search($('#filter_status').val());   // Status column
            permitTable.draw();
        });
        
        $('#reset_filter').click(function() {
            $('#filter_semester, #filter_term, #filter_status').val('');
            let permitTable = $('#permitsTable').DataTable();
            permitTable.columns().search('').draw();
        });
        
        // View permit modal
        $('.view-permit').click(function() {
            // Set student and permit info
            $('#view_student').text($(this).data('student'));
            $('#view_student_id').text($(this).data('student-id'));
            $('#view_email').text($(this).data('email'));
            $('#view_semester').text($(this).data('semester'));
            $('#view_term').text($(this).data('term'));
            $('#view_approved_by').text($(this).data('approved-by'));
            $('#view_approval_date').text($(this).data('approval-date'));
            
            // Set permit card values
            $('#view_card_term').text($(this).data('term'));
            $('#view_card_semester').text($(this).data('semester'));
            $('#view_card_name').text($(this).data('student'));
            $('#view_card_id').text($(this).data('student-id'));
            $('#view_card_approver').text($(this).data('approved-by'));
            $('#view_card_date').text($(this).data('approval-date'));
            
            const status = $(this).data('status');
            let statusHtml = '';
            
            if (status === 'Allowed') {
                statusHtml = '<span class="badge bg-success">Allowed</span>';
                $('#view_permit_card').removeClass('disallowed').addClass('allowed');
                $('#view_card_status_badge').removeClass('bg-danger').addClass('bg-success').text('Allowed');
            } else {
                statusHtml = '<span class="badge bg-danger">Disallowed</span>';
                $('#view_permit_card').removeClass('allowed').addClass('disallowed');
                $('#view_card_status_badge').removeClass('bg-success').addClass('bg-danger').text('Disallowed');
            }
            
            $('#view_status').html(statusHtml);
        });
        
        // Edit permit modal
        $('.edit-permit').click(function() {
            $('#edit_permit_id').val($(this).data('id'));
            $('#edit_student').val($(this).data('student'));
            $('#edit_student_id').val($(this).data('student-id'));
            $('#edit_semester').val($(this).data('semester'));
            $('#edit_term').val($(this).data('term'));
            $('#edit_status').val($(this).data('status'));
            $('#edit_approved_by').val($(this).data('approved-by'));
            $('#edit_approval_date').val($(this).data('approval-date'));
        });
        
        // Delete permit modal
        $('.delete-permit').click(function() {
            $('#delete_permit_id').val($(this).data('id'));
            $('#delete_student').text($(this).data('student'));
        });
        
        // Ledger functionality
        // Edit ledger modal
        $('.edit-ledger').click(function() {
            $('#edit_ledger_id').val($(this).data('id'));
            $('#edit_ledger_semester').val($(this).data('semester'));
            $('#edit_amount').val($(this).data('amount'));
            $('#edit_description').val($(this).data('description'));
            $('#edit_ledger_status').val($(this).data('status'));
        });
        
        // Delete ledger modal
        $('.delete-ledger').click(function() {
            $('#delete_ledger_id').val($(this).data('id'));
            $('#delete_student_name').text($(this).data('student'));
        });

        // When role selection changes
        $('#role').on('change', function() {
            const selectedRole = $(this).val();
            
            // Only show next ID preview for student role
            if (selectedRole === 'student') {
                $.ajax({
                    url: 'ajax/get_next_student_id.php',
                    method: 'GET',
                    success: function(response) {
                        const data = JSON.parse(response);
                        $('#next_id_preview').text('Next ID: ' + data.student_id);
                    }
                });
            } else if (selectedRole === 'admin') {
                $.ajax({
                    url: 'ajax/get_next_admin_id.php',
                    method: 'GET',
                    success: function(response) {
                        const data = JSON.parse(response);
                        $('#next_id_preview').text('Next ID: ' + data.admin_id);
                    }
                });
            } else if (selectedRole === 'staff') {
                $.ajax({
                    url: 'ajax/get_next_staff_id.php',
                    method: 'GET',
                    success: function(response) {
                        const data = JSON.parse(response);
                        $('#next_id_preview').text('Next ID: ' + data.staff_id);
                    }
                });
            }
        });
    });
</script>
EOT;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon.ico" type="image/x-icon">

    <title>User Accounts Management | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        /* Permit Card Styles */
        .permit-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .permit-card.allowed {
            border-left: 5px solid var(--success-color, #28a745);
        }
        .permit-card.disallowed {
            border-left: 5px solid var(--danger-color, #dc3545);
        }
        .permit-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .permit-status {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .permit-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            opacity: 0.04;
            pointer-events: none;
            font-weight: bold;
            white-space: nowrap;
        }
        
        /* Role Badge Styles */
        .role-badge {
            display: inline-flex;
            align-items: center;
            font-weight: 600;
        }
        
        .role-badge-admin {
            background: linear-gradient(135deg, #ff5252, #b33939);
            box-shadow: 0 2px 5px rgba(179, 57, 57, 0.3);
        }
        
        .role-badge-staff {
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 2px 5px rgba(41, 128, 185, 0.3);
        }
        
        .role-badge-student {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            box-shadow: 0 2px 5px rgba(46, 204, 113, 0.3);
        }
        
        /* User Details Tabs */
        .user-details-tabs {
            border-bottom: 0;
        }
        
        .user-details-tabs .nav-link {
            border-radius: 0.5rem 0.5rem 0 0;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
        }
        
        .user-details-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        
        /* User Info Section */
        .tab-content {
            background-color: #f8f9fa;
        }
        
        #viewUserModal .modal-body {
            padding: 1.5rem;
        }
        
        #viewUserModal .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php include 'includes/header.php'; ?>
                
                <div class="container-fluid p-0">
                    <!-- Page heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Account Management</h1>
                        <div>
                            <?php if (!empty($invalid_roles)): ?>
                            <form method="POST" action="accounts.php" class="d-inline-block">
                                <button type="submit" name="fix_roles" class="btn btn-sm btn-warning me-2">
                                    <i class="fas fa-sync me-1"></i> Fix Invalid Roles (<?php echo count($invalid_roles); ?>)
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="accountTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-accounts-tab" data-bs-toggle="tab" data-bs-target="#all-accounts" type="button" role="tab" aria-controls="all-accounts" aria-selected="true">
                                <i class="fas fa-users me-1"></i> All Accounts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="false">
                                <i class="fas fa-hourglass-half me-1"></i> Pending Students
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="permits-tab" data-bs-toggle="tab" data-bs-target="#permits" type="button" role="tab" aria-controls="permits" aria-selected="false">
                                <i class="fas fa-user-check me-1"></i> Approved Students
                            </button>
                        </li>
                        <!-- <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab" aria-controls="ledger" aria-selected="false">
                                <i class="fas fa-money-bill-wave me-1"></i> Ledger
                            </button>
                        </li> -->
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="accountTabsContent">
                        <!-- ALL ACCOUNTS TAB -->
                        <div class="tab-pane fade show active" id="all-accounts" role="tabpanel" aria-labelledby="all-accounts-tab">
                            <!-- All Users Table -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">User Accounts</h6>
                                    <div>
                                        <select id="roleFilter" class="form-select form-select-sm d-inline-block me-2" style="width: auto;">
                                            <option value="">All Roles</option>
                                            <option value="student">Students</option>
                                            <option value="admin">Admins</option>
                                            <option value="staff">Staff</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                            <i class="fas fa-plus me-1"></i> Add New User
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($users)): ?>
                                        <p class="text-center text-muted my-5">No user accounts found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>No.</th>
                                                        <th>Profile</th>
                                                        <th>Name</th>
                                                        <th>ID</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Joined Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($users as $user): ?>
                                                        <tr>
                                                            <td><?php echo $user['id']; ?></td>
                                                            <td class="text-center">
                                                                <img src="<?php echo '../' . (!empty($user['profile_image']) ? 
                                                                    $user['profile_image'] : 
                                                                    get_default_avatar($user['role'])); ?>" 
                                                                    class="rounded-circle" width="40" height="40" 
                                                                    alt="Profile Image">
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                            <td>
                                                                <?php if ($user['role'] == 'admin'): ?>
                                                                    <span class="badge bg-primary">Admin</span>
                                                                <?php elseif ($user['role'] == 'staff'): ?>
                                                                    <span class="badge bg-info">Staff</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Student</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-sm btn-primary edit-user hover-scale"
                                                                        data-id="<?php echo $user['id']; ?>"
                                                                        data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                                        data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                                                        data-student-id="<?php echo htmlspecialchars($user['student_id']); ?>"
                                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                        data-role="<?php echo $user['role']; ?>"
                                                                        data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-info view-user hover-scale"
                                                                        data-id="<?php echo $user['id']; ?>"
                                                                        data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                                        data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                                                        data-student-id="<?php echo htmlspecialchars($user['student_id']); ?>"
                                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                        data-role="<?php echo $user['role']; ?>"
                                                                        data-profile="<?php echo !empty($user['profile_image']) ? 
                                                                            '../' . $user['profile_image'] : 
                                                                            '../' . get_default_avatar($user['role']); ?>"
                                                                        data-date="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>"
                                                                        data-department="<?php echo htmlspecialchars($user['department'] ?? ''); ?>"
                                                                        data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-user hover-scale"
                                                                        data-id="<?php echo $user['id']; ?>"
                                                                        data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- STUDENTS TAB -->
                        <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                            <!-- Students Table -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Accounts</h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#studentFilterModal">
                                            <i class="fas fa-filter me-1"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Get all students - fixed to ensure proper recognition of student accounts
                                    $students = array_filter($users, function($user) {
                                        // Check if role is explicitly 'student' or default to student if not admin/staff
                                        return strtolower($user['role']) === 'student' || 
                                              (!in_array(strtolower($user['role']), ['admin', 'staff']) && $user['role'] !== '');
                                    });
                                    ?>
                                    
                                    <?php if (empty($students)): ?>
                                        <p class="text-center text-muted my-5">No student accounts found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>No.</th>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                        <th>Created At</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($students as $student): ?>
                                                        <tr>
                                                            <td><?php echo $student['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                            <td>
                                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                            <td>
                                                                <?php if ($student['status'] == 'approved'): ?>
                                                                    <span class="badge bg-success">Approved</span>
                                                                <?php elseif ($student['status'] == 'pending'): ?>
                                                                    <span class="badge bg-warning">Pending</span>
                                                                <?php elseif ($student['status'] == 'rejected'): ?>
                                                                    <span class="badge bg-danger">Rejected</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary"><?php echo ucfirst($student['status']); ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-primary view-student" 
                                                                       data-id="<?php echo $student['id']; ?>"
                                                                       data-bs-toggle="modal" data-bs-target="#viewStudentModal">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-sm btn-info update-status"
                                                                       data-id="<?php echo $student['id']; ?>"
                                                                       data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                                                                       data-status="<?php echo $student['status']; ?>"
                                                                       data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-sm btn-danger delete-student"
                                                                       data-id="<?php echo $student['id']; ?>"
                                                                       data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                                                                       data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PERMITS TAB -->
                        <div class="tab-pane fade" id="permits" role="tabpanel" aria-labelledby="permits-tab">
                            <!-- Permits Management -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Permits</h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterPermitsModal">
                                            <i class="fas fa-filter me-1"></i> Filter
                                        </button>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPermitModal">
                                            <i class="fas fa-plus me-1"></i> Create Permit
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get all student permits with user information
                                    $sql = "SELECT sp.*, u.first_name, u.last_name, u.email 
                                            FROM student_permits sp 
                                            JOIN users u ON sp.student_id = u.student_id 
                                            ORDER BY sp.created_at DESC";
                                    $result = mysqli_query($conn, $sql);
                                    $permits = [];
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $permits[] = $row;
                                    }

                                    // Get unique semesters and terms for filters
                                    $semesters = [];
                                    $terms = [];

                                    foreach ($permits as $permit) {
                                        if (!in_array($permit['semester'], $semesters)) {
                                            $semesters[] = $permit['semester'];
                                        }
                                        if (!in_array($permit['term'], $terms)) {
                                            $terms[] = $permit['term'];
                                        }
                                    }

                                    // Common term and semester options
                                    $term_options = ['PRELIM', 'MIDTERM', 'FINALS'];
                                    $semester_options = [
                                        'First Semester SY 2024-2025',
                                        'Second Semester SY 2024-2025',
                                        'Summer SY 2024-2025'
                                    ];
                                    ?>
                                    
                                    <?php if (empty($permits)): ?>
                                        <p class="text-center text-muted my-5">No student permits found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="permitsTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Student</th>
                                                        <th>Semester</th>
                                                        <th>Term</th>
                                                        <th>Status</th>
                                                        <th>Approved By</th>
                                                        <th>Approval Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($permits as $permit): ?>
                                                        <tr>
                                                            <td><?php echo $permit['id']; ?></td>
                                                            <td>
                                                                <?php echo htmlspecialchars($permit['first_name'] . ' ' . $permit['last_name']); ?>
                                                                <br><span class="small text-muted"><?php echo htmlspecialchars($permit['student_id']); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($permit['semester']); ?></td>
                                                            <td><?php echo htmlspecialchars($permit['term']); ?></td>
                                                            <td>
                                                                <?php if ($permit['status'] == 'Allowed'): ?>
                                                                    <span class="badge bg-success">Allowed</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Disallowed</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($permit['approved_by']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($permit['approval_date'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-primary view-permit" 
                                                                   data-id="<?php echo $permit['id']; ?>"
                                                                   data-student="<?php echo htmlspecialchars($permit['first_name'] . ' ' . $permit['last_name']); ?>"
                                                                   data-student-id="<?php echo htmlspecialchars($permit['student_id']); ?>"
                                                                   data-email="<?php echo htmlspecialchars($permit['email']); ?>"
                                                                   data-semester="<?php echo htmlspecialchars($permit['semester']); ?>"
                                                                   data-term="<?php echo htmlspecialchars($permit['term']); ?>"
                                                                   data-status="<?php echo $permit['status']; ?>"
                                                                   data-approved-by="<?php echo htmlspecialchars($permit['approved_by']); ?>"
                                                                   data-approval-date="<?php echo date('Y-m-d', strtotime($permit['approval_date'])); ?>"
                                                                   data-bs-toggle="modal" data-bs-target="#viewPermitModal">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-sm btn-info edit-permit"
                                                                   data-id="<?php echo $permit['id']; ?>"
                                                                   data-student="<?php echo htmlspecialchars($permit['first_name'] . ' ' . $permit['last_name']); ?>"
                                                                   data-student-id="<?php echo htmlspecialchars($permit['student_id']); ?>"
                                                                   data-semester="<?php echo htmlspecialchars($permit['semester']); ?>"
                                                                   data-term="<?php echo htmlspecialchars($permit['term']); ?>"
                                                                   data-status="<?php echo $permit['status']; ?>"
                                                                   data-approved-by="<?php echo htmlspecialchars($permit['approved_by']); ?>"
                                                                   data-approval-date="<?php echo date('Y-m-d', strtotime($permit['approval_date'])); ?>"
                                                                   data-bs-toggle="modal" data-bs-target="#editPermitModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-sm btn-danger delete-permit"
                                                                   data-id="<?php echo $permit['id']; ?>"
                                                                   data-student="<?php echo htmlspecialchars($permit['first_name'] . ' ' . $permit['last_name']); ?>"
                                                                   data-bs-toggle="modal" data-bs-target="#deletePermitModal">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- LEDGER TAB -->
                        <div class="tab-pane fade" id="ledger" role="tabpanel" aria-labelledby="ledger-tab">
                            <!-- Ledger Management -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Ledger</h6>
                                    <div>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">
                                            <i class="fas fa-plus-circle me-1"></i> Add New Ledger Entry
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get ledger entries with user information
                                    $sql = "SELECT l.*, u.first_name, u.last_name, u.student_id 
                                            FROM ledger l 
                                            JOIN users u ON l.user_id = u.id 
                                            ORDER BY l.created_at DESC";
                                    $result = mysqli_query($conn, $sql);
                                    $ledger_entries = [];
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $ledger_entries[] = $row;
                                    }
                                    ?>
                                    
                                    <?php if (empty($ledger_entries)): ?>
                                        <p class="text-center text-muted my-5">No ledger entries found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="ledgerTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Student</th>
                                                        <th>Semester</th>
                                                        <th>Amount</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ledger_entries as $entry): ?>
                                                        <tr>
                                                            <td><?php echo $entry['id']; ?></td>
                                                            <td>
                                                                <?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?>
                                                                <br><span class="small text-muted"><?php echo htmlspecialchars($entry['student_id']); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($entry['semester']); ?></td>
                                                            <td>₱<?php echo number_format($entry['amount'], 2); ?></td>
                                                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                                                            <td>
                                                                <?php if ($entry['status'] == 'paid'): ?>
                                                                    <span class="badge bg-success">Paid</span>
                                                                <?php elseif ($entry['status'] == 'pending'): ?>
                                                                    <span class="badge bg-warning">Pending</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Overdue</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($entry['created_at'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-primary edit-ledger" 
                                                                   data-id="<?php echo $entry['id']; ?>"
                                                                   data-semester="<?php echo htmlspecialchars($entry['semester']); ?>"
                                                                   data-amount="<?php echo $entry['amount']; ?>"
                                                                   data-description="<?php echo htmlspecialchars($entry['description']); ?>"
                                                                   data-status="<?php echo $entry['status']; ?>"
                                                                   data-bs-toggle="modal" data-bs-target="#editLedgerModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger delete-ledger"
                                                                   data-id="<?php echo $entry['id']; ?>"
                                                                   data-student="<?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?>"
                                                                   data-bs-toggle="modal" data-bs-target="#deleteLedgerModal">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="accounts.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                <div class="invalid-feedback">Please enter a first name.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                <div class="invalid-feedback">Please enter a last name.</div>
                            </div>
                           
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Please enter a password.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" disabled selected>Select a role</option>
                                    <option value="student">Student</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <div class="form-text" id="next_id_preview"></div>
                            </div>
                            <div class="col-12">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                <div class="form-text">Upload a profile image (optional).</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary hover-lift">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="accounts.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                <div class="invalid-feedback">Please enter a first name.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                <div class="invalid-feedback">Please enter a last name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                                <div class="form-text">Leave blank to keep current password.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_role" class="form-label">Role</label>
                               <select class="form-select" id="edit_role" name="role" required>
    <option value="student">Student</option>
    <option value="staff">Staff</option>
    <option value="admin">Administrator</option>
</select>
                            </div>
                            <div class="col-12">
                                <label for="edit_profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="edit_profile_image" name="profile_image" accept="image/*">
                                <div class="form-text">Upload a new profile image (optional).</div>
                            </div>
                        </div>
                        <input type="hidden" id="edit_user_id" name="user_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user" class="btn btn-primary hover-lift">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="accounts.php">
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone. All data associated with this user will be permanently deleted.</p>
                        <input type="hidden" id="delete_user_id" name="user_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger hover-lift">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center mb-3">
                            <img id="view_profile_image" src="" class="img-fluid rounded-circle profile-image hover-lift mb-3" alt="Profile Image" style="width: 150px; height: 150px; object-fit: cover;">
                            <div>
                                <span id="view_user_role" class="badge fs-6 py-2 px-3 rounded-pill"></span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h3 id="view_user_name" class="mb-2 border-bottom pb-2"></h3>
                            
                            <div class="mb-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><i class="fas fa-id-card me-2 text-primary"></i>Student ID:</strong></p>
                                        <p class="ms-4" id="view_student_id"></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><i class="fas fa-envelope me-2 text-primary"></i>Email:</strong></p>
                                        <p class="ms-4" id="view_email"></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><i class="fas fa-building me-2 text-primary"></i>Department:</strong></p>
                                        <p class="ms-4" id="view_department"></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong><i class="fas fa-calendar-alt me-2 text-primary"></i>Joined:</strong></p>
                                        <p class="ms-4" id="view_date"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nav tabs for detailed information -->
                    <ul class="nav nav-tabs user-details-tabs" id="userDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal-info" type="button" role="tab" aria-controls="personal-info" aria-selected="true">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency-contact" type="button" role="tab" aria-controls="emergency-contact" aria-selected="false">
                                <i class="fas fa-ambulance me-2"></i>Emergency Contact
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content p-3 border border-top-0 rounded-bottom mb-4">
                        <!-- Personal Information -->
                        <div class="tab-pane fade show active" id="personal-info" role="tabpanel" aria-labelledby="personal-tab">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-birthday-cake me-2 text-primary"></i>Date of Birth:</strong></p>
                                    <p class="ms-4" id="view_date_of_birth"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-venus-mars me-2 text-primary"></i>Gender:</strong></p>
                                    <p class="ms-4" id="view_gender"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-phone me-2 text-primary"></i>Phone Number:</strong></p>
                                    <p class="ms-4" id="view_phone_number"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-flag me-2 text-primary"></i>Nationality:</strong></p>
                                    <p class="ms-4" id="view_nationality"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p class="mb-1"><strong><i class="fas fa-map-marker-alt me-2 text-primary"></i>Address:</strong></p>
                                    <p class="ms-4" id="view_address"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <div class="tab-pane fade" id="emergency-contact" role="tabpanel" aria-labelledby="emergency-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-user-friends me-2 text-primary"></i>Contact Name:</strong></p>
                                    <p class="ms-4" id="view_emergency_contact_name"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-phone me-2 text-primary"></i>Contact Phone:</strong></p>
                                    <p class="ms-4" id="view_emergency_contact_phone"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fas fa-user-friends me-2 text-primary"></i>Relationship:</strong></p>
                                    <p class="ms-4" id="view_emergency_contact_relation"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary hover-lift edit-from-view">
                        <i class="fas fa-edit me-1"></i> Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Student Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="studentDetails">
                        <!-- Student details will be loaded here via AJAX -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading student details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Student Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="accounts.php">
                        <input type="hidden" id="status_student_id" name="student_id">
                        
                        <div class="mb-3">
                            <label>Student Name</label>
                            <input type="text" class="form-control" id="status_student_name" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Student Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStudentModalLabel">Delete Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the student account for <strong id="delete_student_name"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All data associated with this student will be permanently deleted.
                    </div>
                    <form method="POST" action="accounts.php">
                        <input type="hidden" id="delete_student_id" name="student_id">
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_student" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Filter Modal -->
    <div class="modal fade" id="studentFilterModal" tabindex="-1" aria-labelledby="studentFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentFilterModalLabel">Filter Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filter_student_status" class="form-label">Status</label>
                        <select class="form-select" id="filter_student_status">
                            <option value="">All Statuses</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="apply_student_filter" class="btn btn-primary" data-bs-dismiss="modal">Apply Filter</button>
                        <button type="button" id="reset_student_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/script.js"></script>
    
    <?php echo $page_specific_js; ?>
    
    <!-- Permit-related Modals -->
    <!-- Create Permit Modal -->
    <div class="modal fade" id="createPermitModal" tabindex="-1" aria-labelledby="createPermitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPermitModalLabel">Create New Permit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="accounts.php" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="student_ids" class="form-label">Select Student(s) <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="student_ids" name="student_ids[]" multiple required>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select one or more students</div>
                            </div>
                            <div class="col-md-6">
                                <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                                <select class="form-select" id="term" name="term" required>
                                    <option value="">Select Term</option>
                                    <?php foreach ($term_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <?php foreach ($semester_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Permit Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Allowed">Allowed</option>
                                    <option value="Disallowed">Disallowed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="approved_by" class="form-label">Approved By <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="approved_by" name="approved_by" required>
                            </div>
                            <div class="col-md-6">
                                <label for="approval_date" class="form-label">Approval Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="approval_date" name="approval_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="permit_file" class="form-label">Upload Permit Template (Optional)</label>
                            <input type="file" class="form-control" id="permit_file" name="permit_file">
                            <div class="form-text">Accepted formats: PDF, JPG, PNG (max 5MB)</div>
                        </div>
                        
                        <input type="hidden" name="create_permit" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Permit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Permit Modal -->
    <div class="modal fade" id="viewPermitModal" tabindex="-1" aria-labelledby="viewPermitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPermitModalLabel">Permit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> <span id="view_student"></span></p>
                                    <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                                    <p><strong>Email:</strong> <span id="view_email"></span></p>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Permit Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Semester:</strong> <span id="view_semester"></span></p>
                                    <p><strong>Term:</strong> <span id="view_term"></span></p>
                                    <p><strong>Status:</strong> <span id="view_status"></span></p>
                                    <p><strong>Approved By:</strong> <span id="view_approved_by"></span></p>
                                    <p><strong>Approval Date:</strong> <span id="view_approval_date"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="permit-card" id="view_permit_card">
                                <div class="permit-watermark">EXAM PERMIT</div>
                                <div class="permit-header">
                                    <h4>Capitol University</h4>
                                    <h5>Working Scholars Association</h5>
                                    <h6>Exam Permit - <span id="view_card_term"></span></h6>
                                    <p class="mb-0"><span id="view_card_semester"></span></p>
                                </div>
                                <div class="permit-status">
                                    <span class="badge" id="view_card_status_badge"></span>
                                </div>
                                <hr>
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <p><strong>Student Name:</strong> <span id="view_card_name"></span></p>
                                        <p><strong>Student ID:</strong> <span id="view_card_id"></span></p>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <p><strong>Approved By:</strong> <span id="view_card_approver"></span></p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p><strong>Date:</strong> <span id="view_card_date"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Permit Modal -->
    <div class="modal fade" id="editPermitModal" tabindex="-1" aria-labelledby="editPermitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPermitModalLabel">Edit Permit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="accounts.php">
                        <input type="hidden" id="edit_permit_id" name="permit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="edit_student" disabled>
                            <input type="hidden" id="edit_student_id" name="student_id">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Semester</label>
                                <input type="text" class="form-control" id="edit_semester" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Term</label>
                                <input type="text" class="form-control" id="edit_term" disabled>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Permit Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Allowed">Allowed</option>
                                <option value="Disallowed">Disallowed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_approved_by" class="form-label">Approved By <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_approved_by" name="approved_by" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_approval_date" class="form-label">Approval Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_approval_date" name="approval_date" required>
                        </div>
                        
                        <input type="hidden" name="update_permit" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Permit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Permit Modal -->
    <div class="modal fade" id="deletePermitModal" tabindex="-1" aria-labelledby="deletePermitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePermitModalLabel">Delete Permit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the permit for <strong id="delete_student"></strong>?</p>
                    <form method="POST" action="accounts.php">
                        <input type="hidden" id="delete_permit_id" name="permit_id">
                        <input type="hidden" name="delete_permit" value="1">
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Permits Modal -->
    <div class="modal fade" id="filterPermitsModal" tabindex="-1" aria-labelledby="filterPermitsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterPermitsModalLabel">Filter Permits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filter_semester" class="form-label">Semester</label>
                        <select class="form-select" id="filter_semester">
                            <option value="">All Semesters</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo htmlspecialchars($sem); ?>">
                                    <?php echo htmlspecialchars($sem); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filter_term" class="form-label">Term</label>
                        <select class="form-select" id="filter_term">
                            <option value="">All Terms</option>
                            <?php foreach ($terms as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>">
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filter_status" class="form-label">Status</label>
                        <select class="form-select" id="filter_status">
                            <option value="">All Statuses</option>
                            <option value="Allowed">Allowed</option>
                            <option value="Disallowed">Disallowed</option>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="apply_filter" class="btn btn-primary" data-bs-dismiss="modal">Apply Filter</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ledger-related Modals -->
    <!-- Add Ledger Modal -->
    <div class="modal fade" id="addLedgerModal" tabindex="-1" aria-labelledby="addLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLedgerModalLabel">Add New Ledger Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="accounts.php">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Student</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['role'] == 'user'): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['student_id'] . ')'); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="ledger_semester" name="semester" required placeholder="e.g. 1st Semester 2023-2024">
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="ledger_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <input type="hidden" name="add_ledger" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Ledger Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Ledger Modal -->
    <div class="modal fade" id="editLedgerModal" tabindex="-1" aria-labelledby="editLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLedgerModalLabel">Edit Ledger Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="accounts.php">
                        <div class="mb-3">
                            <label for="edit_semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="edit_ledger_semester" name="semester" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_ledger_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <input type="hidden" id="edit_ledger_id" name="ledger_id">
                        <input type="hidden" name="update_ledger" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Ledger Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Ledger Modal -->
    <div class="modal fade" id="deleteLedgerModal" tabindex="-1" aria-labelledby="deleteLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLedgerModalLabel">Delete Ledger Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the ledger entry for <span id="delete_student_name"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                    <form method="POST" action="accounts.php">
                        <input type="hidden" id="delete_ledger_id" name="ledger_id">
                        <input type="hidden" name="delete_ledger" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Ledger Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>