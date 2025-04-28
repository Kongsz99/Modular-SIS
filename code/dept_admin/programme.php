<?php
// programme.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: DEPARTMENT_ADMIN);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Fetch modules from the department's database
try {
    $pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database

    // Fetch programmes (use DISTINCT to avoid duplicates)
    $stmtprog = $pdo->query("SELECT * FROM programme ORDER BY programme_id");
    $programmes = $stmtprog->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all modules for the CS department, grouped by level
    $stmtmod = $pdo->query("
        SELECT * 
        FROM modules 
        ORDER BY level, module_id
    ");
    $modules = $stmtmod->fetchAll(PDO::FETCH_ASSOC);

    // Fetch programme-module assignments
    $stmtprogmod = $pdo->query("SELECT * FROM programme_module");
    $programmeModules = $stmtprogmod->fetchAll(PDO::FETCH_ASSOC);

    // Group modules by level
    $modulesByLevel = [];
    foreach ($modules as $module) {
        $level = $module['level'];
        if (!isset($modulesByLevel[$level])) {
            $modulesByLevel[$level] = [];
        }
        $modulesByLevel[$level][] = $module;
    }
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme Management</title>
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
                <span>Department Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li class="active"><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
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
                    <h1>Programmes Management</h1>
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
                <!-- New Button -->
                <div class="new-button-container">
                    <a href="add_programme.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New 
                    </a>
                    <a href="assign_module_to_programme.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        Assign module
                    </a>
                </div> 
                
                <!-- Programme Cards -->
                <div class="programme-list">
                    <?php foreach ($programmes as $programme): ?>
                        <div class="programme-card">
                            <h4 class="list-subtitle"><?php echo htmlspecialchars($programme['programme_id'] . ' - ' . $programme['programme_name']); ?></h4>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($programme['duration_years']); ?> years</p>
                            <p><strong>Modules:</strong> 
                                <?php
                                $assignedModules = array_filter($programmeModules, function($pm) use ($programme) {
                                    return $pm['programme_id'] === $programme['programme_id'];
                                });
                                $moduleNames = array_map(function($pm) use ($modules) {
                                    $module = array_filter($modules, function($m) use ($pm) {
                                        return $m['module_id'] === $pm['module_id'];
                                    });
                                    return reset($module)['module_name'];
                                }, $assignedModules);
                                echo implode(', ', $moduleNames);
                                ?>
                            </p>
                            <a href="programme_details.php?id=<?php echo $programme['programme_id']; ?>" class="clickable">
                                <button class="btn">View Details</button>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const programmes = <?php echo json_encode($programmes); ?>;
        const modules = <?php echo json_encode($modules); ?>;
        const programmeModules = <?php echo json_encode($programmeModules); ?>;

        // Fetch all programmes and populate the dropdown
        const programmeSelector = document.getElementById('programme');
        programmes.forEach(programme => {
            const option = document.createElement('option');
            option.value = programme.programme_id;
            option.textContent = programme.programme_name;
            programmeSelector.appendChild(option);
        });
    </script>
</body>
</html>