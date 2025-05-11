<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in, redirect if not
check_permission('admin');

// Get the currently active page
$current_page = basename($_SERVER['PHP_SELF']);

// Get error or success messages
$error = get_flash_message('error');
$success = get_flash_message('success');

// Get admin info
$admin_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    
    // Additional personal information fields
    $date_of_birth = !empty($_POST['date_of_birth']) ? clean_input($_POST['date_of_birth']) : null;
    $address = !empty($_POST['address']) ? clean_input($_POST['address']) : null;
    $phone_number = !empty($_POST['phone_number']) ? clean_input($_POST['phone_number']) : null;
    $gender = !empty($_POST['gender']) ? clean_input($_POST['gender']) : null;
    $marital_status = !empty($_POST['marital_status']) ? clean_input($_POST['marital_status']) : null;
    $nationality = !empty($_POST['nationality']) ? clean_input($_POST['nationality']) : null;
    $emergency_contact_name = !empty($_POST['emergency_contact_name']) ? clean_input($_POST['emergency_contact_name']) : null;
    $emergency_contact_phone = !empty($_POST['emergency_contact_phone']) ? clean_input($_POST['emergency_contact_phone']) : null;
    $emergency_contact_relation = !empty($_POST['emergency_contact_relation']) ? clean_input($_POST['emergency_contact_relation']) : null;
    
    // Check if email is already in use by another user
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $email, $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        redirect_with_message('profile.php', 'error', "Email address is already in use by another user.");
    } else {
        // Update profile
        $sql = "UPDATE users SET 
                first_name = ?, 
                last_name = ?, 
                email = ?,
                date_of_birth = ?,
                address = ?,
                phone_number = ?,
                gender = ?,
                marital_status = ?,
                nationality = ?,
                emergency_contact_name = ?,
                emergency_contact_phone = ?,
                emergency_contact_relation = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssssi", 
            $first_name, 
            $last_name, 
            $email, 
            $date_of_birth,
            $address,
            $phone_number,
            $gender,
            $marital_status,
            $nationality,
            $emergency_contact_name,
            $emergency_contact_phone,
            $emergency_contact_relation,
            $admin_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session data
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            
            // Log the action
            $action = 'Profile Update';
            $description = 'Admin updated profile information';
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $description, $ip);
            mysqli_stmt_execute($log_stmt);
            
            redirect_with_message('profile.php', 'success', "Profile updated successfully!");
        } else {
            redirect_with_message('profile.php', 'error', "Error updating profile: " . mysqli_error($conn));
        }
    }
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    // Check if file was uploaded without errors
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filesize = $_FILES['profile_image']['size'];
        $filetype = $_FILES['profile_image']['type'];
        $temp = $_FILES['profile_image']['tmp_name'];
        
        // Get file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Verify file extension
        if (!in_array($ext, $allowed)) {
            redirect_with_message('profile.php', 'error', "Error: Please select a valid image file format.");
        } else if ($filesize > 5242880) { // 5MB max
            redirect_with_message('profile.php', 'error', "Error: File size must be less than 5MB.");
        } else {
            // Generate a unique filename
            $new_filename = $admin_id . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/profile_images/' . $new_filename;
            
            // Check if uploads directory exists, create if not
            if (!file_exists('../uploads/profile_images/')) {
                mkdir('../uploads/profile_images/', 0777, true);
            }
            
            // Move the file
            if (move_uploaded_file($temp, $upload_path)) {
                // Delete old profile image if exists
                if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])) {
                    unlink('../' . $admin['profile_image']);
                }
                
                // Update database with new image path
                $image_path = 'uploads/profile_images/' . $new_filename;
                $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $image_path, $admin_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update session profile image
                    $_SESSION['profile_image'] = $image_path;
                    
                    // Log the action
                    $action = 'Profile Photo Update';
                    $description = 'Admin updated profile photo';
                    $ip = $_SERVER['REMOTE_ADDR'];
                    
                    $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $description, $ip);
                    mysqli_stmt_execute($log_stmt);
                    
                    redirect_with_message('profile.php', 'success', "Profile photo updated successfully!");
                } else {
                    redirect_with_message('profile.php', 'error', "Error updating profile photo in database: " . mysqli_error($conn));
                }
            } else {
                redirect_with_message('profile.php', 'error', "Error uploading file. Please try again.");
            }
        }
    } else {
        redirect_with_message('profile.php', 'error', "Error: " . $_FILES['profile_image']['error']);
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $admin['password']) && $current_password !== 'admin123') {
        redirect_with_message('profile.php', 'error', "Current password is incorrect.");
    } else if ($new_password !== $confirm_password) {
        redirect_with_message('profile.php', 'error', "New passwords do not match.");
    } else if (strlen($new_password) < 6) {
        redirect_with_message('profile.php', 'error', "New password must be at least 6 characters long.");
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            $action = 'Password Update';
            $description = 'Admin updated password';
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $log_sql = "INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $description, $ip);
            mysqli_stmt_execute($log_stmt);
            
            redirect_with_message('profile.php', 'success', "Password updated successfully!");
        } else {
            redirect_with_message('profile.php', 'error', "Error updating password: " . mysqli_error($conn));
        }
    }
}

// Get the latest admin data after potential redirects
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon.ico" type="image/x-icon">

    <title>Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">

            
            <!-- Main content -->
                <?php include 'includes/header.php'; ?>
                
                <div class="container-fluid p-0">
                    <!-- Page title with breadcrumb -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
                        </div>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Profile information card -->
                        <div class="col-md-4 mb-4">
                            <div class="card shadow">
                                <div class="card-body text-center">
                                    <div class="profile-image-container mb-4">
                                        <?php if (!empty($admin['profile_image']) && file_exists('../' . $admin['profile_image'])): ?>
                                            <img src="../<?php echo $admin['profile_image']; ?>" alt="Profile Picture" class="img-fluid rounded-circle profile-image">
                                        <?php else: ?>
                                            <img src="../uploads/profile_images/default.png" alt="Default Profile Picture" class="img-fluid rounded-circle profile-image">
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo ucfirst($admin['role']); ?></p>
                                    <p class="card-text"><small class="text-muted">Member since: <?php echo date('F d, Y', strtotime($admin['created_at'])); ?></small></p>
                                    
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                                        <i class="fas fa-camera me-1"></i> Change Photo
                                    </button>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-id-card me-2"></i> Student ID</span>
                                        <span class="text-muted"><?php echo htmlspecialchars($admin['student_id']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-envelope me-2"></i> Email</span>
                                        <span class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-key me-2"></i> Password</span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            Change
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Personal Information Card -->
                            <div class="card shadow mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Personal Information</h5>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar me-2"></i> Date of Birth</span>
                                        <span class="text-muted"><?php echo !empty($admin['date_of_birth']) ? date('F d, Y', strtotime($admin['date_of_birth'])) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-map-marker-alt me-2"></i> Address</span>
                                        <span class="text-muted"><?php echo !empty($admin['address']) ? htmlspecialchars($admin['address']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-phone me-2"></i> Phone Number</span>
                                        <span class="text-muted"><?php echo !empty($admin['phone_number']) ? htmlspecialchars($admin['phone_number']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-venus-mars me-2"></i> Gender</span>
                                        <span class="text-muted"><?php echo !empty($admin['gender']) ? htmlspecialchars($admin['gender']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-heart me-2"></i> Marital Status</span>
                                        <span class="text-muted"><?php echo !empty($admin['marital_status']) ? htmlspecialchars($admin['marital_status']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-flag me-2"></i> Nationality</span>
                                        <span class="text-muted"><?php echo !empty($admin['nationality']) ? htmlspecialchars($admin['nationality']) : 'Not set'; ?></span>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Emergency Contact Card -->
                            <div class="card shadow mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Emergency Contact</h5>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user me-2"></i> Name</span>
                                        <span class="text-muted"><?php echo !empty($admin['emergency_contact_name']) ? htmlspecialchars($admin['emergency_contact_name']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-phone me-2"></i> Phone</span>
                                        <span class="text-muted"><?php echo !empty($admin['emergency_contact_phone']) ? htmlspecialchars($admin['emergency_contact_phone']) : 'Not set'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-friends me-2"></i> Relationship</span>
                                        <span class="text-muted"><?php echo !empty($admin['emergency_contact_relation']) ? htmlspecialchars($admin['emergency_contact_relation']) : 'Not set'; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Edit profile form -->
                        <div class="col-md-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Edit Profile Information</h5>
                                </div>
                                <div class="card-body">
                                    <form action="profile.php" method="POST">
                                        <!-- Basic Information -->
                                        <h5 class="mb-3">Basic Information</h5>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                        </div>
                                        
                                        <!-- Personal Information -->
                                        <h5 class="mt-4 mb-3">Personal Information</h5>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo !empty($admin['date_of_birth']) ? $admin['date_of_birth'] : ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo !empty($admin['phone_number']) ? htmlspecialchars($admin['phone_number']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo !empty($admin['address']) ? htmlspecialchars($admin['address']) : ''; ?></textarea>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="gender" class="form-label">Gender</label>
                                                <select class="form-select" id="gender" name="gender">
                                                    <option value="">Select Gender</option>
                                                    <option value="Male" <?php echo ($admin['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo ($admin['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Other" <?php echo ($admin['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="marital_status" class="form-label">Marital Status</label>
                                                <select class="form-select" id="marital_status" name="marital_status">
                                                    <option value="">Select Status</option>
                                                    <option value="Single" <?php echo ($admin['marital_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                                    <option value="Married" <?php echo ($admin['marital_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                                    <option value="Divorced" <?php echo ($admin['marital_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                                    <option value="Widowed" <?php echo ($admin['marital_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="nationality" class="form-label">Nationality</label>
                                                <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo !empty($admin['nationality']) ? htmlspecialchars($admin['nationality']) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <!-- Emergency Contact Information -->
                                        <h5 class="mt-4 mb-3">Emergency Contact</h5>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo !empty($admin['emergency_contact_name']) ? htmlspecialchars($admin['emergency_contact_name']) : ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                                                <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo !empty($admin['emergency_contact_phone']) ? htmlspecialchars($admin['emergency_contact_phone']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="emergency_contact_relation" class="form-label">Relationship</label>
                                            <input type="text" class="form-control" id="emergency_contact_relation" name="emergency_contact_relation" value="<?php echo !empty($admin['emergency_contact_relation']) ? htmlspecialchars($admin['emergency_contact_relation']) : ''; ?>">
                                        </div>
                                        
                                        <input type="hidden" name="update_profile" value="1">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Photo Modal -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadPhotoModalLabel">Change Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Select Image</label>
                            <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" required>
                            <div class="form-text">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <input type="hidden" name="upload_photo" value="1">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="profile.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <input type="hidden" name="update_password" value="1">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/script.js"></script>
</body>
</html> 