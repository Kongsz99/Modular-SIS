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

// Initialize the array to store all requests
$allRequests = [];

// Loop through each department and fetch pending disability requests
foreach ($departments as $dept) {
    $departmentId = $dept['department_id'];
    $departmentName = $dept['department_name']; // Get the department name

    // Connect to the department's database
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch pending disability requests
    $disabilityQuery = "
        SELECT disability_id AS request_id, student_id, disability_type AS type, requested_accommodation AS description, document, 'disability' AS request_type
        FROM disability_requests
        WHERE status = 'pending'
    ";
    $disabilityStmt = $departmentPdo->prepare($disabilityQuery);
    $disabilityStmt->execute();
    $disabilityRequests = $disabilityStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add department information to each disability request
    foreach ($disabilityRequests as &$request) {
        $request['department_name'] = $departmentName;
        $request['department_id'] = $departmentId;
    }

    // Fetch pending EC requests
    $ecQuery = "
        SELECT ec_id AS request_id, student_id, ec_type AS type, ec_description AS description, supporting_document AS document, 'ec' AS request_type
        FROM ec_requests
        WHERE status = 'pending'
    ";
    $ecStmt = $departmentPdo->prepare($ecQuery);
    $ecStmt->execute();
    $ecRequests = $ecStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add department information to each EC request
    foreach ($ecRequests as &$request) {
        $request['department_name'] = $departmentName;
        $request['department_id'] = $departmentId;
    }

    // Combine both types of requests
    $allRequests = array_merge($allRequests, $disabilityRequests, $ecRequests);
}

// Handle approval or rejection of a request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; // 'approve' or 'reject'
    $requestId = $_POST['request_id'];
    $requestType = $_POST['request_type']; // 'disability' or 'ec'
    $departmentId = $_POST['department_id'];

    // Validate input
    if (empty($action) || empty($requestId) || empty($requestType) || empty($departmentId)) {
        die("Invalid request.");
    }

    // Connect to the department's database
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Determine the table and ID column based on the request type
    $table = ($requestType === 'disability') ? 'disability_requests' : 'ec_requests';
    $idColumn = ($requestType === 'disability') ? 'disability_id' : 'ec_id';

    // Update the request status
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $updateQuery = "
        UPDATE $table
        SET status = :status
        WHERE $idColumn = :request_id
    ";
    $updateStmt = $departmentPdo->prepare($updateQuery);
    $updateStmt->execute([
        'status' => $status,
        'request_id' => $requestId,
    ]);

    // Redirect to avoid form resubmission
    header("Location: requests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Combined Requests</title>
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
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li class="active"><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
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
                    <h1>Combined Requests</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                
                <!-- Logout Button -->
                    <a href="../logout.php" class="logout-btn">
                        <button>Logout</button>
                    </a>
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
                            <th>Request Type</th>
                            <th>Student ID</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Document</th>
                            <th>Department</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?></td>
                                <td><?php echo htmlspecialchars($request['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($request['type']); ?></td>
                                <td><?php echo htmlspecialchars($request['description']); ?></td>
                                <td>
                                    <?php if ($request['document']): ?>
                                        <a href="<?php echo htmlspecialchars($request['document']); ?>" target="_blank">View Document</a>
                                    <?php else: ?>
                                        No Document
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                <td>
                                    <form method="POST" action="requests.php" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                        <input type="hidden" name="request_type" value="<?php echo htmlspecialchars($request['request_type']); ?>">
                                        <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($request['department_id']); ?>">
                                        <button type="submit" name="action" value="approve" class="btn-approve"> <i class="fas fa-check-circle"></i> 

                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn-reject"><i class="fas fa-times-circle"></i>
                                        </button>
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