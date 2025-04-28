<?php
// lecturer_dashboard.php
require_once '../db_connect.php';
require_once '../auths.php';
check_role(STAFF);

$staff_id = $_SESSION['staff_id'];
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

$departmentName = '';
$stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = :department_id");
$stmt->execute(['department_id' => $departmentId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $departmentName = $result['department_name'];
}

// //Fetch lecturer name and department
// $lecturer_info = $pdo->prepare("
//     SELECT s.first_name, s.last_name
//     FROM staff s
// ");
// $lecturer_info->execute(['staff_id' => $staff_id]);
// $lecturer = $lecturer_info->fetch(PDO::FETCH_ASSOC);

// Fetch total students
$total_students_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT student_id) AS total_students
    FROM assigned_lecturers al
    JOIN modules m ON al.module_id = m.module_id
    JOIN student_modules e ON m.module_id = e.module_id
    WHERE al.staff_id = :staff_id
");
$total_students_stmt->execute(['staff_id' => $staff_id]);
$total_students = $total_students_stmt->fetchColumn();

// Fetch modules taught
$modules_taught_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT module_id) AS modules_taught
    FROM assigned_lecturers
    WHERE staff_id = :staff_id
");
$modules_taught_stmt->execute(['staff_id' => $staff_id]);
$modules_taught = $modules_taught_stmt->fetchColumn();

// Fetch assignments given
$assignments_given_stmt = $pdo->prepare("
    SELECT COUNT(*) AS assignments_given
    FROM assignment a
    JOIN assigned_lecturers al ON a.module_id = al.module_id
    WHERE al.staff_id = :staff_id
");
$assignments_given_stmt->execute(['staff_id' => $staff_id]);
$assignments_given = $assignments_given_stmt->fetchColumn();

// Fetch submissions made
$submissions_made_stmt = $pdo->prepare("
    SELECT COUNT(*) AS submissions_made
    FROM submission s
    JOIN assignment a ON s.assignment_id = a.assignment_id
    JOIN assigned_lecturers al ON a.module_id = al.module_id
    WHERE al.staff_id = :staff_id
");
$submissions_made_stmt->execute(['staff_id' => $staff_id]);
$submissions_made = $submissions_made_stmt->fetchColumn();

// Fetch number of tutors supervised
$tutors_supervised_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT staff_id) AS tutors_supervised
    FROM academic_tutor_assigned
    WHERE staff_id = :staff_id
");
$tutors_supervised_stmt->execute(['staff_id' => $staff_id]);
$tutors_supervised = $tutors_supervised_stmt->fetchColumn();

// Fetch tutor-student list
$tutor_student_stmt = $pdo->prepare("
    SELECT s.staff_id, s.first_name AS tutor_name, st.student_id, st.first_name AS student_name
    FROM academic_tutor_assigned ata
    JOIN staff s ON ata.staff_id = s.staff_id
    JOIN students st ON ata.student_id = st.student_id
    WHERE ata.staff_id = :staff_id
    ORDER BY s.first_name, st.first_name;
");
$tutor_student_stmt->execute(['staff_id' => $staff_id]);
$tutor_student_list = $tutor_student_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch timetable
$timetable_stmt = $pdo->prepare("
    SELECT mt.date, mt.start_time, mt.end_time, mt.location, m.module_name AS module_name, mt.type
    FROM module_timetable mt
    JOIN modules m ON mt.module_id = m.module_id
    WHERE mt.staff_id = :staff_id
    AND (mt.date > CURRENT_DATE OR (mt.date = CURRENT_DATE AND mt.start_time > CURRENT_TIME))
    ORDER BY mt.date, mt.start_time
    LIMIT 5
");
$timetable_stmt->execute(['staff_id' => $staff_id]);
$timetable = $timetable_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming assignments to grade
$upcoming_grading_stmt = $pdo->prepare("
    SELECT a.assignment_id, a.title, m.module_name, a.due_date
    FROM assignment a
    JOIN assigned_lecturers al ON a.module_id = al.module_id
    JOIN modules m ON a.module_id = m.module_id
    WHERE al.staff_id = :staff_id
    AND a.due_date >= CURRENT_DATE
    ORDER BY a.due_date ASC
    LIMIT 5
");
$upcoming_grading_stmt->execute(['staff_id' => $staff_id]);
$upcoming_grading = $upcoming_grading_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<style>
    /* Cards grid */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 20px;
        transition: transform 0.3s;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }
    
    /* Different colors for each card icon */
    .card:nth-child(1) .card-icon {
        background-color: rgba(63, 140, 192, 0.33);
        color: var(--primary-color);
    }
    
    .card:nth-child(2) .card-icon {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success-color);
    }
    
    .card:nth-child(3) .card-icon {
        background-color: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
    }
    
    .card:nth-child(4) .card-icon {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning-color);
    }
    
    .card:nth-child(5) .card-icon {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
    }
    
    .card:nth-child(6) .card-icon {
        background-color: rgba(26, 188, 156, 0.1);
        color: #1abc9c;
    }
    
    .card-info h3 {
        margin: 0 0 5px 0;
        font-size: 1rem;
        color: #7f8c8d;
    }
    
    .card-info p {
        margin: 0;
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--secondary-color);
    }
    
    /* Charts and tables section */
    .dashboard-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .chart-container, .table-container {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 20px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .section-header h2 {
        margin: 0;
        font-size: 1.2rem;
        color: var(--secondary-color);
    }
    
    .section-header a {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 0.9rem;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: rgba(52, 152, 219, 0.1);
        color: var(--primary-color);
    }
    
    .badge-warning {
        background-color: rgba(243, 156, 18, 0.1);
        color: var(--warning-color);
    }
    
    .badge-danger {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
    }
    
    /* Responsive styles */
    @media (max-width: 1200px) {
        .dashboard-section {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .sidebar-toggle {
            display: block;
        }
        
        .cards {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    @media (max-width: 480px) {
        .cards {
            grid-template-columns: 1fr;
        }
        
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .header-right {
            width: 100%;
            justify-content: space-between;
        }
    }
    
    /* Placeholder for chart */
    .chart-placeholder {
        height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: var(--border-radius);
        color: #7f8c8d;
    }
</style>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Lecturer Portal</span>
        </div>
        <ul class="nav">
                <li class="active"><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Lecturer Dashboard <?php echo htmlspecialchars($departmentId); ?></h1>
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
            <!-- Cards -->
            <div class="cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3>Students Taught</h3>
                        <p><?php echo htmlspecialchars($total_students); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-info">
                        <h3>Modules Taught</h3>
                        <p><?php echo htmlspecialchars($modules_taught); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3>Assignments Given</h3>
                        <p><?php echo htmlspecialchars($assignments_given); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-info">
                        <h3>Submissions Received</h3>
                        <p><?php echo htmlspecialchars($submissions_made); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-info">
                        <h3>Tutors Supervised</h3>
                        <p><?php echo htmlspecialchars($tutors_supervised); ?></p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Sections -->
            <div class="dashboard-section">
                <!-- Upcoming Timetable -->
                <div class="table-container">
                    <div class="section-header">
                        <h2>Upcoming Schedule</h2>
                        <a href="timetable.php">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Module</th>
                                <th>Type</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetable as $session): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($session['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($session['start_time'] . ' - ' . $session['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($session['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($session['type']); ?></td>
                                    <td><?php echo htmlspecialchars($session['location']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($timetable)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No upcoming sessions</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Assignments to Grade -->
                <div class="table-container">
                    <div class="section-header">
                        <h2>Assignments to Grade</h2>
                        <a href="grade.php">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Module</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_grading as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['module_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                $dueDate = strtotime($assignment['due_date']);
                                                $today = strtotime('today');
                                                $diff = ($dueDate - $today) / (60 * 60 * 24);
                                                
                                                if ($diff < 0) {
                                                    echo 'badge-danger';
                                                } elseif ($diff < 7) {
                                                    echo 'badge-warning';
                                                } else {
                                                    echo 'badge-primary';
                                                }
                                            ?>">
                                            <?php 
                                                if ($diff < 0) {
                                                    echo 'Overdue';
                                                } elseif ($diff < 7) {
                                                    echo 'Due Soon';
                                                } else {
                                                    echo 'Upcoming';
                                                }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($upcoming_grading)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No assignments to grade</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Tutor-Student List Section -->
            <div class="table-container">
                <div class="section-header">
                    <h2>Tutor-Student Assignments</h2>
                    <a href="student.php">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tutor</th>
                            <th>Student</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutor_student_list as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['tutor_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['student_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tutor_student_list)): ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">No tutor-student assignments</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Sidebar toggle functionality
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>