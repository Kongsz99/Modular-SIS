<?php
// programme_details.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is logged in and has the appropriate role
check_role(required_role: GLOBAL_ADMIN); // Adjust the role as needed

// Define departments
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
    // Add more departments if needed
];

// Get the programme ID and department ID from the URL
$programmeId = $_GET['id'] ?? '';
$selectedDepartment = $_GET['department_id'] ?? '';

if (empty($programmeId)) {
    die("Programme ID is required.");
}

// Initialize programme details
$programme = null;

if ($selectedDepartment) {
    // Connect to the selected department's database
    $pdo = getDatabaseConnection(strtolower($selectedDepartment));
    $sql = "SELECT * FROM programme WHERE programme_id = :programme_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['programme_id' => $programmeId]);
    $programme = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($programme) {
        // Add department information to the programme
        $programme['department'] = strtoupper($selectedDepartment);
        foreach ($departments as $dept) {
            if ($dept['department_id'] === $selectedDepartment) {
                $programme['department_name'] = $dept['department_name'];
                break;
            }
        }
    }
} else {
    // Fetch programme from all departments
    foreach ($departments as $department) {
        $pdo = getDatabaseConnection(strtolower($department['department_id']));
        $sql = "SELECT * FROM programme WHERE programme_id = :programme_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['programme_id' => $programmeId]);
        $departmentProgramme = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($departmentProgramme) {
            $departmentProgramme['department'] = strtoupper($department['department_id']);
            $departmentProgramme['department_name'] = $department['department_name'];
            $programme = $departmentProgramme;
            break; // Stop searching once the programme is found
        }
    }
}

if (!$programme) {
    die("Programme not found.");
}

// Extract programme details
$programmeId = $programme['programme_id'];
$programmeName = $programme['programme_name'];
$department = $programme['department'];
$departmentName = $programme['department_name'];
$durationYears = $programme['duration_years'];
$localFees = $programme['local_fees'];
$internationalFees = $programme['international_fees'];
$description = $programme['description'];

// Fetch associated modules for the programme
$pdo = getDatabaseConnection(strtolower($department));
$sql = "SELECT m.module_id, m.module_name, pm.module_type, m.level
        FROM programme_module pm
        JOIN modules m ON pm.module_id = m.module_id
        WHERE pm.programme_id = :programme_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['programme_id' => $programmeId]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes Management</title>
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
                <li class="active"><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
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
                <!-- Back Button -->
                <a href="programme.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Programme List
                </a>

                <!-- Staff Details Container -->
                <div class="person-details-container">
                    <!-- Two-Column Layout for Personal and Job Details -->
                    <div class="details-grid-container">
                        <!-- Personal Details -->
                        <div class="details-section">
                            <h2><?php echo htmlspecialchars($programmeId); ?> - <?php echo htmlspecialchars($programmeName); ?></h2>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Programme ID</label>
                                    <span id="programme-code"><?php echo htmlspecialchars($programmeId); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Programme Name</label>
                                    <span id="programme-name"><?php echo htmlspecialchars($programmeName); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Department:</label>
                                    <span id="department"><?php echo htmlspecialchars($departmentName); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Duration:</label>
                                    <span id="duration"><?php echo htmlspecialchars($durationYears); ?> years</span>
                                </div>
                                <div class="detail-item">
                                    <label>Local Fees:</label>
                                    <span id="local-fees">£ <?php echo htmlspecialchars(number_format($localFees, 2)); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>International Fees:</label>
                                    <span id="international-fees">£ <?php echo htmlspecialchars(number_format($internationalFees, 2)); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Description:</label>
                                    <span id="description"><?php echo htmlspecialchars($description); ?></span>
                                </div>
                            </div>
                        </div>
                            
                        <?php
                            // Group modules by their level
                            $groupedModules = [];
                            foreach ($modules as $module) {
                                $level = $module['level']; // Assuming 'level' is the key for the module level in the $modules array
                                if (!isset($groupedModules[$level])) {
                                    $groupedModules[$level] = [];
                                }
                                $groupedModules[$level][] = $module;
                            }

                            // Sort the grouped modules by level (Year 1, Year 2, Year 3)
                            ksort($groupedModules);

                            // Display modules for each year
                            foreach ($groupedModules as $level => $modulesForLevel) {
                                $year = "Year $level"; // Convert level to year (e.g., level 1 -> Year 1)
                                echo "<h3>$year</h3>";
                                echo "<ul class='module-list'>";
                                foreach ($modulesForLevel as $module) {
                                    echo "<li>";
                                    echo htmlspecialchars($module['module_id']) . " - ";
                                    echo htmlspecialchars($module['module_name']) . " ";
                                    echo "[ " . htmlspecialchars($module['module_type']) . " ]";
                                    echo "</li>";
                                }
                                echo "</ul>";
                            }
                            ?>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>