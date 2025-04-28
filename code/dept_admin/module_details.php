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

// Get the module ID from the query parameter
$moduleId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$moduleId) {
    die("Module ID is missing.");
}

// Fetch module details from the database
$stmt = $pdo->prepare("
    SELECT m.*, CONCAT(s.first_name, ' ', s.last_name) AS module_leader_name
    FROM modules m
    LEFT JOIN assigned_lecturers al ON m.module_id = al.module_id
    LEFT JOIN staff s ON al.staff_id = s.staff_id
    WHERE m.module_id = :module_id
");

$stmt->execute(['module_id' => $moduleId]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    die("Module not found.");
}

// Extract module details
$moduleId = $module['module_id'];
$moduleName = $module['module_name'];
$level = $module['level'];
$credits = $module['credits'];
$exam_weight = $module['exam_weight'];
$assignment_weight = $module['assignment_weight'];
$moduleLeader = $module['module_leader_name'];
// $startDate = $module['start_date'];
// $endDate = $module['end_date'];
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
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
                <li class="active"><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
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
                                <h2><?php echo htmlspecialchars("$moduleId : $moduleName"); ?></h2>
                                <button class="view-students-button" onclick="viewStudents('<?php echo $moduleId; ?>')">
                                    <i class="fas fa-users"></i> View Students
                                </button>
                            </div>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Module Code:</label>
                                    <span id="module-code"><?php echo htmlspecialchars($moduleId); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Module Name:</label>
                                    <span id="module-name"><?php echo htmlspecialchars($moduleName); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Level:</label>
                                    <span id="level"><?php echo htmlspecialchars($level); ?></span>
                                </div>
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

                        <!-- Job Details -->
                        <div class="details-section">
                            <h2>Teaching Staff</h2>
                            <div class="details-grid">
                                <!-- <div class="detail-item">
                                    <label>Start Date:</label>
                                    <span id="start-date"><?php echo htmlspecialchars($startDate); ?></span>
                                </div> -->
                                <div class="detail-item">
                                    <label>Module Leader:</label>
                                    <span id="module-leader"><?php echo htmlspecialchars($moduleLeader); ?></span>
                                </div>
                                <!-- <div class="detail-item">
                                    <label>End Date:</label>
                                    <span id="end-date"><?php echo htmlspecialchars($endDate); ?></span>
                                </div> -->
                            </div>
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