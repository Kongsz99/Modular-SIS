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
// Initialize success message variable
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = $_POST['module_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $academic_year = $_POST['academic_year'];

    // Validate input
    if (empty($module_id) || empty($exam_date) || empty($start_time) || empty($end_time) || empty($location) || empty($academic_year)) {
        die("Error: All fields are required.");
    } 

    // Insert the exam details
    try {
        $sql = "INSERT INTO exam (module_id, exam_date, start_time, end_time, location, academic_year) VALUES (:module_id, :exam_date, :start_time, :end_time, :location, :academic_year)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'module_id' => $module_id,
            'exam_date' => $exam_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'location' => $location,
            'academic_year' => $academic_year
        ]);

        $successMessage = "Exam added successfully!";
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
    <title>Add New Exam</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="add-module-page">
    <div class="dashboard">
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
                    <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                    <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                    <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                    <li class="active"><a href="exam.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                    <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                    <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
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
        <!-- Content Area -->
        <div class="content">
            <!-- Back Button -->
            <a href="exam.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Exam List
            </a>

            <!-- Add New Exam Form -->
            <div class="form-container">
            <h2>Create New Exam</h2>
                <form id="add-exam-form" action="add_exam.php" method="POST">
                    <!-- Exam Details -->
                    <!-- <fieldset> -->
                        <!-- <legend>Exam Details</legend> -->
                        <div class="form-group">
                            <label for="module-id">Module</label>
                            <select id="module-id" name="module_id" required>
                                <option value="">Select Module</option>
                                <?php
                                // Fetch departments for the filter dropdown
                                $stmt = $pdo->prepare("SELECT module_id, module_name FROM modules Order BY module_id");
                                $stmt->execute();
                                $mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($mods as $mod): ?>
                                    <option value="<?php echo htmlspecialchars($mod['module_id']); ?>">
                                        <?php echo htmlspecialchars($mod['module_id']); ?> -                                         
                                        <?php echo htmlspecialchars($mod['module_name']); ?>

                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <select id="academic_year" name="academic_year" required>
                                <option value="">Select Academic Year</option>
                                <?php
                            // Fetch departments for the filter dropdown
                            $stmt = $pdo->prepare("SELECT DISTINCT academic_year FROM programme_enrolment");
                            $stmt->execute();
                            $acads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($acads as $acad): ?>
                                <option value="<?php echo htmlspecialchars($acad['academic_year']); ?>">
                                    <?php echo htmlspecialchars($acad['academic_year']); ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exam-date">Date</label>
                            <input type="date" id="exam-date" name="exam_date" required>
                        </div>
                        <div class="form-group">
                            <label for="start-time">Start Time</label>
                            <input type="time" id="start-time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end-time">End Time</label>
                            <input type="time" id="end-time" name="end_time" required>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="Enter Location" required>
                        </div>
                    <!-- </fieldset> -->

                    <!-- Submit Button -->
                    <div class="form-group submit-button">
                        <button type="submit" class="btn">Add Exam</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
    <script src="template/sidebar.js"></script>

    <!-- Add SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <?php if (!empty($successMessage)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo $successMessage; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect or do something else if needed
                        // window.location.href = 'assignment.php';
                    }
                });
            });
        </script>
        <?php endif; ?>
</body>
</html>
