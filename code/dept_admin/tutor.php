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

    $stmt = $pdo->prepare(query: "
        SELECT ata.student_id, ata.staff_id,
             st.first_name AS student_first_name, st.last_name AS student_last_name,
            sf.first_name AS staff_first_name, sf.last_name AS staff_last_name
        FROM academic_tutor_assigned ata
        JOIN staff sf ON ata.staff_id = sf.staff_id
        JOIN students st ON ata.student_id = st.student_id
    "); // Fetch all programmes
    $stmt->execute();
    $assignedTutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Academic Tutor</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
        <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
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

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Academic Tutor Management</h1>
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
                    <a href="assign_tutor.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>
        
                <!-- Assigned Tutor Table -->
                <h2>List of Assigned Tutors</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Academic Tutor</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignedTutors)): ?>
                            <tr>
                                <td colspan="5">No assigned tutors found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedTutors as $tutor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tutor['staff_id']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['staff_first_name'] . ' ' . $tutor['staff_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['student_first_name'] . ' ' . $tutor['student_last_name']); ?></td>
                                    <td>
                                    <a href="delete_tutor_assignment.php?staff_id=<?php echo $tutor['staff_id']; ?>&student_id=<?php echo $tutor['student_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this assignment?');">
                                    <i class="fas fa-trash-alt"></i>
                                    </a>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>