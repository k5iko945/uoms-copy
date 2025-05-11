<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in, redirect if not
check_permission('admin');

// Get the currently active page
$current_page = basename($_SERVER['PHP_SELF']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Expense Form
    if (isset($_POST['add_expense'])) {
        $amount = clean_input($_POST['amount']);
        $category = clean_input($_POST['category']);
        $department = clean_input($_POST['department']);
        $description = clean_input($_POST['description']);
        $expense_date = clean_input($_POST['expense_date']);
        
        // Handle receipt upload
        $receipt_image = null;
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/audit/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . $_FILES['receipt_image']['name'];
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                $receipt_image = 'uploads/audit/' . $file_name;
            }
        }
        
        // Add the expense
        $expense_id = add_expense($_SESSION['user_id'], $amount, $category, $department, $description, $expense_date, $receipt_image);
        
        if ($expense_id) {
            // Redirect after successful submission to prevent form resubmission on refresh
            redirect("audit.php?success=expense_added");
        } else {
            $error_message = "Failed to add expense. Please try again.";
        }
    }
    
    // Record Financial Transaction
    elseif (isset($_POST['record_transaction'])) {
        $user_id = clean_input($_POST['user_id']);
        $amount = clean_input($_POST['amount']);
        $transaction_type = clean_input($_POST['transaction_type']);
        $description = clean_input($_POST['description']);
        
        // Handle receipt upload
        $receipt_image = null;
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/audit/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . $_FILES['receipt_image']['name'];
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                $receipt_image = 'uploads/audit/' . $file_name;
            }
        }
        
        // Record the transaction
        $transaction_id = record_financial_transaction($user_id, $_SESSION['user_id'], $amount, $transaction_type, null, null, $description, $receipt_image);
        
        if ($transaction_id) {
            // Redirect after successful submission to prevent form resubmission on refresh
            redirect("audit.php?success=transaction_recorded");
        } else {
            $error_message = "Failed to record transaction. Please try again.";
        }
    }
    
    // Delete Financial Transaction
    elseif (isset($_POST['delete_transaction'])) {
        $transaction_id = clean_input($_POST['transaction_id']);
        
        if (empty($transaction_id)) {
            $error_message = "No transaction ID provided for deletion.";
        } else {
            // Try to delete the transaction
            $result = delete_financial_transaction($transaction_id, $_SESSION['user_id']);
            
            if ($result) {
                // Redirect after successful submission
                redirect("audit.php?success=transaction_deleted");
            } else {
                // Check for a more specific error in the error log
                $error_message = "Failed to delete transaction. Please try again.";
                // For admin debugging only - in production you might want to remove this
                error_log("Failed to delete transaction ID: $transaction_id. Check PHP error log for details.");
            }
        }
    }
    
    // Delete Expense
    elseif (isset($_POST['delete_expense'])) {
        $expense_id = clean_input($_POST['expense_id']);
        
        if (empty($expense_id)) {
            $error_message = "No expense ID provided for deletion.";
        } else {
            // Try to delete the expense
            $result = delete_expense($expense_id, $_SESSION['user_id']);
            
            if ($result) {
                // Redirect after successful submission
                redirect("audit.php?success=expense_deleted");
            } else {
                $error_message = "Failed to delete expense. Please try again.";
                // For admin debugging only - in production you might want to remove this
                error_log("Failed to delete expense ID: $expense_id. Check PHP error log for details.");
            }
        }
    }
    
    // Delete Audit Log Entry
    elseif (isset($_POST['delete_audit_log'])) {
        $log_id = clean_input($_POST['log_id']);
        
        if (empty($log_id)) {
            $error_message = "No audit log ID provided for deletion.";
        } else {
            // Try to delete the audit log
            $result = delete_audit_log($log_id, $_SESSION['user_id']);
            
            if ($result) {
                // Redirect after successful submission
                redirect("audit.php?success=audit_log_deleted");
            } else {
                $error_message = "Failed to delete audit log entry. Please try again.";
                // For admin debugging only - in production you might want to remove this
                error_log("Failed to delete audit log ID: $log_id. Check PHP error log for details.");
            }
        }
    }
    
    // Export to Excel
    elseif (isset($_POST['export_excel'])) {
        $export_type = clean_input($_POST['export_type']);
        $start_date = !empty($_POST['start_date']) ? clean_input($_POST['start_date']) : null;
        $end_date = !empty($_POST['end_date']) ? clean_input($_POST['end_date']) : null;
        
        $filters = [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        
        if ($export_type === 'transactions') {
            if (!empty($_POST['transaction_type'])) {
                $filters['transaction_type'] = clean_input($_POST['transaction_type']);
            }
            
            if (!empty($_POST['student_id'])) {
                $filters['student_id'] = clean_input($_POST['student_id']);
            }
            
            $transactions = get_financial_transactions($filters);
            
            // Prepare data for Excel
            $headers = ['ID', 'Student', 'Amount', 'Type', 'Description', 'Admin', 'Date'];
            $data = [];
            
            foreach ($transactions as $transaction) {
                $student_name = $transaction['student_id'] ? $transaction['first_name'] . ' ' . $transaction['last_name'] . ' (' . $transaction['student_id'] . ')' : 'N/A';
                $admin_name = $transaction['admin_id'] ? $transaction['admin_first_name'] . ' ' . $transaction['admin_last_name'] : 'N/A';
                
                $data[] = [
                    $transaction['id'],
                    $student_name,
                    $transaction['amount'],
                    ucfirst($transaction['transaction_type']),
                    $transaction['description'],
                    $admin_name,
                    date('M d, Y g:i A', strtotime($transaction['created_at']))
                ];
            }
            
            $filename = 'financial_transactions_' . date('Y-m-d') . '.xlsx';
        } elseif ($export_type === 'expenses') {
            if (!empty($_POST['category'])) {
                $filters['category'] = clean_input($_POST['category']);
            }
            
            if (!empty($_POST['department'])) {
                $filters['department'] = clean_input($_POST['department']);
            }
            
            $expenses = get_expenses($filters);
            
            // Prepare data for Excel
            $headers = ['ID', 'Amount', 'Category', 'Department', 'Description', 'Admin', 'Date'];
            $data = [];
            
            foreach ($expenses as $expense) {
                $admin_name = $expense['first_name'] . ' ' . $expense['last_name'];
                
                $data[] = [
                    $expense['id'],
                    $expense['amount'],
                    ucfirst($expense['category']),
                    $expense['department'],
                    $expense['description'],
                    $admin_name,
                    date('M d, Y', strtotime($expense['expense_date']))
                ];
            }
            
            $filename = 'expenses_' . date('Y-m-d') . '.xlsx';
        } else {
            $error_message = "Invalid export type.";
        }
        
        if (isset($data) && isset($headers) && isset($filename)) {
            $excel_file = generate_excel($data, $headers, $filename);
            
            if ($excel_file) {
                // Save the file path in session and redirect
                $_SESSION['excel_file'] = $excel_file;
                redirect("audit.php?success=excel_generated");
            } else {
                $error_message = "Failed to generate Excel file. Please make sure PHPSpreadsheet is installed.";
            }
        }
    }
}

// Get filtered data
$transaction_filters = [];
$expense_filters = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get filter parameters
    if (isset($_GET['filter_transactions'])) {
        if (!empty($_GET['transaction_type'])) {
            $transaction_filters['transaction_type'] = clean_input($_GET['transaction_type']);
        }
        
        if (!empty($_GET['student_id'])) {
            $transaction_filters['student_id'] = clean_input($_GET['student_id']);
        }
        
        if (!empty($_GET['start_date'])) {
            $transaction_filters['start_date'] = clean_input($_GET['start_date']);
        }
        
        if (!empty($_GET['end_date'])) {
            $transaction_filters['end_date'] = clean_input($_GET['end_date']);
        }
    }
    
    if (isset($_GET['filter_expenses'])) {
        if (!empty($_GET['category'])) {
            $expense_filters['category'] = clean_input($_GET['category']);
        }
        
        if (!empty($_GET['department'])) {
            $expense_filters['department'] = clean_input($_GET['department']);
        }
        
        if (!empty($_GET['start_date'])) {
            $expense_filters['start_date'] = clean_input($_GET['start_date']);
        }
        
        if (!empty($_GET['end_date'])) {
            $expense_filters['end_date'] = clean_input($_GET['end_date']);
        }
    }
}

// Get audit log entries with user information
$sql = "SELECT a.*, u.first_name, u.last_name, u.student_id 
        FROM audit_log a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC
        LIMIT 100"; // Limit to 100 records for performance
$result = mysqli_query($conn, $sql);
$audit_entries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $audit_entries[] = $row;
}

// Get financial transactions
$financial_transactions = get_financial_transactions($transaction_filters);

// Get expenses
$expenses = get_expenses($expense_filters);

// Get summary statistics
$audit_summary = get_audit_summary('month');

// Get users for dropdowns
$users_sql = "SELECT id, first_name, last_name, student_id FROM users WHERE status = 'approved' ORDER BY first_name, last_name";
$users_result = mysqli_query($conn, $users_sql);
$users = [];
while ($user_row = mysqli_fetch_assoc($users_result)) {
    $users[] = $user_row;
}

// Get departments for dropdowns
$departments_sql = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $departments_sql);
$departments = [];
while ($dept_row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $dept_row['department'];
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'transaction_recorded':
            $success_message = "Transaction recorded successfully!";
            break;
        case 'transaction_deleted':
            $success_message = "Transaction deleted successfully!";
            break;
        case 'expense_added':
            $success_message = "Expense added successfully!";
            break;
        case 'expense_deleted':
            $success_message = "Expense deleted successfully!";
            break;
        case 'audit_log_deleted':
            $success_message = "Audit log entry deleted successfully!";
            break;
        case 'excel_generated':
            $excel_file = isset($_SESSION['excel_file']) ? $_SESSION['excel_file'] : null;
            if ($excel_file) {
                // Check if it's a CSV file (our fallback)
                $extension = pathinfo($excel_file, PATHINFO_EXTENSION);
                if ($extension == 'csv') {
                    $success_message = "Data exported to CSV format. <a href='../$excel_file' download>Click here to download</a>";
                } else {
                    $success_message = "Excel file generated. <a href='../$excel_file' download>Click here to download</a>";
                }
                // Clear the session variable
                unset($_SESSION['excel_file']);
            } else {
                $success_message = "Export file generated successfully!";
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working Scholars Association</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: bold;
        }
        .dashboard-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .income-card {
            border-left-color: #28a745;
        }
        .expense-card {
            border-left-color: #dc3545;
        }
        .net-card {
            border-left-color: #007bff;
        }
        .counts-card {
            border-left-color: #ffc107;
        }
        .table-styled {
            width: 100%;
            border-collapse: collapse;
        }
        .empty-row td {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            background-color: #f8f9fa;
        }
        /* Prevent DataTables reinitialize errors from showing */
        .dt-info, .dataTables_info {
            visibility: hidden;
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
                        <h1 class="h3 mb-0 text-gray-800">Financial Audit</h1>
                    </div>
                    
                    <!-- Alert Messages -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Audit Dashboard -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 dashboard-card income-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Monthly Income</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($audit_summary['total_income'], 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 dashboard-card expense-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Monthly Expenses</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($audit_summary['total_expenses'], 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 dashboard-card net-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Net</div>
                                            <div class="h5 mb-0 font-weight-bold <?php echo $audit_summary['net'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                ₱<?php echo number_format($audit_summary['net'], 2); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow h-100 py-2 dashboard-card counts-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Transactions</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo array_sum($audit_summary['transaction_counts']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs mb-4" id="auditTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                                <i class="fas fa-exchange-alt me-1"></i> Financial Logs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab" aria-controls="expenses" aria-selected="false">
                                <i class="fas fa-file-invoice-dollar me-1"></i> Liquidation
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab" aria-controls="export" aria-selected="false">
                                <i class="fas fa-file-export me-1"></i> Export to Excel
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="audit-log-tab" data-bs-toggle="tab" data-bs-target="#audit-log" type="button" role="tab" aria-controls="audit-log" aria-selected="false">
                                <i class="fas fa-history me-1"></i> System Audit Log
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="auditTabsContent">
                        <!-- TRANSACTIONS TAB -->
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Financial Transactions</h6>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                        <i class="fas fa-plus me-1"></i> Record Transaction
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Filter Form -->
                                    <form method="GET" action="" class="row mb-4 g-3">
                                        <div class="col-md-2">
                                            <label for="transaction_type" class="form-label">Transaction Type</label>
                                            <select class="form-select form-select-sm" id="transaction_type" name="transaction_type">
                                                <option value="">All Types</option>
                                                <option value="tuition" <?php echo isset($transaction_filters['transaction_type']) && $transaction_filters['transaction_type'] == 'tuition' ? 'selected' : ''; ?>>Tuition</option>
                                                <option value="shop" <?php echo isset($transaction_filters['transaction_type']) && $transaction_filters['transaction_type'] == 'shop' ? 'selected' : ''; ?>>Shop</option>
                                                <option value="permit" <?php echo isset($transaction_filters['transaction_type']) && $transaction_filters['transaction_type'] == 'permit' ? 'selected' : ''; ?>>Permit</option>
                                                <option value="fine" <?php echo isset($transaction_filters['transaction_type']) && $transaction_filters['transaction_type'] == 'fine' ? 'selected' : ''; ?>>Fine</option>
                                                <option value="other" <?php echo isset($transaction_filters['transaction_type']) && $transaction_filters['transaction_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="student_id" class="form-label">Student ID</label>
                                            <input type="text" class="form-control form-control-sm" id="student_id" name="student_id" value="<?php echo isset($transaction_filters['student_id']) ? htmlspecialchars($transaction_filters['student_id']) : ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo isset($transaction_filters['start_date']) ? htmlspecialchars($transaction_filters['start_date']) : ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo isset($transaction_filters['end_date']) ? htmlspecialchars($transaction_filters['end_date']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" name="filter_transactions" class="btn btn-sm btn-primary me-2">
                                                <i class="fas fa-filter me-1"></i> Filter
                                            </button>
                                            <a href="audit.php" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-redo me-1"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                    
                                    <!-- Transactions Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="transactionsTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Student</th>
                                                    <th>Amount</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Receipt</th>
                                                    <th>Admin</th>
                                                    <th>Date/Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($financial_transactions)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center">No transactions found</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($financial_transactions as $transaction): ?>
                                                        <tr>
                                                            <td><?php echo $transaction['id']; ?></td>
                                                            <td>
                                                                <?php if ($transaction['user_id']): ?>
                                                                    <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                                                    <br><span class="small text-muted"><?php echo htmlspecialchars($transaction['student_id']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">N/A</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="<?php echo $transaction['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                ₱<?php echo number_format(abs($transaction['amount']), 2); ?>
                                                            </td>
                                                            <td><?php echo ucfirst(htmlspecialchars($transaction['transaction_type'])); ?></td>
                                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                            <td>
                                                                <?php if ($transaction['receipt_image']): ?>
                                                                    <a href="../<?php echo $transaction['receipt_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                        <i class="fas fa-file-invoice"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">None</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($transaction['admin_id']): ?>
                                                                    <?php echo htmlspecialchars($transaction['admin_first_name'] . ' ' . $transaction['admin_last_name']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">System</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M d, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger delete-transaction-btn" 
                                                                        data-id="<?php echo $transaction['id']; ?>"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteTransactionModal"
                                                                        style="z-index: 100; position: relative;">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EXPENSES TAB -->
                        <div class="tab-pane fade" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Expenses & Liquidations</h6>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                                        <i class="fas fa-plus me-1"></i> Add Expense
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Filter Form -->
                                    <form method="GET" action="" class="row mb-4 g-3">
                                        <div class="col-md-2">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select form-select-sm" id="category" name="category">
                                                <option value="">All Categories</option>
                                                <option value="operational" <?php echo isset($expense_filters['category']) && $expense_filters['category'] == 'operational' ? 'selected' : ''; ?>>Operational</option>
                                                <option value="salary" <?php echo isset($expense_filters['category']) && $expense_filters['category'] == 'salary' ? 'selected' : ''; ?>>Salary</option>
                                                <option value="departmental" <?php echo isset($expense_filters['category']) && $expense_filters['category'] == 'departmental' ? 'selected' : ''; ?>>Departmental</option>
                                                <option value="miscellaneous" <?php echo isset($expense_filters['category']) && $expense_filters['category'] == 'miscellaneous' ? 'selected' : ''; ?>>Miscellaneous</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="department" class="form-label">Department</label>
                                            <select class="form-select form-select-sm" id="department" name="department">
                                                <option value="">All Departments</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo isset($expense_filters['department']) && $expense_filters['department'] == $dept ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($dept); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="exp_start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control form-control-sm" id="exp_start_date" name="start_date" value="<?php echo isset($expense_filters['start_date']) ? htmlspecialchars($expense_filters['start_date']) : ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="exp_end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control form-control-sm" id="exp_end_date" name="end_date" value="<?php echo isset($expense_filters['end_date']) ? htmlspecialchars($expense_filters['end_date']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" name="filter_expenses" class="btn btn-sm btn-primary me-2">
                                                <i class="fas fa-filter me-1"></i> Filter
                                            </button>
                                            <a href="audit.php" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-redo me-1"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                    
                                    <!-- Expenses Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="expensesTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Amount</th>
                                                    <th>Category</th>
                                                    <th>Department</th>
                                                    <th>Description</th>
                                                    <th>Receipt</th>
                                                    <th>Admin</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($expenses)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">No expenses found</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($expenses as $expense): ?>
                                                        <tr>
                                                            <td><?php echo $expense['id']; ?></td>
                                                            <td class="text-danger">₱<?php echo number_format($expense['amount'], 2); ?></td>
                                                            <td><?php echo ucfirst(htmlspecialchars($expense['category'])); ?></td>
                                                            <td><?php echo htmlspecialchars($expense['department']); ?></td>
                                                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                                            <td>
                                                                <?php if ($expense['receipt_image']): ?>
                                                                    <a href="../<?php echo $expense['receipt_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                        <i class="fas fa-file-invoice"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">None</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($expense['first_name'] . ' ' . $expense['last_name']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger delete-expense-btn" 
                                                                        data-id="<?php echo $expense['id']; ?>"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteExpenseModal">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EXPORT TAB -->
                        <div class="tab-pane fade" id="export" role="tabpanel" aria-labelledby="export-tab">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Export Data to Excel</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    <h6 class="m-0 font-weight-bold">Financial Transactions Export</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="export_type" value="transactions">
                                                        
                                                        <div class="mb-3">
                                                            <label for="export_transaction_type" class="form-label">Transaction Type</label>
                                                            <select class="form-select" id="export_transaction_type" name="transaction_type">
                                                                <option value="">All Types</option>
                                                                <option value="tuition">Tuition</option>
                                                                <option value="shop">Shop</option>
                                                                <option value="permit">Permit</option>
                                                                <option value="fine">Fine</option>
                                                                <option value="other">Other</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="export_student_id" class="form-label">Student ID (optional)</label>
                                                            <input type="text" class="form-control" id="export_student_id" name="student_id">
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="export_start_date" class="form-label">Start Date</label>
                                                                    <input type="date" class="form-control" id="export_start_date" name="start_date">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="export_end_date" class="form-label">End Date</label>
                                                                    <input type="date" class="form-control" id="export_end_date" name="end_date">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <button type="submit" name="export_excel" class="btn btn-success">
                                                            <i class="fas fa-file-excel me-1"></i> Export Transactions
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    <h6 class="m-0 font-weight-bold">Expenses Export</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="export_type" value="expenses">
                                                        
                                                        <div class="mb-3">
                                                            <label for="export_category" class="form-label">Category</label>
                                                            <select class="form-select" id="export_category" name="category">
                                                                <option value="">All Categories</option>
                                                                <option value="operational">Operational</option>
                                                                <option value="salary">Salary</option>
                                                                <option value="departmental">Departmental</option>
                                                                <option value="miscellaneous">Miscellaneous</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="export_department" class="form-label">Department</label>
                                                            <select class="form-select" id="export_department" name="department">
                                                                <option value="">All Departments</option>
                                                                <?php foreach ($departments as $dept): ?>
                                                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                                                        <?php echo htmlspecialchars($dept); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="export_exp_start_date" class="form-label">Start Date</label>
                                                                    <input type="date" class="form-control" id="export_exp_start_date" name="start_date">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="export_exp_end_date" class="form-label">End Date</label>
                                                                    <input type="date" class="form-control" id="export_exp_end_date" name="end_date">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <button type="submit" name="export_excel" class="btn btn-success">
                                                            <i class="fas fa-file-excel me-1"></i> Export Expenses
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AUDIT LOG TAB -->
                        <div class="tab-pane fade" id="audit-log" role="tabpanel" aria-labelledby="audit-log-tab">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">System Audit Log</h6>
                                    <button class="btn btn-sm btn-outline-primary" id="refreshAudit">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($audit_entries)): ?>
                                        <p class="text-center text-muted my-5">No audit log entries found.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="auditTable" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>No.</th>
                                                        <th>User</th>
                                                        <th>Action</th>
                                                        <th>Description</th>
                                                        <th>IP Address</th>
                                                        <th>Date/Time</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($audit_entries as $entry): ?>
                                                        <tr>
                                                            <td><?php echo $entry['id']; ?></td>
                                                            <td>
                                                                <?php if ($entry['user_id']): ?>
                                                                    <?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?>
                                                                    <br><span class="small text-muted"><?php echo htmlspecialchars($entry['student_id']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">System</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($entry['action']); ?></td>
                                                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                                                            <td><?php echo htmlspecialchars($entry['ip_address']); ?></td>
                                                            <td><?php echo date('M d, Y g:i A', strtotime($entry['created_at'])); ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger delete-audit-btn" 
                                                                        data-id="<?php echo $entry['id']; ?>"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteAuditModal">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Record Financial Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">Student (optional)</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">N/A</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['student_id'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_type" class="form-label">Transaction Type *</label>
                                <select class="form-select" id="transaction_type" name="transaction_type" required>
                                    <option value="tuition">Tuition</option>
                                    <option value="shop">Shop</option>
                                    <option value="permit">Permit</option>
                                    <option value="fine">Fine</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (₱) *</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                                <div class="form-text">Positive number for income, negative for expense</div>
                            </div>
                            <div class="col-md-6">
                                <label for="receipt_image" class="form-label">Receipt Upload</label>
                                <input type="file" class="form-control" id="receipt_image" name="receipt_image">
                                <div class="form-text">Accepted formats: JPG, PNG, PDF (max 5MB)</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="record_transaction" class="btn btn-primary">Record Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExpenseModalLabel">Add Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="operational">Operational</option>
                                    <option value="salary">Salary</option>
                                    <option value="departmental">Departmental</option>
                                    <option value="miscellaneous">Miscellaneous</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department *</label>
                                <select class="form-select" id="department" name="department" required>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (₱) *</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="expense_amount" name="amount" required>
                            </div>
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="receipt_image" class="form-label">Receipt Upload</label>
                            <input type="file" class="form-control" id="expense_receipt_image" name="receipt_image">
                            <div class="form-text">Accepted formats: JPG, PNG, PDF (max 5MB)</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="expense_description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_expense" class="btn btn-primary">Add Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Transaction Confirmation Modal -->
    <div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTransactionModalLabel">Delete Transaction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this transaction? This action cannot be undone.</p>
                        <input type="hidden" name="transaction_id" id="delete_transaction_id" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_transaction" class="btn btn-danger">Delete Transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Expense Confirmation Modal -->
    <div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteExpenseModalLabel">Delete Expense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
                        <input type="hidden" name="expense_id" id="delete_expense_id" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_expense" class="btn btn-danger">Delete Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Audit Log Confirmation Modal -->
    <div class="modal fade" id="deleteAuditModal" tabindex="-1" aria-labelledby="deleteAuditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAuditModalLabel">Delete Audit Log Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this audit log entry? This action cannot be undone.</p>
                        <input type="hidden" name="log_id" id="delete_log_id" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_audit_log" class="btn btn-danger">Delete Audit Log Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    if (tab === 'audit-log') {
        // Find the tab button and trigger a click
        var auditLogTab = document.getElementById('audit-log-tab');
        if (auditLogTab) {
            auditLogTab.click();
        }
    }
});
</script>

    <!-- Bootstrap JS and other script references will be included via footer.php -->
    <?php 
    $use_datatables = true;
    // Add page specific JS if needed
    $page_specific_js = <<<EOT
    <script>
        $(document).ready(function() {
            // Wait for DOM to be fully loaded
            setTimeout(function() {
                // Initialize each table individually with proper error handling
                initializeTable('#auditTable');
                initializeTable('#transactionsTable');
                initializeTable('#expensesTable');
            }, 200);
            
            function initializeTable(tableId) {
                var table = $(tableId);
                if (table.length === 0) {
                    console.log('Table not found:', tableId);
                    return;
                }
                
                // If DataTable is already initialized, destroy it properly
                if ($.fn.DataTable.isDataTable(tableId)) {
                    console.log('Destroying existing DataTable instance for:', tableId);
                    $(tableId).DataTable().destroy();
                    // Clear the HTML to prevent stale data issues
                    table.find('tbody').empty();
                }
                
                // Check if the table has rows - if no rows, add a dummy row to prevent errors
                if (table.find('tbody tr').length === 0) {
                    var colCount = table.find('thead th').length;
                    var emptyRow = '<tr class="empty-row"><td colspan="' + colCount + '" class="text-center">No data available</td></tr>';
                    table.find('tbody').html(emptyRow);
                    
                    // For empty tables, just add basic styling without full DataTable functionality
                    table.addClass('table-styled');
                    return;
                }
                
                try {
                    // Initialize with basic options to minimize chance of errors
                    var dt = table.DataTable({
                        retrieve: true,
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        order: [[0, 'desc']],
                        columnDefs: [{
                            targets: '_all',
                            defaultContent: ''
                        }],
                        language: {
                            emptyTable: 'No data available',
                            zeroRecords: 'No matching records found'
                        }
                    });
                    
                    console.log('Successfully initialized DataTable for:', tableId);
                    return dt;
                } catch (error) {
                    console.error('Error initializing DataTable for ' + tableId, error);
                    // Add basic styling as fallback
                    table.addClass('table-styled');
                }
            }
            
            // Refresh audit log
            $('#refreshAudit').click(function() {
                location.reload();
            });
            
            // Use document for event delegation to ensure it works with dynamically created elements
            $(document).on('click', '.delete-transaction-btn', function(e) {
                e.preventDefault();
                var transactionId = $(this).data('id');
                $('#delete_transaction_id').val(transactionId);
                $('#deleteTransactionModal').modal('show');
            });
            
            $(document).on('click', '.delete-expense-btn', function(e) {
                e.preventDefault();
                var expenseId = $(this).data('id');
                $('#delete_expense_id').val(expenseId);
                $('#deleteExpenseModal').modal('show');
            });
            
            $(document).on('click', '.delete-audit-btn', function(e) {
                e.preventDefault();
                var logId = $(this).data('id');
                $('#delete_log_id').val(logId);
                $('#deleteAuditModal').modal('show');
            });
            
            // Add form submission handler to check IDs before submit
            $('#deleteTransactionModal form').submit(function(e) {
                var id = $('#delete_transaction_id').val();
                if (!id) {
                    console.error('No transaction ID set for deletion');
                    e.preventDefault();
                    alert('Error: No transaction ID specified');
                    return false;
                }
            });
            
            $('#deleteExpenseModal form').submit(function(e) {
                var id = $('#delete_expense_id').val();
                if (!id) {
                    console.error('No expense ID set for deletion');
                    e.preventDefault();
                    alert('Error: No expense ID specified');
                    return false;
                }
            });
            
            $('#deleteAuditModal form').submit(function(e) {
                var id = $('#delete_log_id').val();
                if (!id) {
                    console.error('No audit log ID set for deletion');
                    e.preventDefault();
                    alert('Error: No audit log ID specified');
                    return false;
                }
            });
            
            // File upload validation
            $('input[type="file"]').change(function() {
                var maxSize = 5 * 1024 * 1024; // 5MB
                var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                var file = this.files[0];
                
                if (file) {
                    // Check file size
                    if (file.size > maxSize) {
                        alert('File size exceeds 5MB limit.');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    if (!allowedTypes.includes(file.type)) {
                        alert('File type not allowed. Please upload JPG, PNG or PDF.');
                        this.value = '';
                        return;
                    }
                }
            });
        });
    </script>
EOT;
    
    include 'includes/footer.php';
    ?>
</body>
</html> 