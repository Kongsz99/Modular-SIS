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

// Array to store exam data for each department
$departmentExams = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details using the student_id stored in the session
        $studentId = $_SESSION['student_id'];

        // Fetch the student's enrolled modules
        $stmt = $pdo->prepare("
            SELECT m.module_id, m.module_name
            FROM student_modules sm
            JOIN modules m ON sm.module_id = m.module_id
            WHERE sm.student_id = :student_id
            AND sm.academic_year = (SELECT academic_year FROM programme_enrolment WHERE student_id = :student_id)
        ");
        $stmt->execute(['student_id' => $studentId]);
        $enrolledModules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch exam details for the enrolled modules
        $exams = [];
        foreach ($enrolledModules as $module) {
            $stmt = $pdo->prepare("
                SELECT 
                    e.exam_id, 
                    e.module_id, 
                    m.module_name, 
                    e.exam_date, 
                    e.start_time, 
                    e.end_time, 
                    e.location, 
                    e.academic_year
                FROM exam e
                JOIN modules m ON e.module_id = m.module_id
                WHERE e.module_id = :module_id 
                AND (e.exam_date > CURRENT_DATE OR (e.exam_date = CURRENT_DATE AND e.start_time > CURRENT_TIME))
            ");
            $stmt->execute(['module_id' => $module['module_id']]);
            $exams = array_merge($exams, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // Sort the merged results by exam_date and start_time
        usort($exams, function($a, $b) {
            $dateComparison = strcmp($a['exam_date'], $b['exam_date']);
            if ($dateComparison === 0) {
                return strcmp($a['start_time'], $b['start_time']);
            }
            return $dateComparison;
        });

        // Store exam data for this department
        $departmentExams[] = [
            'department_id' => $departmentId,
            'exams' => $exams,
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
    <title>Exam Information</title>
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
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li class="active"><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
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
                    <h1>Exam</h1>
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

            <!-- Sidebar Toggle Icon -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <!-- Content Area -->
            <div class="content">
                <div class="person-details-container">
                    <?php foreach ($departmentExams as $department): ?>
                        <h2>Exams (Department: <?php echo htmlspecialchars($department['department_id']); ?>)</h2>
                        <!-- Upcoming Exams -->
                        <div class="details-section">
                            <h3>Upcoming Exams</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Module ID Examination Paper</th>
                                        <th>Module Name</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department['exams'] as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['module_id']. '  ' . $exam['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['module_name']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['location']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Exam Timetable -->
                        <div class="details-section">
                            <h3>Exam Timetable</h3>
                            <div class="timetable-grid">
                                <?php
                                // Group exams by date
                                $examsByDate = [];
                                foreach ($department['exams'] as $exam) {
                                    $date = $exam['exam_date'];
                                    if (!isset($examsByDate[$date])) {
                                        $examsByDate[$date] = [];
                                    }
                                    $examsByDate[$date][] = $exam;
                                }

                                // Display exams grouped by date
                                foreach ($examsByDate as $date => $examsOnDate): ?>
                                    <div class="timetable-day">
                                        <h4><?php echo htmlspecialchars($date); ?></h4>
                                        <?php foreach ($examsOnDate as $exam): ?>
                                            <div class="exam-card">
                                                <div class="exam-time"><?php echo htmlspecialchars($exam['start_time'] . ' - ' . $exam['end_time']); ?></div>
                                                <div class="exam-subject"><?php echo htmlspecialchars($exam['module_name']); ?></div>
                                                <div class="exam-location"><?php echo htmlspecialchars($exam['location']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>