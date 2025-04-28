<?php
// finance.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user has the required role (e.g., GLOBAL_ADMIN)
check_role(required_role: GLOBAL_ADMIN);

// Fetch departments for the filter dropdown
$departments = ['CS', 'BM']; // Add more departments if needed

// Fetch finance data based on the selected department (if any)
$selectedDepartment = $_GET['department'] ?? '';
$financeData = [];

if ($selectedDepartment) {
    // Connect to the selected department's database
    $pdo = getDatabaseConnection(strtolower($selectedDepartment));

    // Fetch finance data for the selected department
    $sql = "SELECT sf.finance_id, sf.student_id, s.first_name, s.last_name, sf.base_fees, sf.scholarship_amount, sf.amount_paid, sf.due_date, sf.status
            FROM student_finance sf
            JOIN students s ON sf.student_id = s.student_id
            ORDER BY sf.student_id ASC";
    $stmt = $pdo->query($sql);

    if ($stmt) {
        $financeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Add department information to each finance record
        foreach ($financeData as &$finance) {
            $finance['department'] = strtoupper($selectedDepartment);
        }
    }
} else {
    // Fetch finance data from all departments
    foreach ($departments as $department) {
        $pdo = getDatabaseConnection(strtolower($department));

        $sql = "SELECT sf.finance_id, sf.student_id, s.first_name, s.last_name, sf.base_fees, sf.scholarship_amount, sf.amount_paid, sf.due_date, sf.status
                FROM student_finance sf
                JOIN students s ON sf.student_id = s.student_id
                ORDER BY sf.student_id ASC";
        $stmt = $pdo->query($sql);

        if ($stmt) {
            $departmentFinanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($departmentFinanceData as $finance) {
                $finance['department'] = strtoupper($department); // Add department info
                $financeData[] = $finance;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="global_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li class="active"><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Student Payment Status</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Toggle Icon -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Search and Filter -->
                <div class="search-filter">
                    <form method="GET" action="finance.php">
                        <input type="text" id="search" name="search" placeholder="Search by ID or name" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <select id="filter-status" name="status">
                            <option value="">Filter by Status</option>
                            <option value="paid" <?php echo ($_GET['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="not-paid" <?php echo ($_GET['status'] ?? '') === 'not-paid' ? 'selected' : ''; ?>>Not Paid</option>
                            <option value="partially-paid" <?php echo ($_GET['status'] ?? '') === 'partially-paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        </select>
                        <select id="filter-year" name="year">
                            <option value="">Filter by Year</option>
                            <option value="2024" <?php echo ($_GET['year'] ?? '') === '2024' ? 'selected' : ''; ?>>2024</option>
                            <option value="2025" <?php echo ($_GET['year'] ?? '') === '2025' ? 'selected' : ''; ?>>2025</option>
                            <option value="2026" <?php echo ($_GET['year'] ?? '') === '2026' ? 'selected' : ''; ?>>2026</option>

                        </select>
                        <select id="filter-department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department; ?>" <?php echo ($selectedDepartment === $department) ? 'selected' : ''; ?>>
                                    <?php echo $department; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn">Apply</button>
                    </form>
                </div>

                <!-- Finance List Subtitle -->
                <h2 class="finance-list-subtitle">Student Payment Status</h2>

                <!-- Finance List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Amount (£)</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($financeData)): ?>
                                <tr>
                                    <td colspan="6">No finance records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($financeData as $finance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($finance['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['first_name'] . ' ' . $finance['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['base_fees'] - $finance['scholarship_amount']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['due_date']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['status']); ?></td>
                                        <td>
                                            <button class="btn-edit" onclick="editPayment('<?php echo $finance['finance_id']; ?>')"><i class="fas fa-edit"></i></button>
                                            <button class="btn-delete" onclick="deletePayment('<?php echo $finance['finance_id']; ?>')"><i class="fas fa-trash-alt"></i></button>
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

    <script>
        // Function to redirect to edit payment page
        function editPayment(paymentId) {
            window.location.href = `edit_payment.php?id=${paymentId}`;
        }

        // Function to delete a payment
        function deletePayment(paymentId) {
            if (confirm(`Are you sure you want to delete Payment ID ${paymentId}?`)) {
                alert(`Payment ID ${paymentId} deleted`);
                // You can send an API request here to delete the payment from the database
            }
        }
    </script>
</body>
</html>