<?php
// module.php

require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(STAFF);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}
// Fetch modules and lecturers from the CS database
try {
    // Connect to the CS department's database
    $pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database
    $staffId = $_SESSION['staff_id'];

    // Fetch modules taught by this staff member
    $moduleQuery = "
    SELECT m.module_id, m.module_name, m.level, m.semester, m.credits 
    FROM modules m
    JOIN assigned_lecturers al ON m.module_id = al.module_id
    WHERE al.staff_id = :staff_id
    ORDER BY m.module_id
    ";
    $moduleStmt = $pdo->prepare($moduleQuery);
    $moduleStmt->execute(['staff_id' => $staffId]);
    $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li class="active"><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
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
                <!-- Module List Subtitle -->
                <h2 class="list-subtitle">Module Teaching</h2>

                <!-- Module List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Module ID</th>
                                <th>Module Name</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                                <tr>
                                    <td><a href="module_details.php?id=<?php echo htmlspecialchars($module['module_id']); ?>" class="clickable">
                                        <?php echo htmlspecialchars($module['module_id']); ?>
                                    </a></td>
                                    <td><a href="module_details.php?id=<?php echo htmlspecialchars($module['module_id']); ?>" class="clickable">
                                        <?php echo htmlspecialchars($module['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($module['level']); ?></td>
                                    <td><?php echo htmlspecialchars($module['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($module['credits']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>