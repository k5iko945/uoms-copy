<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
check_permission('user');

// Get the currently active page
$current_page = basename($_SERVER['PHP_SELF']);

// Get student ID
$student_id = $_SESSION['student_id'];

// Get user's permits
$sql = "SELECT * FROM student_permits WHERE student_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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

// Get user profile
$profile_sql = "SELECT * FROM users WHERE id = ?";
$profile_stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($profile_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$user_profile = mysqli_fetch_assoc($profile_result);

// Log the permit view
$action = 'View Permits';
$description = "User viewed their permits";
$ip = $_SERVER['REMOTE_ADDR'];
log_audit_event($_SESSION['user_id'], $action, $description, $ip);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Permits | Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .permit-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .permit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .permit-card.allowed {
            border-left: 5px solid var(--success-color);
        }
        .permit-card.disallowed {
            border-left: 5px solid var(--danger-color);
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
        .download-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
        }
        .no-permits {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        .semester-badge {
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            margin-right: 10px;
            margin-bottom: 10px;
            display: inline-block;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .semester-badge.active {
            background-color: var(--dark-color);
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
                <!-- Include header -->
                <?php include 'includes/header.php'; ?>
                
                <!-- Page Content -->
                <div class="container-fluid">
                    <!-- Page heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Permits</h1>
                    </div>
                    
                    <?php if (empty($permits)): ?>
                        <div class="no-permits">
                            <i class="fas fa-exclamation-circle fa-3x mb-3 text-muted"></i>
                            <h4>No Permits Found</h4>
                            <p class="text-muted">You don't have any exam permits assigned yet.</p>
                        </div>
                    <?php else: ?>
                        <!-- Filter options -->
                        <div class="mb-4">
                            <h5>Filter by Semester:</h5>
                            <div class="d-flex flex-wrap">
                                <div class="semester-badge all active" data-semester="">All</div>
                                <?php foreach ($semesters as $sem): ?>
                                    <div class="semester-badge" data-semester="<?php echo htmlspecialchars($sem); ?>">
                                        <?php echo htmlspecialchars($sem); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <h5 class="mt-3">Filter by Term:</h5>
                            <div class="d-flex flex-wrap">
                                <div class="term-badge all active" data-term="">All</div>
                                <?php foreach ($terms as $term): ?>
                                    <div class="term-badge semester-badge" data-term="<?php echo htmlspecialchars($term); ?>">
                                        <?php echo htmlspecialchars($term); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Permits display -->
                        <div class="row" id="permits-container">
                            <?php foreach ($permits as $permit): ?>
                                <div class="col-md-6 permit-item" 
                                     data-semester="<?php echo htmlspecialchars($permit['semester']); ?>"
                                     data-term="<?php echo htmlspecialchars($permit['term']); ?>">
                                    <div class="permit-card <?php echo $permit['status'] == 'Allowed' ? 'allowed' : 'disallowed'; ?>">
                                        <div class="permit-watermark">EXAM PERMIT</div>
                                        <div class="permit-header">
                                            <h4>Capitol University</h4>
                                            <h5>Working Scholars Association</h5>
                                            <h6>Exam Permit - <?php echo htmlspecialchars($permit['term']); ?></h6>
                                            <p class="mb-0"><?php echo htmlspecialchars($permit['semester']); ?></p>
                                        </div>
                                        
                                        <div class="permit-status">
                                            <?php if ($permit['status'] == 'Allowed'): ?>
                                                <span class="badge bg-success">Allowed</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Disallowed</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row mb-2">
                                            <div class="col-md-12">
                                                <p><strong>Student Name:</strong> <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?></p>
                                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($user_profile['student_id']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <p><strong>Approved By:</strong> <?php echo htmlspecialchars($permit['approved_by']); ?></p>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($permit['approval_date'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="download-btn">
                                            <button class="btn btn-sm btn-primary print-permit">
                                                <i class="fas fa-print me-1"></i> Print
                                            </button>
                                            
                                            <?php if ($permit['file_path']): ?>
                                                <a href="../<?php echo $permit['file_path']; ?>" download class="btn btn-sm btn-outline-primary ms-1">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Filter by semester
            $('.semester-badge').click(function() {
                $('.semester-badge').removeClass('active');
                $(this).addClass('active');
                
                filterPermits();
            });
            
            // Filter by term
            $('.term-badge').click(function() {
                $('.term-badge').removeClass('active');
                $(this).addClass('active');
                
                filterPermits();
            });
            
            // Filter function
            function filterPermits() {
                const selectedSemester = $('.semester-badge.active').data('semester');
                const selectedTerm = $('.term-badge.active').data('term');
                
                $('.permit-item').each(function() {
                    const permitSemester = $(this).data('semester');
                    const permitTerm = $(this).data('term');
                    
                    const semesterMatch = !selectedSemester || permitSemester === selectedSemester;
                    const termMatch = !selectedTerm || permitTerm === selectedTerm;
                    
                    if (semesterMatch && termMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            // Print functionality
            $('.print-permit').click(function() {
                const permitCard = $(this).closest('.permit-card').clone();
                
                // Remove the download button from the clone
                permitCard.find('.download-btn').remove();
                
                // Set styles for printing
                permitCard.css({
                    'width': '100%',
                    'max-width': '800px',
                    'margin': '0 auto',
                    'box-shadow': 'none',
                    'border': '1px solid #ddd'
                });
                
                // Create options for html2pdf
                const options = {
                    margin: 0.5,
                    filename: 'exam_permit.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                
                // Generate PDF
                html2pdf().set(options).from(permitCard[0]).save();
            });
            
            // Toggle sidebar on mobile
            $('#mobileToggle').click(function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html> 