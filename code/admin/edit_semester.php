<?php
// Include database connection
require_once '../db_connect.php'; // Adjust the path as needed
require_once '../auth.php'; // Adjust the path as needed

check_role(GLOBAL_ADMIN);
// Initialize variables
$year_semester_id = $_GET['id'] ?? null; // Get the semester ID from the query parameter
$academic_year = $semester_name = $start_date = $end_date = '';
$error_message = $success_message = '';

// Fetch semester details for editing
if ($year_semester_id) {
    try {
        $pdo = getDatabaseConnection('central'); // Adjust the connection as needed
        $sql = "SELECT * FROM year_semester WHERE year_semester_id = :year_semester_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year_semester_id' => $year_semester_id]);
        $semester = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$semester) {
            die("Semester not found.");
        }

        // Pre-fill form fields
        $academic_year = $semester['academic_year'];
        $semester_name = $semester['semester_name'];
        $start_date = $semester['start_date'];
        $end_date = $semester['end_date'];
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    die("Invalid semester ID.");
}

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
            // Update the semester in the database
            $sql = "
                UPDATE year_semester
                SET academic_year = :academic_year,
                    semester_name = :semester_name,
                    start_date = :start_date,
                    end_date = :end_date
                WHERE year_semester_id = :year_semester_id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'academic_year' => $academic_year,
                'semester_name' => $semester_name,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'year_semester_id' => $year_semester_id
            ]);

            $success_message = "Semester updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Semester</title>
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
                <li><a href="disability_requests.php"><i class="fas fa-wheelchair"></i> Disability Requests</a></li>
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
                    <h1>Edit Semester</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </div>
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
                <!-- Edit Semester Form -->
                <div class="form-container">
                    <h2>Edit Semester: <?php echo htmlspecialchars($year_semester_id); ?></h2>
                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="edit_semester.php?id=<?php echo htmlspecialchars($year_semester_id); ?>">
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($academic_year); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="semester_name">Semester Name</label>
                            <input type="text" id="semester_name" name="semester_name" value="<?php echo htmlspecialchars($semester_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Update Semester</button>
                        </div> 
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="sidebar.js"></script>
</body>
</html>