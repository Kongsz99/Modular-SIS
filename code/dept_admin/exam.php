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
        SELECT * FROM exam ORDER BY exam_id ASC " );

// Fetch all programmes
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <!-- New Button -->
                <div class="new-button-container">
                    <a href="add_exam.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>
        
                <!-- Department Filter -->
                <h2>List of Exam Schedule</h2>
                <table>
                    <thead>
                        <tr>
                            <!-- <th>Department</th> -->
                            <th>Exam ID</th>
                            <th>Module ID</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="8">No exams found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['exam_id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['module_id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['location']); ?></td>
                                    <td>
                                        <a href="edit_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this exam?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>

