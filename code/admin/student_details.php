<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Get the student ID from the query parameter
$studentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$studentId) {
    die("Student ID is missing.");
}

// Fetch the list of departments from the central database
$departmentsQuery = "SELECT department_id, department_name FROM departments";
$departmentsStmt = $pdo->prepare($departmentsQuery);
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($departments)) {
    die("No departments found in the central database.");
}

// First pass: Find the student details from any department
$studentDetails = null;
$studentDepartmentData = [];

foreach ($departments as $dept) {
    $departmentId = $dept['department_id'];
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch student details from the department's database
    $studentQuery = "
        SELECT s.*, u.username, CONCAT(a.address, ', ', a.city, ', ', a.state, ', ', a.postcode, ', ', a.country) AS full_address
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN address a ON s.address_id = a.address_id
        WHERE s.student_id = :student_id
    ";
    $studentStmt = $departmentPdo->prepare($studentQuery);
    $studentStmt->execute(['student_id' => $studentId]);
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $studentDetails = $student;
        break; // Found the student, we can stop looking
    }
}

if (!$studentDetails) {
    die("❌ No student found with ID: " . htmlspecialchars($studentId));
}

// Second pass: Collect all department-specific data
foreach ($departments as $dept) {
    $departmentId = $dept['department_id'];
    $departmentPdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch programme enrolment details for the student in this department
    $programmeEnrolmentQuery = "
        SELECT p.programme_id, p.programme_name, p.duration_years, p.department_id, d.department_name,
               pe.current_year, pe.programme_start_date, pe.status, pe.programme_end_date
        FROM programme_enrolment pe
        JOIN programme p ON pe.programme_id = p.programme_id
        JOIN departments d ON p.department_id = d.department_id
        WHERE pe.student_id = :student_id
    ";
    $programmeEnrolmentStmt = $departmentPdo->prepare($programmeEnrolmentQuery);
    $programmeEnrolmentStmt->execute(['student_id' => $studentId]);
    $programmes = $programmeEnrolmentStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($programmes)) {
        continue; // Skip if no programmes in this department
    }

    // Fetch modules taken by the student in this department
    $modulesTakenQuery = "
        SELECT m.module_id, m.module_name, m.credits, m.level, g.grade, sm.academic_year, pe.current_year
        FROM student_modules sm
        JOIN modules m ON sm.module_id = m.module_id
        LEFT JOIN grade g ON sm.student_id = g.student_id AND sm.module_id = g.module_id
        JOIN programme_enrolment pe ON sm.student_id = pe.student_id
        WHERE sm.student_id = :student_id AND m.level < pe.current_year
    ";
    $modulesTakenStmt = $departmentPdo->prepare($modulesTakenQuery);
    $modulesTakenStmt->execute(['student_id' => $studentId]);
    $modulesTaken = $modulesTakenStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current modules for the student in this department
    $currentModulesQuery = "
        SELECT m.module_id, m.module_name, m.credits, m.level, sm.academic_year, pe.current_year
        FROM student_modules sm
        JOIN modules m ON sm.module_id = m.module_id
        JOIN programme_enrolment pe ON sm.student_id = pe.student_id
        WHERE sm.student_id = :student_id AND m.level = pe.current_year
    ";
    $currentModulesStmt = $departmentPdo->prepare($currentModulesQuery);
    $currentModulesStmt->execute(['student_id' => $studentId]);
    $currentModules = $currentModulesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tuition fee details for the student in this department
    $tuitionFeeQuery = "
        SELECT sf.base_fees, sf.scholarship_amount, sf.amount_paid, sf.due_date, sf.status
        FROM student_finance sf
        WHERE sf.student_id = :student_id
    ";
    $tuitionFeeStmt = $departmentPdo->prepare($tuitionFeeQuery);
    $tuitionFeeStmt->execute(['student_id' => $studentId]);
    $tuitionFeeDetails = $tuitionFeeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add this department's data to our collection
    $studentDepartmentData[] = [
        'programmes' => $programmes,
        'modulesTaken' => $modulesTaken,
        'currentModules' => $currentModules,
        'tuitionFeeDetails' => $tuitionFeeDetails,
        'department' => $dept['department_name']
    ];
}

// Check if we found any department data
if (empty($studentDepartmentData)) {
    die("❌ No programme data found for the student in any department.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
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
                <li class="active"><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
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
                    <h1>Student Details</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
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
                <!-- Back Button -->
                <a href="student.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Student List
                </a>

                <!-- Student Details Container -->
                <div class="person-details-container">
                    <div class="details-section">
                        <h2>Student Personal Information</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Full Name:</label> 
                                <span><?= htmlspecialchars($studentDetails['first_name'] . ' ' . $studentDetails['last_name']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Student ID:</label>
                                <span><?= htmlspecialchars($studentDetails['student_id']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Gender:</label>
                                <span><?= htmlspecialchars($studentDetails['gender']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Username:</label>
                                <span><?= htmlspecialchars($studentDetails['username']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Date of Birth:</label>
                                <span><?= htmlspecialchars($studentDetails['date_of_birth']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Contact Number:</label>
                                <span><?= htmlspecialchars($studentDetails['phone']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Personal Email:</label>
                                <span id="personal-email"><?php echo htmlspecialchars($studentDetails['personal_email']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Address:</label>
                                <span id="address"><?php echo htmlspecialchars($studentDetails['full_address']) ?></span>
                            </div>
                            <div class="detail-item">
                                <label>University Email:</label>
                                <span id="university-email"><?php echo htmlspecialchars($studentDetails['uni_email']) ?></span>
                            </div>
                        </div>
                    </div>

                    <?php foreach ($studentDepartmentData as $deptData): ?>
                        <div class="details-section">
                            <h2>Department: <?= htmlspecialchars($deptData['department']) ?></h2>

                            <!-- Programmes -->
                            <?php foreach ($deptData['programmes'] as $programme): ?>
                                <div class="details-section">
                                <h3>Programme Details</h3>
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <label>Programme Name:</label>
                                        <span id="programme-name"><?= htmlspecialchars($programme['programme_name']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Duration (Years):</label>
                                        <span id="duration"><?= htmlspecialchars($programme['duration_years']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Current Year:</label>
                                        <span id="current-year"><?= htmlspecialchars($programme['current_year']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Programme Start Date:</label>
                                        <span id="programme-start-date"><?= htmlspecialchars($programme['programme_start_date']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Status:</label>
                                        <span id="status"><?= htmlspecialchars($programme['status']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Programme End Date:</label>
                                        <span id="programme-end-date"><?= htmlspecialchars($programme['programme_end_date']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Modules Taken -->
                            <div class="details-section">
                            <h3>Modules Taken</h3>
                            <table>
                                <thead>
                                    <tr><th>Year</th><th>ID</th><th>Title</th><th>Credits</th><th>Grade</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deptData['modulesTaken'] as $module): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($module['academic_year']) ?></td>
                                            <td><?= htmlspecialchars($module['module_id']) ?></td>
                                            <td><?= htmlspecialchars($module['module_name']) ?></td>
                                            <td><?= htmlspecialchars($module['credits']) ?></td>
                                            <td><?= htmlspecialchars($module['grade'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>

                            <!-- Current Modules -->
                            <div class="details-section">
                            <h3>Current Modules</h3>
                            <table>
                                <thead>
                                    <tr><th>Year</th><th>ID</th><th>Title</th><th>Credits</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deptData['currentModules'] as $module): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($module['academic_year']) ?></td>
                                            <td><?= htmlspecialchars($module['module_id']) ?></td>
                                            <td><?= htmlspecialchars($module['module_name']) ?></td>
                                            <td><?= htmlspecialchars($module['credits']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>

                            <!-- Tuition Fees -->
                            <div class="details-section">
                            <h3>Tuition Fee Details</h3>
                            <table>
                                <tbody>
                                    <?php foreach ($deptData['tuitionFeeDetails'] as $fee): ?>
                                        <tr><td><strong>Total Fee:</strong></td><td><?= htmlspecialchars($fee['base_fees']) ?></td></tr>
                                        <tr><td><strong>Scholarship:</strong></td><td><?= htmlspecialchars($fee['scholarship_amount']) ?></td></tr>
                                        <tr><td><strong>Paid:</strong></td><td><?= htmlspecialchars($fee['amount_paid']) ?></td></tr>
                                        <tr><td><strong>Status:</strong></td><td><?= htmlspecialchars($fee['status']) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>