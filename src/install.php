<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'uom_system';

// Flag to track installation status
$installed = false;
$error = false;
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // Connect to MySQL server without selecting a database
    $conn = mysqli_connect($db_host, $db_user, $db_pass);

    // Check connection
    if (!$conn) {
        $error = true;
        $error_message = "Connection failed: " . mysqli_connect_error();
    } else {
        // Read the SQL file
        $sql_file = file_get_contents('database.sql');
        
        if (!$sql_file) {
            $error = true;
            $error_message = "Could not read database.sql file";
        } else {
            // Create database if not exists
            $create_db_query = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
            if (!mysqli_query($conn, $create_db_query)) {
                $error = true;
                $error_message = "Could not create database: " . mysqli_error($conn);
            } else {
                // Select the database
                mysqli_select_db($conn, $db_name);

                // Remove "CREATE DATABASE" and "USE" statements - we've already created and selected the DB
                $sql_file = preg_replace('/CREATE DATABASE.*?;/is', '', $sql_file);
                $sql_file = preg_replace('/USE.*?;/is', '', $sql_file);
                
                // Split SQL file into statements
                $sql_statements = explode(';', $sql_file);
                
                $success = true;
                $count = 0;
                
                foreach ($sql_statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        if (!mysqli_query($conn, $statement)) {
                            $error = true;
                            $error_message = "Error executing statement: " . mysqli_error($conn) . "<br>Statement: " . substr($statement, 0, 100) . "...";
                            $success = false;
                            break;
                        } else {
                            $count++;
                        }
                    }
                }
                
                if ($success) {
                    $installed = true;
                    $success_message = "Database installed successfully! $count SQL statements executed. You can now <a href='login.php'>login</a> to your account.";
                    
                    // Create a file to indicate installation is complete
                    file_put_contents('installed.lock', date('Y-m-d H:i:s'));
                }
            }
        }
        
        // Close connection
        mysqli_close($conn);
    }
}

// Check if already installed
if (file_exists('installed.lock')) {
    $installed = true;
    $success_message = "The application is already installed. Go to <a href='login.php'>login page</a>.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
        }
        .install-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center">
                    <h2 class="mb-1"><i class="fas fa-graduation-cap text-primary me-2"></i> Working Scholars Association</h2>
                    <p class="mb-4 text-muted">Installation</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($installed): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                    </div>
                <?php else: ?>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Welcome to the Installation Process</h5>
                            <p>This wizard will help you install the Working Scholars Association database. Make sure you have:</p>
                            <ul>
                                <li>A MySQL/MariaDB database server</li>
                                <li>Proper database credentials</li>
                                <li>Web server with PHP 7.4 or higher</li>
                            </ul>
                            <p class="card-text text-muted small">The default admin credentials after installation:<br>
                            Username: admin<br>
                            Password: admin123</p>
                        </div>
                    </div>

                    <form method="POST" action="" class="mt-4">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="uom_system" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="root" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" value="" readonly>
                            <div class="form-text">Leave blank for no password (default XAMPP setup)</div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" name="install" class="btn btn-primary">
                                <i class="fas fa-cog me-2"></i> Install Now
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 