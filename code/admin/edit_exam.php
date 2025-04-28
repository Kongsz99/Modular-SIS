<?php
// update_exam.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

// Fetch exam data if exam_id is provided in the URL (for editing)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['exam_id']) && isset($_GET['department'])) {
    $examId = $_GET['exam_id'];
    $department = $_GET['department'];

    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department));

    // Fetch the exam data
    try {
        $sql = "SELECT * FROM exam WHERE exam_id = :exam_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            die("Error: Exam not found.");
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = $_POST['exam_id'];
    $department = $_POST['department'];
    $moduleId = $_POST['module_id'];
    $examDate = $_POST['exam_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $location = $_POST['location'];
    $academic_year = $_POST['academic_year'];

    // Validate input
    if (empty($examId) || empty($department) || empty($moduleId) || empty($examDate) || empty($startTime) || empty($endTime) || empty($location) || empty($academic_year)) {
        die("Error: All fields are required.");
    }

    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department));

    // Update the exam
    try {
        $sql = "UPDATE exam SET 
                module_id = :module_id, 
                exam_date = :exam_date, 
                start_time = :start_time, 
                end_time = :end_time, 
                location = :location, 
                academic_year = :academic_year 
                WHERE exam_id = :exam_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'module_id' => $moduleId,
            'exam_date' => $examDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location' => $location,
            'academic_year' => $academic_year,
            'exam_id' => $examId
        ]);

        header("Location: exam.php?success=Exam updated successfully.");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
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
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li class="active"><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
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
                    <h1>Exam Management</h1>
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

            <!-- Edit Exam Form -->
            <div class="content">
                <!-- Back Button -->
               <a href="exam.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to exam List
               </a>
               <div class="form-container">
                <form method="POST">

                    <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">

                    <div class="form-group">
                        <label for="module_id">Module ID</label>
                        <input type="text" id="module_id" name="module_id" value="<?php echo htmlspecialchars($exam['module_id']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="exam_date">Date</label>
                        <input type="date" id="exam_date" name="exam_date" value="<?php echo htmlspecialchars($exam['exam_date']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($exam['start_time']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?php echo htmlspecialchars($exam['end_time']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($exam['location']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($exam['academic_year']); ?>" required>
                    </div>

                    <div class="form-group submit-button">
                        <button type="submit" class="btn">Update Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>