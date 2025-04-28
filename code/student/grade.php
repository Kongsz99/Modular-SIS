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

// Array to store grades for each department
$departmentGrades = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details using the student_id stored in the session
        $studentId = $_SESSION['student_id'];

        // Fetch the student's grades
        $stmt = $pdo->prepare("
            SELECT 
                m.module_id, 
                m.module_name, 
                g.academic_year, 
                g.assignment_marks,
                g.exam_marks,
                g.total_marks, 
                g.grade
            FROM 
                grade g
            JOIN 
                modules m ON g.module_id = m.module_id
            WHERE 
                g.student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch the student's name
        $stmt = $pdo->prepare("
            SELECT first_name, last_name 
            FROM students 
            WHERE student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $studentName = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Student';

        // Store grades and student name for this department
        $departmentGrades[] = [
            'department_id' => $departmentId,
            'student_name' => $studentName,
            'grades' => $grades,
        ];
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Details</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li class="active"><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
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
                    <h1>Grade</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    </div>
                    <!-- Logout Button -->
                    <a href="../logout.php" class="logout-btn">
                        <button>Logout</button>
                    </a>
                </div>
            </div>

            <!-- Sidebar Toggle -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <!-- Content Area -->
            <div class="content">
                <div class="person-details-container">
                    <?php foreach ($departmentGrades as $department): ?>
                        <div class="details-section">
                            <h2>Grades for <?php echo htmlspecialchars($department['student_name']); ?> (Department: <?php echo htmlspecialchars($department['department_id']); ?>)</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Module ID</th>
                                        <th>Module Name</th>
                                        <th>Academic Year</th>
                                        <th>Assignment Marks</th>
                                        <th>Exam Marks</th>
                                        <th>Total Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department['grades'] as $grade): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($grade['module_id']); ?></td>
                                            <td><?php echo htmlspecialchars($grade['module_name']); ?></td>
                                            <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($grade['assignment_marks']); ?>%</td>
                                            <td><?php echo htmlspecialchars($grade['exam_marks']); ?>%</td>
                                            <td><?php echo htmlspecialchars($grade['total_marks']); ?>%</td>
                                            <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="results-footer">
                            <span>Grading Scale: A+ (90-100%), A (80-89%), B (70-79%), C (60-69%), D (50-59%), F (Below 50%)</span>  
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>