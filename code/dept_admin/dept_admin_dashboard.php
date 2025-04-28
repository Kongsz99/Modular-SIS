<?php
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

// Fetch department name
$departmentName = '';
$stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = :department_id");
$stmt->execute(['department_id' => $departmentId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $departmentName = $result['department_name'];
}

// Fetch total students
$totalStudents = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_students FROM students");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $totalStudents = $result['total_students'];
}

// Fetch active modules
$activeModules = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS active_modules FROM modules");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $activeModules = $result['active_modules'];
}

// Fetch upcoming exams
$upcomingExams = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS upcoming_exams FROM exam WHERE exam_date >= CURRENT_DATE");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $upcomingExams = $result['upcoming_exams'];
}

// Fetch total staff
$totalStaff = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_staff FROM staff");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $totalStaff = $result['total_staff'];
}

// Fetch pending assignments
$pendingAssignments = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS pending_assignments FROM assignment");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $pendingAssignments = $result['pending_assignments'];
}

// Fetch recent enrollments
$recentEnrollments = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) AS recent_enrollments 
                            FROM programme_enrolment 
                            WHERE academic_year = '2024/5' ");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $recentEnrollments = $result['recent_enrollments'];
}

// Fetch upcoming deadlines
$upcomingDeadlines = [];
$stmt = $pdo->prepare("SELECT a.*, m.module_id FROM assignment a JOIN modules m ON a.module_id = m.module_id WHERE a.due_date >= CURRENT_DATE ORDER BY a.due_date ASC LIMIT 5");
$stmt->execute();
$upcomingDeadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch average grade
$averageGrade = 0;
$stmt = $pdo->prepare("SELECT AVG(total_marks) AS average_grade FROM grade");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $averageGrade = round($result['average_grade'], 2);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Admin Dashboard</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<style>
    :root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #34495e;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

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
            grid-template-columns: 2fr 1fr;
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
        
        /* Upcoming deadlines table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f8f9fa;
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
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li class="active"><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
            </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                <h1>Department Admin Dashboard <?php echo htmlspecialchars($departmentId); ?> </h1>
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
                        <h3>Total Students</h3>
                        <p><?php echo htmlspecialchars($totalStudents); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total Staff</h3>
                        <p><?php echo htmlspecialchars($totalStaff); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-info">
                        <h3>Active Modules</h3>
                        <p><?php echo htmlspecialchars($activeModules); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3>Upcoming Exams</h3>
                        <p><?php echo htmlspecialchars($upcomingExams); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3>Assignments</h3>
                        <p><?php echo htmlspecialchars($pendingAssignments); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="card-info">
                        <h3>Recent Enrolments</h3>
                        <p><?php echo htmlspecialchars($recentEnrollments); ?></p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Sections -->
            <div class="dashboard-section">
                <!-- Chart Section -->
                <!-- <div class="chart-container">
                    <div class="section-header">
                        <h2>Department Overview</h2>
                        <a href="#">View Details</a>
                    </div>
                    <div class="chart-placeholder">
                        <p>Student Enrollment Trend Chart (would be implemented with Chart.js)</p>
                    </div>
                </div> -->
                
                <!-- Upcoming Deadlines -->
                <div class="table-container">
                    <div class="section-header">
                        <h2>Upcoming Deadlines</h2>
                        <a href="assignment.php">View All</a>
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
                            <?php foreach ($upcomingDeadlines as $deadline): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($deadline['title']); ?></td>
                                    <td><?php echo htmlspecialchars($deadline['module_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($deadline['due_date'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                $dueDate = strtotime($deadline['due_date']);
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
                            <?php if (empty($upcomingDeadlines)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No upcoming deadlines</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Activity Section -->
            <div class="chart-container">
                <div class="section-header">
                    <h2>Recent Activity</h2>
                    <a href="#">View All</a>
                </div>
                <div class="chart-placeholder">
                    <p>Recent department activity log would appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Sidebar toggle functionality
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // You would add Chart.js initialization here for real charts
    // Example:
    /*
    const ctx = document.createElement('canvas');
    document.querySelector('.chart-placeholder').replaceWith(ctx);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Student Enrollment',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    */
</script>
</body>
</html>