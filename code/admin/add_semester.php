<?php

// Include database connection
require_once '../db_connect.php'; // Adjust the path as needed
require_once '../auth.php'; // Adjust the path as needed

check_role(GLOBAL_ADMIN);


// Initialize variables
$academic_year = $semester_name = $start_date = $end_date = '';
$error_message = $success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $academic_year = $_POST['academic_year'];
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate inputs
    if (empty($academic_year) || empty($semester_name) || empty($start_date) || empty($end_date)) {
        $error_message = "All fields are required.";
    } elseif ($start_date >= $end_date) {
        $error_message = "Start date must be before the end date.";
    } else {
        try {
            // Generate year_semester_id (e.g., "2023-Fall")
            $year_semester_id = $academic_year . '-' . $semester_name;

            // Insert data into the database
            $pdo = getDatabaseConnection('central'); // Adjust the connection as needed
            $sql = "
                INSERT INTO year_semester (year_semester_id, academic_year, semester_name, start_date, end_date)
                VALUES (:year_semester_id, :academic_year, :semester_name, :start_date, :end_date)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'year_semester_id' => $year_semester_id,
                'academic_year' => $academic_year,
                'semester_name' => $semester_name,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

            $success_message = "Semester added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') { // Duplicate key error
                $error_message = "A semester with the same academic year and name already exists.";
            } else {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Semester</title>
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li class="active"><a href="semester.php"><i class="fas fa-calendar"></i><span>Semesters</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Add Semester</h1>
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
            <a href="semester.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Semester List
               </a>
                <!-- Add Semester Form -->
                <div class="form-container">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="add_semester.php">
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <input type="text" id="academic_year" name="academic_year" placeholder="e.g., 2023/4" required>
                        </div>
                        <div class="form-group">
                            <label for="semester_name">Semester Name</label>
                            <input type="text" id="semester_name" name="semester_name" placeholder="e.g., S1 or S2" required>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                        <div class="form-group submit-button">
                        <button type="submit" class="btn">Add Semester</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="sidebar.js"></script>
</body>
</html>