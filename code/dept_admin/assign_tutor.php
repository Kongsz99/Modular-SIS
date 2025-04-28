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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'];
    $student_id = $_POST['student_id'];

    if (empty($staff_id) || empty($student_id)) {
        die("Error: All fields are required.");
    }

    try {
        $sql = "INSERT INTO academic_tutor_assigned (staff_id, student_id) VALUES (:staff_id, :student_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'staff_id' => $staff_id,
            'student_id' => $student_id,
        ]);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

    try {     
            $sql = "
                SELECT at.staff_id, at.student_id, 
                    s.first_name AS staff_first_name, s.last_name AS staff_last_name,
                    st.first_name AS student_first_name, st.last_name AS student_last_name
                FROM academic_tutor_assigned at
                JOIN staff s ON at.staff_id = s.staff_id
                JOIN students st ON at.student_id = st.student_id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $assigned_tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                       
    } catch (PDOException $e) {
        die("Error fetching assigned tutors: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Tutor Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style></style>
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
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li class="active"><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>     

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Assign Academic Tutor</h1>
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
               <a href="tutor.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Academic Tutor List
               </a>
                <!-- Add New Scholarship Form -->
                <div class="form-container">
                    <form id="assign-tutor-form" name="assign-tutor-form" method="POST" action="assign_tutor.php">
                        <div class="form-group">
                             <label for="staff-id">Academic Tutor</label>
                            <select id="staff" name="staff_id" required>
                                <option value="">Select Tutor</option>
                                <?php
                                // Fetch departments for the filter dropdown
                                $stmt = $pdo->prepare("SELECT first_name, last_name, staff_id FROM staff Order BY staff_id");
                                $stmt->execute();
                                $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($staffs as $staff): ?>
                                    <option value="<?php echo htmlspecialchars($staff['staff_id']); ?>">
                                        <?php echo htmlspecialchars($staff['staff_id']); ?> -                                         
                                        <?php echo htmlspecialchars($staff['first_name']. ' ' . $staff['last_name']); ?> 
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                             <label for="student-id">Student ID or Name</label>

                            <input type="text" id="student-search" name="student-search" placeholder="Search by name or ID" autocomplete="on">

                            <div id="search-results" class="search-results"></div>
                        </div>
                            <input type="hidden" id="student_id" name="student_id">

                            <div class="form-group submit-button">
                                <button type="submit" name="assign-tutor" class="btn" id="assign-btn">Assign Tutor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
