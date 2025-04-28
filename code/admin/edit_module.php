<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Determine which database to use based on the module's department
function getDatabaseForModule($module_id) {
    // Example logic: If module_id starts with "CS", use CS database; otherwise, use BM
    if (strpos($module_id, 'CS') === 0) {
        return getDatabaseConnection('cs');
    } else {
        return getDatabaseConnection('bm');
    }
}

// Fetch module details based on module_id
if (isset($_GET['id'])) {
    $module_id = $_GET['id'];

    // Connect to the appropriate database
    $pdo = getDatabaseForModule($module_id);

    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_name, m.department_id, m.level, m.semester, m.credits, d.department_name
        FROM modules m
        JOIN departments d ON m.department_id = d.department_id
        WHERE m.module_id = :module_id
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
    $department_id = trim($_POST['department_id']);
    $level = trim($_POST['level']);
    $semester = trim($_POST['semester']);
    $credits = trim($_POST['credits']);

    // Update module in the database
    $stmt = $pdo->prepare("
        UPDATE modules
        SET module_name = :module_name,
            department_id = :department_id,
            level = :level,
            semester = :semester,
            credits = :credits
        WHERE module_id = :module_id
    ");
    $stmt->execute([
        ':module_name' => $module_name,
        ':department_id' => $department_id,
        ':level' => $level,
        ':semester' => $semester,
        ':credits' => $credits,
        ':module_id' => $module_id
    ]);

    // Redirect back to the module list
    header("Location: module.php");
    exit();
}

// Fetch departments for the dropdown
$stmt_departments = $pdo->prepare("SELECT department_id, department_name FROM departments");
$stmt_departments->execute();
$departments = $stmt_departments->fetchAll(PDO::FETCH_ASSOC);
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
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id" required>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_id']); ?>" <?php echo ($department['department_id'] == $module['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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