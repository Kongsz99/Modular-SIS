<?php
// module_details.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is logged in and has the appropriate role
check_role(required_role: GLOBAL_ADMIN & STAFF); // Adjust the role as needed

// Define departments
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
    // Add more departments if needed
];

// Get the module ID and department ID from the URL
$moduleId = $_GET['id'] ?? '';
$selectedDepartment = $_GET['department_id'] ?? '';

if (empty($moduleId)) {
    die("Module ID is required.");
}

// Initialize module details
$module = null;

if ($selectedDepartment) {
    // Connect to the selected department's database
    $pdo = getDatabaseConnection(strtolower($selectedDepartment));
    $sql = "SELECT * FROM modules WHERE module_id = :module_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['module_id' => $moduleId]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($module) {
        // Add department information to the module
        $module['department'] = strtoupper($selectedDepartment);
        foreach ($departments as $dept) {
            if ($dept['department_id'] === $selectedDepartment) {
                $module['department_name'] = $dept['department_name'];
                break;
            }
        }
        // Fetch assigned lecturers for this module
        $lecturersQuery = "
            SELECT s.staff_id, s.first_name, s.last_name, CONCAT(s.first_name, ' ', s.last_name) AS full_name
            FROM assigned_lecturers al
            JOIN staff s ON al.staff_id = s.staff_id
            WHERE al.module_id = :module_id
        ";
        $lecturersStmt = $pdo->prepare($lecturersQuery);
        $lecturersStmt->execute(['module_id' => $moduleId]);
        $assignedLecturers = $lecturersStmt->fetchAll(PDO::FETCH_ASSOC);
        $module['lecturers'] = $assignedLecturers;
        } 
    } else {
        // Fetch module from all departments
        foreach ($departments as $department) {
            $pdo = getDatabaseConnection(strtolower($department['department_id']));
            $sql = "SELECT * FROM modules WHERE module_id = :module_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['module_id' => $moduleId]);
            $departmentModule = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($departmentModule) {
                $departmentModule['department'] = strtoupper($department['department_id']);
                $departmentModule['department_name'] = $department['department_name'];
                $module = $departmentModule;
                break; // Stop searching once the module is found
            }
        }
    }

if (!$module) {
    die("Module not found.");
}

// Extract module details
$moduleCode = $module['module_id'];
$moduleName = $module['module_name'];
$department = $module['department'];
$departmentName = $module['department_name'];
$level = $module['level'];
// $associatedProgram = $module['dep'];
$credits = $module['credits'];
$exam_weight = $module['exam_weight'];
$assignment_weight = $module['assignment_weight'];
// $startDate = $module['start_date'];
// $endDate = $module['end_date'];
// $moduleLeader = $module['module_leader'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Details</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Add styles for the "View Students" button */
        .module-title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .module-title-container h2 {
            margin: 0;
        }
        .view-students-button {
            padding: 8px 16px;
            font-size: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .view-students-button:hover {
            background-color: #0056b3;
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
                <li class="active"><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
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
                    <h1>Module Management</h1>
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
                <!-- Back Button -->
                <a href="module.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Module List
                </a>

                <!-- Staff Details Container -->
                <div class="person-details-container">
                    <!-- Two-Column Layout for Personal and Job Details -->
                    <div class="details-grid-container">
                        <!-- Personal Details -->
                        <div>
                            <!-- Module Title with "View Students" Button -->
                            <div class="module-title-container">
                                <h2><?php echo htmlspecialchars("$moduleCode : $moduleName"); ?></h2>
                                <button class="view-students-button" onclick="viewStudents('<?php echo $moduleCode; ?>')">
                                    <i class="fas fa-users"></i> View Students
                                </button>
                            </div>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Module Code:</label>
                                    <span id="module-code"><?php echo htmlspecialchars($moduleCode); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Department:</label>
                                    <span id="department"><?php echo htmlspecialchars($departmentName); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Module Name:</label>
                                    <span id="module-name"><?php echo htmlspecialchars($moduleName); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Level:</label>
                                    <span id="level"><?php echo htmlspecialchars($level); ?></span>
                                </div>
                                <!-- <div class="detail-item">
                                    <label>Associated Program:</label>
                                    <span id="associated-program"><?php echo htmlspecialchars($associatedProgram); ?></span>
                                </div> -->
                                <div class="detail-item">
                                    <label>Credits:</label>
                                    <span id="credits"><?php echo htmlspecialchars($credits); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Exam Percentage:</label>
                                    <span id="exam"><?php echo htmlspecialchars($exam_weight); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Assignment Percentage:</label>
                                    <span id="assignment"><?php echo htmlspecialchars($assignment_weight); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="details-section">
                            <h2>Teaching Staff</h2>
                            <?php if (!empty($module['lecturers'])): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Lecturer ID</th>
                                                <th>Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($module['lecturers'] as $lecturer): ?>
                                                <tr>
                                                    <td><a href="staff_details.php?id=<?= $lecturer['staff_id'] ?>&department_id=<?= $department ?>" style="text-decoration: none;"><?= htmlspecialchars($lecturer['staff_id']) ?></td>
                                                    <td><a href="staff_details.php?id=<?= $lecturer['staff_id'] ?>&department_id=<?= $department ?>" style="text-decoration: none;"><?= htmlspecialchars($lecturer['full_name']) ?></td>
                                                    <!-- <td>
                                                        <a href="staff_details.php?id=<?= $lecturer['staff_id'] ?>&department_id=<?= $department ?>" class="btn btn-view" style="text-decoration: none;">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td> -->
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No lecturers assigned to this module.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to handle "View Students" button click
        function viewStudents(moduleId) {
            // Redirect to a page that lists students enrolled in the module
            window.location.href = `module_students.php?id=${moduleId}`;
        }
    </script>
</body>
</html>