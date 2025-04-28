<?php
// dashboard.php

// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch module details based on module_id
if (isset($_GET['id'])) {
    $module_id = $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT module_id, module_name, level, semester, credits
        FROM modules
        WHERE module_id = :module_id
    ");
    $stmt->execute([':module_id' => $module_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        die("Module not found.");
    }
} else {
    die("Module ID not provided.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module_name = trim($_POST['module_name']);
    $level = trim($_POST['level']);
    $semester = trim($_POST['semester']);
    $credits = trim($_POST['credits']);

    // Update module in the database
    $stmt = $pdo->prepare("
        UPDATE modules
        SET module_name = :module_name,
            level = :level,
            semester = :semester,
            credits = :credits
        WHERE module_id = :module_id
    ");
    $stmt->execute([
        ':module_name' => $module_name,
        ':level' => $level,
        ':semester' => $semester,
        ':credits' => $credits,
        ':module_id' => $module_id
    ]);

    // Redirect back to the module list
    header("Location: module.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Module</title>
    <link rel="stylesheet" href="template/styles.css">
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
                <li><a href="semester.php"><i class="fas fa-calendar"></i><span>Semesters</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li class="active"><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="disability_requests.php"><i class="fas fa-wheelchair"></i> Disability Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Edit Module</h1>
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

            <div class="content">
                <!-- Back Button -->
               <a href="module.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Module List
               </a>
               <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" id="module_name" name="module_name" value="<?php echo htmlspecialchars($module['module_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="level">Level</label>
                        <input type="number" id="level" name="level" value="<?php echo htmlspecialchars(string: $module['level']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <input type="text" id="semester" name="semester" value="<?php echo htmlspecialchars($module['semester']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="credits">Credits</label>
                        <input type="number" id="credits" name="credits" value="<?php echo htmlspecialchars($module['credits']); ?>" required>
                    </div>
                    
                    <div class="form-group submit-button">
                    <button type="submit" class="btn">Update Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>