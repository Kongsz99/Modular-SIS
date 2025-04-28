<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Arrays to store combined student and enrollment data
$combinedStudents = [];
$combinedEnrollments = [];
$totalCompletedCredits = 0;

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch student details using the student_id stored in the session
    $studentId = $_SESSION['student_id'];
    $studentQuery = "
        SELECT p.programme_name, st.programme_end_date 
        FROM students s
        JOIN programme_enrolment st ON s.student_id = st.student_id
        JOIN programme p ON st.programme_id = p.programme_id
        WHERE s.student_id = :student_id;
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
            throw new Exception("Student details not found for department ID: $departmentId.");
        }

        // Add department ID to the student data
        $student['department_id'] = $departmentId;

        // Store student details in the combined array
        $combinedStudents[] = $student;
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }

        // Fetch academic progress
        $progressQuery = "
            SELECT SUM(m.credits) AS total_credits
            FROM student_modules sm
            JOIN modules m ON sm.module_id = m.module_id
            WHERE sm.student_id = :student_id AND sm.status = 'Completed';
        ";

    try {
        $progressStmt = $pdo->prepare($progressQuery);
        $progressStmt->execute(['student_id' => $studentId]);
        $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);

        if ($progress && $progress['total_credits']) {
            $totalCompletedCredits += $progress['total_credits'];
        }
        } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
        }


    // Fetch enrollment progress from programme_enrolment
    $enrollmentQuery = "
        SELECT status, progress_step
        FROM programme_enrolment
        WHERE student_id = :student_id;
    ";

    try {
        // Prepare the statement
        $enrollmentStmt = $pdo->prepare($enrollmentQuery);
        if (!$enrollmentStmt) {
            throw new Exception("Failed to prepare the SQL statement.");
        }

        // Bind the parameter and execute the query
        $enrollmentStmt->execute(['student_id' => $studentId]);

        // Fetch the enrollment progress
        $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);

        // If no enrollment data is found, set default values
        if (!$enrollment) {
            $enrollment = [
                'status' => 'Not Enrolled',
                'progress_step' => 0, // No progress
            ];
        }

        // Add department ID to the enrollment data
        $enrollment['department_id'] = $departmentId;

        // Store enrollment data in the combined array
        $combinedEnrollments[] = $enrollment;
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}

// Determine the overall enrollment status and progress
// Determine the button text and link based on progress_step and status
$enrollmentButtonText = "Continue"; // Default
$enrollmentButtonLink = "enrol_1.php"; // Default

if ($enrollment['progress_step'] == 0) {
    $enrollmentButtonText = "Start";
} elseif ($enrollment['progress_step'] == 4 && $enrollment['status'] == 'enrolled') {
    $enrollmentButtonText = "Completed";
    $enrollmentButtonLink = "#"; // No link needed for completed enrollment
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
                <li class="active"><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
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
                     <h1>Home</h1>
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
            <div class="content">
                <!-- this is welcome-banner -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                    <h1>Welcome Back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>                        
                        <!-- Loop through each program -->
                        <?php foreach ($combinedStudents as $student): ?>
                            <p><?php echo htmlspecialchars($student['programme_name']); ?> | Expected Graduation: <?php echo htmlspecialchars($student['programme_end_date']); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="academic-status">
                        <span class="status-badge enrolled"><?php echo htmlspecialchars($enrollment['status']); ?></span>
                        <span class="status-badge credits">Completed Credits: <?php echo htmlspecialchars($totalCompletedCredits); ?>/360</span>
                    </div>        
                </div>
                <!-- until here is welcome-banner -->
                <!-- Enrolment Overview Section -->
                <div class="person-details-container">
                    <div class="details-section">
                        <h2>Enrolment Overview</h2>
                        <div class="enrolment-container">
                            <div class="progress-header">
                                <h1>2024/2025 Enrolment</h1>
                                <div class="progress-status">Status: <?php echo htmlspecialchars($enrollment['status']); ?></div>
                            </div>

                            <?php if ($enrollment['status'] === 'Not Enrolled'): ?>
                                <!-- Display a message if the student is not enrolled -->
                                <div class="enrolment-message">
                                    <p>You are not currently enrolled in any programme. <a href="enrol_1.html">Click here to start your enrollment.</a></p>
                                </div>
                            <?php else: ?>
                                <!-- Display the progress tracker if the student is enrolled -->
                                <div class="progress-tracker-v2">
                                    <div class="progress-enrol-bar">
                                        <div class="progress-fill" style="width: <?php echo ($enrollment['progress_step'] / 4) * 100; ?>%"></div>
                                    </div>

                                    <div class="progress-steps">
                                        <div class="progress-step">
                                            <div class="step-indicator <?php echo ($enrollment['progress_step'] >= 1) ? 'completed' : ''; ?>">1</div>
                                            <span class="step-name">Rules & Regulations</span>
                                        </div>
                                        <div class="progress-step">
                                            <div class="step-indicator <?php echo ($enrollment['progress_step'] >= 2) ? 'completed' : ''; ?>">2</div>
                                            <span class="step-name">Personal Details</span>
                                        </div>
                                        <div class="progress-step">
                                            <div class="step-indicator <?php echo ($enrollment['progress_step'] >= 3) ? 'completed' : ''; ?>">3</div>
                                            <span class="step-name">Modules Selection</span>
                                        </div>
                                        <div class="progress-step">
                                            <div class="step-indicator <?php echo ($enrollment['progress_step'] >= 4) ? 'completed' : ''; ?>">4</div>
                                            <span class="step-name">Fees Payment</span>
                                        </div>
                                    </div>

                                    <div class="enrolment-action">
                                        <a href="<?php echo $enrollmentButtonLink; ?>">
                                            <button class="action-button">
                                                <!-- <i class="fas fa-arrow-right"></i> -->
                                                <?php echo $enrollmentButtonText; ?>
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>