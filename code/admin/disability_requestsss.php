<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Fetch the list of departments from the central database
$departmentsQuery = "SELECT department_id, department_name FROM departments";
$departmentsStmt = $pdo->prepare($departmentsQuery);
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($departments)) {
    die("No departments found in the central database.");
}

// Array to store all disability requests
$disabilityRequests = [];

// Loop through each department and fetch pending disability requests
foreach ($departments as $dept) {
    $departmentId = $dept['department_id'];

    // Connect to the department's database
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch pending disability requests
    $requestsQuery = "
        SELECT disability_id, student_id, disability_type, requested_accommodation, document
        FROM disability_accommodation
        WHERE status = 'pending'
    ";
    $requestsStmt = $departmentPdo->prepare($requestsQuery);
    $requestsStmt->execute();
    $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add department ID and name to each request
    foreach ($requests as $request) {
        $request['department_id'] = $departmentId;
        $request['department_name'] = $dept['department_name'];
        $disabilityRequests[] = $request;
    }
}

// Handle approval or rejection of a request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; // 'approve' or 'reject'
    $disabilityId = $_POST['disability_id'];
    $departmentId = $_POST['department_id'];

    // Validate input
    if (empty($action) || empty($disabilityId) || empty($departmentId)) {
        die("Invalid request.");
    }

    // Connect to the department's database
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Update the request status
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $updateQuery = "
        UPDATE disability_accommodation
        SET status = :status
        WHERE disability_id = :disability_id
    ";
    $updateStmt = $departmentPdo->prepare($updateQuery);
    $updateStmt->execute([
        'status' => $status,
        'disability_id' => $disabilityId,
    ]);

    // Redirect to avoid form resubmission
    header("Location: disability_requests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Disability Accommodation Requests</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .btn-approve, .btn-reject {
            padding: 5px 10px;
            font-size: 14px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar (Same as other admin pages) -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="dashboard.html"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="student.html"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.html"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.html"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.html"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.html"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.html"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.html"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.html"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li class="active"><a href="disability_requests.php"><i class="fas fa-wheelchair"></i> Disability Requests</a></li>
                <li><a href="assign_tutor.html"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.html"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.html"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Disability Accommodation Requests</h1>
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
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Disability Type</th>
                            <th>Requested Accommodation</th>
                            <th>Document</th>
                            <th>Department</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disabilityRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($request['disability_type']); ?></td>
                                <td><?php echo htmlspecialchars($request['requested_accommodation']); ?></td>
                                <td>
                                    <?php if ($request['document']): ?>
                                        <a href="<?php echo htmlspecialchars($request['document']); ?>" target="_blank">View Document</a>
                                    <?php else: ?>
                                        No Document
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                <td>
                                    <form method="POST" action="disability_requests.php" style="display: inline;">
                                        <input type="hidden" name="disability_id" value="<?php echo htmlspecialchars($request['disability_id']); ?>">
                                        <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($request['department_id']); ?>">
                                        <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>