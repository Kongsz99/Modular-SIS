<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(STAFF);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch student details using the student_id stored in the session
$staffId = $_SESSION['staff_id'];
$staffQuery = "
    SELECT s.*, u.username, r.role_name, CONCAT(a.address, ', ', a.city, ', ', a.state, ', ', a.postcode, ', ', a.country) AS full_address
        FROM staff s
    JOIN users u ON s.user_id = u.user_id
    JOIN role r ON u.role_id = r.role_id
    JOIN address a ON s.address_id = a.address_id
    WHERE staff_id = :staff_id;
";

try {
    // Prepare the statement
    $staffStmt = $pdo->prepare($staffQuery);
    if (!$staffStmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $staffStmt->execute(['staff_id' => $staffId]);

    // Fetch the student details
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        throw new Exception("Staff details not found.");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Details</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<style>
</style>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars(string: $staff['first_name'] . ' ' . $staff['last_name']); ?></h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars(string: $user['first_name'] . ' ' . $user['last_name']); ?></span>
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
                <!-- Staff Details Container -->
                <div class="person-details-container">
                    <!-- Two-Column Layout for Personal and Job Details -->
                    <div class="details-grid-container">
                        <!-- Personal Details -->
                        <div class="details-section">
                            <h2>Personal Details</h2>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Full Name:</label>
                                    <span id="full-name"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Staff ID:</label>
                                    <span id="staff-id"><?php echo htmlspecialchars($staff['staff_id']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Gender:</label>
                                    <span id="gender"><?php echo htmlspecialchars($staff['gender']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Username:</label>
                                    <span id="username"><?php echo htmlspecialchars($staff['username']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Date of Birth:</label>
                                    <span id="dob"><?php echo htmlspecialchars($staff['date_of_birth']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Contact Number:</label>
                                    <span id="contact-number"><?php echo htmlspecialchars($staff['phone']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Personal Email:</label>
                                    <span id="personal-email"><?php echo htmlspecialchars($staff['personal_email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Address:</label>
                                    <span id="address"><?php echo htmlspecialchars($staff['full_address']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>University Email:</label>
                                    <span id="university-email"><?php echo htmlspecialchars($staff['uni_email']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-item" style="text-align: center; display:flex; justify-content: center; align-items: center; margin-top: 20px; margin-bottom: 20px;">
                            <button class="btn-edit" style="padding: 10px; text-align: center" onclick="window.location.href='edit_profile.php'">
                                <i class="fas fa-edit"></i> Update Address, Contact Number, Email
                            </button>
                        </div>

                        <!-- Job Details -->
                        <div class="details-section">
                            <h2>Job Details</h2>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label>Job Title:</label>
                                    <span id="job-title"><?php echo htmlspecialchars($staff['role_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Start Date:</label>
                                    <span id="start-date"><?php echo htmlspecialchars($staff['start_date']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Status:</label>
                                    <span id="status"><?php echo htmlspecialchars($staff['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>