<?php
// assign_module_to_programme.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user has the DEPARTMENT_ADMIN role
check_role(required_role: DEPARTMENT_ADMIN);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Use the department ID to connect to the appropriate database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programmeId = $_POST['programme_id'] ?? '';
    $moduleId = $_POST['module_id'] ?? '';
    $moduleType = $_POST['module_type'] ?? 'Compulsory';

    if (empty($programmeId) || empty($moduleId)) {
        die("Programme ID and Module ID are required.");
    }

    // Insert into programme_module table
    $sql = "INSERT INTO programme_module (programme_id, module_id, module_type) VALUES (:programme_id, :module_id, :module_type)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'programme_id' => $programmeId,
        'module_id' => $moduleId,
        'module_type' => $moduleType
    ]);

    echo "<p>Module successfully assigned to programme!</p>";
}

// Fetch all programmes and modules for the dropdowns
try {
    // Fetch programmes
    $sql_programmes = "SELECT programme_id, programme_name FROM programme ORDER BY programme_id";
    $stmt_programmes = $pdo->query($sql_programmes);
    $programmes = $stmt_programmes->fetchAll(PDO::FETCH_ASSOC);

    // Fetch modules
    $sql_modules = "SELECT module_id, module_name FROM modules ORDER BY module_id";
    $stmt_modules = $pdo->query($sql_modules);
    $modules = $stmt_modules->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Module to Programme</title>
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
                    <h1>Assign Module to Programme</h1>
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

                <!-- Assign Module Form -->
                <div class="form-container">
                    <form method="POST" action="assign_module_to_programme.php">
                        <div class="form-group">
                            <label for="programme_id">Programme:</label>
                            <select name="programme_id" id="programme_id" required>
                                <option value="">Select a Programme</option>
                                <?php foreach ($programmes as $programme): ?>
                                    <option value="<?php echo htmlspecialchars($programme['programme_id']); ?>">
                                    <?php echo htmlspecialchars($programme['programme_id']); ?> - <?php echo htmlspecialchars($programme['programme_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="module_id">Module:</label>
                            <select name="module_id" id="module_id" required>
                                <option value="">Select a Module</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?php echo htmlspecialchars($module['module_id']); ?>">
                                    <?php echo htmlspecialchars($module['module_id']); ?> - <?php echo htmlspecialchars($module['module_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="module_type">Module Type:</label>
                            <select name="module_type" id="module_type" required>
                                <option value="Compulsory">Compulsory</option>
                                <option value="Optional">Optional</option>
                            </select>
                        </div>
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Assign Module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>