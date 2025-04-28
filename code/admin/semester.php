<?php
// Include database connection
require_once '../db_connect.php'; // Adjust the path as needed
require_once '../auth.php'; // Adjust the path as needed

check_role(GLOBAL_ADMIN);

// Check if the user is logged in (you can add your own authentication logic)
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access. Please log in.");
}

// Fetch semesters from the database
try {
    $pdo = getDatabaseConnection('central'); // Adjust the connection as needed
    $sql = "SELECT * FROM year_semester ORDER BY start_date DESC";
    $stmt = $pdo->query($sql);
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semesters</title>
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
                    <h1>Semesters Management</h1>
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
                    <a href="add_semester.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New 
                    </a>
                </div>
            <!-- Content Area -->
            <div class="content">
                <!-- Semesters Table -->
                <div class="table-container">
                    <h2>List of Semesters</h2>
                    <?php if (empty($semesters)): ?>
                        <p>No semesters found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Year-Semester ID</th>
                                    <th>Academic Year</th>
                                    <th>Semester Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($semesters as $semester): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($semester['year_semester_id']); ?></td>
                                        <td><?php echo htmlspecialchars($semester['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($semester['semester_name']); ?></td>
                                        <td><?php echo htmlspecialchars($semester['start_date']); ?></td>
                                        <td><?php echo htmlspecialchars($semester['end_date']); ?></td>
                                        <td><a href="edit_semester.php?id=<?php echo $semester['year_semester_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="sidebar.js"></script>
</body>
</html>