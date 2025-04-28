<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch student details using the student_id stored in the session
$studentId = $_SESSION['student_id'];
$studentQuery = "
    SELECT s.*, u.username, CONCAT(a.address, ', ', a.city, ', ', a.state, ', ', a.postcode, ', ', a.country) AS full_address
        FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN address a ON s.address_id = a.address_id
    WHERE student_id = :student_id;
";

try {
    // Prepare the statement
    $studentStmt = $pdo->prepare($studentQuery);
    if (!$studentStmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $studentStmt->execute(['student_id' => $studentId]);

    // Fetch the student details
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student details not found.");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}


// Fetch scholarship details for the student
$scholarshipQuery = "
    SELECT sa.scholarship_id, sc.scholarship_name, sc.amount
    FROM scholarship_assignment sa
    JOIN scholarship sc ON sa.scholarship_id = sc.scholarship_id
    WHERE sa.student_id = :student_id;
";

try {
    // Prepare the statement
    $scholarshipStmt = $pdo->prepare($scholarshipQuery);
    if (!$scholarshipStmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $scholarshipStmt->execute(['student_id' => $studentId]);

    // Fetch the scholarship details
    $scholarships = $scholarshipStmt->fetchAll(PDO::FETCH_ASSOC);
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
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li><a href="disability_request.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars(string: $student['first_name'] . ' ' . $student['last_name']); ?></h1>
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
                                    <span id="full-name"><?php echo htmlspecialchars(string: $student['first_name'] . ' ' . $student['last_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Student ID:</label>
                                    <span id="student-id"><?php echo htmlspecialchars(string: $student['student_id']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Gender:</label>
                                    <span id="gender"><?php echo htmlspecialchars(string: $student['gender']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Username:</label>
                                    <span id="username"><?php echo htmlspecialchars(string: $student['username']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Date of Birth:</label>
                                    <span id="dob"><?php echo htmlspecialchars(string: $student['date_of_birth']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Contact Number:</label>
                                    <span id="contact-number"><?php echo htmlspecialchars(string: $student['phone']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Personal Email:</label>
                                    <span id="personal-email"><?php echo htmlspecialchars(string: $student['personal_email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Address:</label>
                                    <span id="address"><?php echo htmlspecialchars(string: $student['full_address']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>University Email:</label>
                                    <span id="university-email"><?php echo htmlspecialchars(string: $student['uni_email']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-item" style="text-align: center; display:flex; justify-content: center; align-items: center; margin-top: 20px; margin-bottom: 20px;">
                        <button class="btn-edit" style="padding: 10px; text-align: center" onclick="window.location.href='edit_profile.php'">
                            <i class="fas fa-edit"></i> Update Address, Contact Number, Email
                        </button>
                    </div>


                    <!-- Scholarship Details -->
                    <div class="details-section">
                        <h2>Scholarship Detail</h2>
                        <?php if (!empty($scholarships)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Scholarship Name</th>
                                        <th>Amount (£)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scholarships as $scholarship): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></td>
                                            <td><?php echo htmlspecialchars($scholarship['amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No scholarship assigned.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>