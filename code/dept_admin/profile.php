<?php
// profile.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is logged in and has the required role
check_role(required_role: DEPARTMENT_ADMIN); // Assuming STAFF is the required role for this page

// Fetch the logged-in user's ID from the session
$user_id = $_SESSION['user_id']; // Assuming the user ID is stored in the session

// Fetch the staff member's details from the database
try {
    $pdo = getDatabaseConnection('central'); // Connect to the default database
    $sql = "
         SELECT a.first_name, a.last_name, u.username, a.gender, a.date_of_birth, a.phone, a.personal_email, ad.address,
               ad.city, ad.state, ad.postcode, ad.country, a.uni_email,
               a.admin_id, r.role_name, a.start_date, a.status
        FROM admin a
        JOIN users u ON a.user_id = u.user_id
        JOIN role r ON u.role_id = r.role_id
        JOIN address ad ON a.address_id = ad.address_id
        WHERE u.user_id = :user_id;
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        die("Error: Staff details not found.");
    }
} catch (PDOException $e) {
    die("Error fetching staff details: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Details</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
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
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h1>
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
                                    <label>admin ID:</label>
                                    <span id="staff-id"><?php echo htmlspecialchars($staff['admin_id']); ?></span>
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
                                    <span id="address"><?php echo htmlspecialchars($staff['address']. ', ' . $staff['city']. ', ' . $staff['state']. ', ' . $staff['postcode']. ', ' . $staff['country']); ?></span>
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