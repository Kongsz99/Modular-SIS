<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

check_role(DEPARTMENT_ADMIN);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Get the student ID from the query parameter
$studentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$studentId) {
    die("Student ID is missing.");
}

// Fetch student details from the database
$studentQuery = "
    SELECT s.*, u.username, CONCAT(a.address, ', ', a.city, ', ', a.state, ', ', a.postcode, ', ', a.country) AS full_address, p.*, pe.*
    FROM students s
    JOIN programme_enrolment pe ON s.student_id = pe.student_id
    JOIN programme p ON pe.programme_id = p.programme_id
    JOIN users u ON s.user_id = u.user_id
    JOIN address a ON s.address_id = a.address_id
    WHERE s.student_id = :student_id
";
$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->execute(['student_id' => $studentId]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

// Extract student details
$fullName = $student['first_name'] . ' ' . $student['last_name'];
$gender = $student['gender'];
$username = $student['username'];
$dob = $student['date_of_birth'];
$contactNumber = $student['phone'];
$personalEmail = $student['personal_email'];
$address = $student['full_address'];
$universityEmail = $student['uni_email'];

// Extract programme details
$programmeName = $student['programme_name'];
// $department = $student['department_name'];
$duration = $student['duration_years'];
$currentYear = $student['current_year'];
$programmeStartDate = $student['programme_start_date'];
$status = $student['status'];
$programmeEndDate = $student['programme_end_date'];

// Fetch modules taken by the student
$modulesTakenQuery = "
    SELECT m.module_id, m.module_name, m.credits, m.level, g.grade, sm.academic_year, pe.current_year
        FROM student_modules sm
        JOIN modules m ON sm.module_id = m.module_id
        LEFT JOIN grade g ON sm.student_id = g.student_id AND sm.module_id = g.module_id
        JOIN programme_enrolment pe ON sm.student_id = pe.student_id
        WHERE sm.student_id = :student_id AND m.level < pe.current_year
";
$modulesTakenStmt = $pdo->prepare($modulesTakenQuery);
$modulesTakenStmt->execute(['student_id' => $studentId]);
$modulesTaken = $modulesTakenStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current modules for the student
$currentModulesQuery = "
    SELECT m.module_id, m.module_name, m.credits, m.level, sm.academic_year, pe.current_year
        FROM student_modules sm
        JOIN modules m ON sm.module_id = m.module_id
        JOIN programme_enrolment pe ON sm.student_id = pe.student_id
        WHERE sm.student_id = :student_id AND m.level = pe.current_year
";
$currentModulesStmt = $pdo->prepare($currentModulesQuery);
$currentModulesStmt->execute(['student_id' => $studentId]);
$currentModules = $currentModulesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch tuition fee details
$tuitionFeeQuery = "
    SELECT sf.base_fees, sf.scholarship_amount, sf.amount_paid, sf.due_date, sf.status
    FROM student_finance sf
    WHERE sf.student_id = :student_id
";
$tuitionFeeStmt = $pdo->prepare($tuitionFeeQuery);
$tuitionFeeStmt->execute(['student_id' => $studentId]);
$tuitionFeeDetails = $tuitionFeeStmt->fetchAll(PDO::FETCH_ASSOC);
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
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
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
                <!-- Back Button -->
                <a href="student.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Student List
                </a>

                <!-- Student Details Container -->
                <div class="person-details-container">
                    <!-- Personal Details -->
                    <div class="details-section">
                        <h2>Personal Details</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Full Name:</label>
                                <span id="full-name"><?php echo htmlspecialchars($fullName); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Student ID:</label>
                                <span id="student-id"><?php echo htmlspecialchars($studentId); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Gender:</label>
                                <span id="gender"><?php echo htmlspecialchars($gender); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Username:</label>
                                <span id="username"><?php echo htmlspecialchars($username); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Date of Birth:</label>
                                <span id="dob"><?php echo htmlspecialchars($dob); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Contact Number:</label>
                                <span id="contact-number"><?php echo htmlspecialchars($contactNumber); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Personal Email:</label>
                                <span id="personal-email"><?php echo htmlspecialchars($personalEmail); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Address:</label>
                                <span id="address"><?php echo htmlspecialchars($address); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>University Email:</label>
                                <span id="university-email"><?php echo htmlspecialchars($universityEmail); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Programme Details -->
                    <div class="details-section">
                        <h2>Programme Details</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Programme Name:</label>
                                <span id="programme-name"><?php echo htmlspecialchars($programmeName); ?></span>
                            </div>
                            <!-- <div class="detail-item">
                                <label>Department:</label>
                                <span id="department"><?php echo htmlspecialchars($department); ?></span>
                            </div> -->
                            <div class="detail-item">
                                <label>Duration (Years):</label>
                                <span id="duration"><?php echo htmlspecialchars($duration); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Current Year:</label>
                                <span id="current-year"><?php echo htmlspecialchars($currentYear); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Programme Start Date:</label>
                                <span id="programme-start-date"><?php echo htmlspecialchars($programmeStartDate); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span id="status"><?php echo htmlspecialchars($status); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Programme End Date:</label>
                                <span id="programme-end-date"><?php echo htmlspecialchars($programmeEndDate); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modules Taken -->
                    <div class="details-section">
                        <h2>Modules Taken</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Credits</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modulesTaken as $module): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($module['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($module['module_id']); ?></td>
                                        <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                        <td><?php echo htmlspecialchars($module['credits']); ?></td>
                                        <td><?php echo htmlspecialchars($module['grade'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Current Modules -->
                    <div class="details-section">
                        <h2>Current Modules</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Credits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currentModules as $module): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($module['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($module['module_id']); ?></td>
                                        <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                        <td><?php echo htmlspecialchars($module['credits']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tuition Fee Details -->
                    <!-- <div class="details-section">
                        <h2>Tuition Fee Details</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tuitionFeeDetails as $fee): ?>
                                    <tr>
                                        <td>Total Tuition Fee:</td>
                                        <td><?php echo htmlspecialchars($fee['base_fees']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Scholarship Applied:</td>
                                        <td><?php echo htmlspecialchars($fee['scholarship_amount']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Amount Paid:</td>
                                        <td><?php echo htmlspecialchars($fee['amount_paid']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Due Date:</td>
                                        <td><?php echo htmlspecialchars($fee['due_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Payment Status:</td>
                                        <td><?php echo htmlspecialchars($fee['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>