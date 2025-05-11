<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if admin is logged in
check_permission('admin');

// Validate request
if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
    echo '<div class="alert alert-danger">Invalid request</div>';
    exit;
}

$student_id = clean_input($_POST['student_id']);

// Get student details
$sql = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Student not found</div>';
    exit;
}

$student = mysqli_fetch_assoc($result);

// Get student's permits, if any
$permits_sql = "SELECT * FROM student_permits WHERE student_id = ? ORDER BY created_at DESC";
$permits_stmt = mysqli_prepare($conn, $permits_sql);
mysqli_stmt_bind_param($permits_stmt, "s", $student['student_id']);
mysqli_stmt_execute($permits_stmt);
$permits_result = mysqli_stmt_get_result($permits_stmt);
$permits = [];
while ($permit = mysqli_fetch_assoc($permits_result)) {
    $permits[] = $permit;
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="mb-4">
            <h5>Personal Information</h5>
            <hr>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Student ID:</div>
                <div class="col-md-8"><?php echo htmlspecialchars($student['student_id']); ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Full Name:</div>
                <div class="col-md-8"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Email:</div>
                <div class="col-md-8"><?php echo htmlspecialchars($student['email']); ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Phone:</div>
                <div class="col-md-8"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Status:</div>
                <div class="col-md-8">
                    <?php if ($student['status'] == 'approved'): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php elseif ($student['status'] == 'pending'): ?>
                        <span class="badge bg-warning">Pending</span>
                    <?php elseif ($student['status'] == 'rejected'): ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?php echo ucfirst($student['status']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="mb-4">
            <h5>Account Information</h5>
            <hr>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Created:</div>
                <div class="col-md-8"><?php echo date('M d, Y h:i A', strtotime($student['created_at'])); ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 fw-bold">Last Login:</div>
                <div class="col-md-8">
                    <?php echo !empty($student['last_login']) 
                        ? date('M d, Y h:i A', strtotime($student['last_login'])) 
                        : 'Never logged in'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($permits)): ?>
<div class="mt-4">
    <h5>Student Permits</h5>
    <hr>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Semester</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Approval Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permits as $permit): ?>
                    <tr>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle me-2"></i>
        No permits found for this student.
    </div>
<?php endif; ?> 