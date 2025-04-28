<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Array to store data for each department
$departmentData = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details using the student_id stored in the session
        $studentId = $_SESSION['student_id'];

        // Fetch programme details
        $programmeQuery = "
            SELECT p.programme_name, p.programme_id, p.duration_years, d.department_name, 
                d.department_id, pe.current_year, pe.programme_start_date, pe.programme_end_date
            FROM programme_enrolment pe
            JOIN programme p ON pe.programme_id = p.programme_id
            JOIN departments d ON p.department_id = d.department_id
            WHERE pe.student_id = :student_id;
        ";

        $programmeStmt = $pdo->prepare($programmeQuery);
        $programmeStmt->execute(['student_id' => $studentId]);
        $programme = $programmeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$programme) {
            throw new Exception("Programme details not found for department ID: $departmentId.");
        }

        // Fetch assigned tutors
        $assignedTutors = "
            SELECT CONCAT(s.first_name, ' ', s.last_name) AS staff_name
            FROM staff s
            JOIN academic_tutor_assigned ata ON s.staff_id = ata.staff_id
            WHERE student_id = :student_id;
        ";

        $tutorStmt = $pdo->prepare($assignedTutors);
        $tutorStmt->execute(['student_id' => $studentId]);
        $tutors = $tutorStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch modules taken by the student
        $modulesQuery = "
            SELECT
                m.module_id,
                m.module_name,
                m.level,
                m.credits,
                sm.status,
                sm.academic_year,
                g.grade
            FROM student_modules sm
            JOIN modules m ON sm.module_id = m.module_id
            LEFT JOIN grade g ON sm.module_id = g.module_id AND sm.student_id = g.student_id
            WHERE sm.student_id = :student_id
            ORDER BY sm.academic_year DESC;
        ";

        $modulesStmt = $pdo->prepare($modulesQuery);
        $modulesStmt->execute(['student_id' => $studentId]);
        $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch academic progress
        $progressQuery = "
            SELECT SUM(m.credits) AS total_credits
            FROM student_modules sm
            JOIN modules m ON sm.module_id = m.module_id
            WHERE sm.student_id = :student_id AND sm.status = 'Completed';
        ";

        $progressStmt = $pdo->prepare($progressQuery);
        $progressStmt->execute(['student_id' => $studentId]);
        $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);

        if (!$progress) {
            $progress = ['total_credits' => 0]; // Default if no progress found
        }

        // Store all data for this department
        $departmentData[] = [
            'department_id' => $departmentId,
            'programme' => $programme,
            'tutors' => $tutors,
            'modules' => $modules,
            'progress' => $progress,
        ];
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme Details</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li class="active"><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li><a href="disability_request.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Programme & Modules</h1>
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
                <?php foreach ($departmentData as $data): ?>
                    <div class="person-details-container">
                        <!-- Programme Information -->
                        <div class="details-section">
                            <h2>Programme Information (Department: <?php echo htmlspecialchars($data['programme']['department_id']); ?>)</h2>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Programme Name:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['programme_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Programme ID:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['programme_id']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Duration (Years):</label>
                                    <span><?php echo htmlspecialchars($data['programme']['duration_years']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Department:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['department_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Academic Tutor:</label>
                                    <span>
                                        <?php
                                        if (!empty($data['tutors'])) {
                                            foreach ($data['tutors'] as $tutor) {
                                                echo htmlspecialchars($tutor['staff_name']) . "<br>";
                                            }
                                        } else {
                                            echo "No tutor assigned";
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <label>Current Year:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['current_year']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Start Date:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['programme_start_date']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Expected End Date:</label>
                                    <span><?php echo htmlspecialchars($data['programme']['programme_end_date']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Modules Taken -->
                        <div class="details-section">
                            <h2>Modules Taken</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Module Code</th>
                                        <th>Module Name</th>
                                        <th>Level</th>
                                        <th>Credits</th>
                                        <th>Status</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['modules'] as $module): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($module['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($module['module_id']); ?></td>
                                            <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                            <td><?php echo htmlspecialchars($module['level']); ?></td>
                                            <td><?php echo htmlspecialchars($module['credits']); ?></td>
                                            <td class="status<?php echo htmlspecialchars($module['status']); ?>"><?php echo htmlspecialchars($module['status']); ?></td>
                                            <td><?php echo htmlspecialchars($module['grade']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Academic Progress -->
                        <div class="details-section">
                            <h2>Academic Progress</h2>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($data['progress']['total_credits'] / 360) * 100; ?>%"></div>
                                </div>
                                <div class="progress-stats">
                                    <span>Completed: <?php echo htmlspecialchars($data['progress']['total_credits']); ?>/360 Credits (<?php echo round(($data['progress']['total_credits'] / 360) * 100, 2); ?>%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>