<?php
// dashboard.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is an admin
check_role(required_role: GLOBAL_ADMIN);

// Define departments
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
    // Add more departments if needed
];

// Initialize dashboard data
$dashboardData = [];
$recentEnrolments = [];

// Set the specific academic year
$academicYear = '2024/5';

// Fetch data from each department
foreach ($departments as $department) {
    $departmentId = $department['department_id'];
    $departmentName = $department['department_name'];

    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch department statistics
    $staffCount = $pdo->query("SELECT COUNT(*) AS total_staff FROM staff")->fetch(PDO::FETCH_ASSOC)['total_staff'];
    $studentsCount = $pdo->query("SELECT COUNT(*) AS total_students FROM students")->fetch(PDO::FETCH_ASSOC)['total_students'];
    $programmesCount = $pdo->query("SELECT COUNT(*) AS total_programmes FROM programme")->fetch(PDO::FETCH_ASSOC)['total_programmes'];
    $modulesCount = $pdo->query("SELECT COUNT(*) AS total_modules FROM modules")->fetch(PDO::FETCH_ASSOC)['total_modules'];
    
    // Query for enrolments count for 2024/5 academic year
    $enrolmentsCount = $pdo->query("SELECT COUNT(*) AS total_enrolments FROM programme_enrolment WHERE academic_year = '2024/5'")
                          ->fetch(PDO::FETCH_ASSOC)['total_enrolments'];
    
    // Query for upcoming exams
    $upcomingExams = $pdo->query("SELECT COUNT(*) AS upcoming_exams FROM exam WHERE exam_date >= CURRENT_DATE")
                        ->fetch(PDO::FETCH_ASSOC)['upcoming_exams'];

    // Store the data for this department
    $dashboardData[$departmentId] = [
        'department_name' => $departmentName,
        'total_staff' => $staffCount,
        'total_students' => $studentsCount,
        'total_programmes' => $programmesCount,
        'total_modules' => $modulesCount,
        'total_enrolments' => $enrolmentsCount,
        'upcoming_exams' => $upcomingExams,
    ];

    // Fetch recent enrolments for this department (last 10) for 2024/5 academic year
    $stmt = $pdo->prepare("
        SELECT 
            pe.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            :department_name AS department_name,
            p.programme_name,
            pe.enrolment_date,
            'Completed' AS status
        FROM programme_enrolment pe
        JOIN students s ON pe.student_id = s.student_id
        JOIN programme p ON pe.programme_id = p.programme_id
        WHERE pe.academic_year = '2024/5'
        ORDER BY pe.enrolment_date DESC
        LIMIT 3
    ");
    $stmt->execute([
        'department_name' => $departmentName
    ]);
    $departmentEnrolments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge with recent enrolments array
    $recentEnrolments = array_merge($recentEnrolments, $departmentEnrolments);
}

// Sort all enrolments by date (newest first)
usort($recentEnrolments, function($a, $b) {
    return strtotime($b['enrolment_date']) - strtotime($a['enrolment_date']);
});

// Keep only the 10 most recent enrolments across all departments
$recentEnrolments = array_slice($recentEnrolments, 0, 10);
?>

<!-- The HTML part remains the same as in the previous dashboard -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Admin Dashboard</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
    
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--secondary-color);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-profile i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .logout-btn button {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .logout-btn button:hover {
            background-color: #c0392b;
        }
        
        
        /* Content styles */
        .content {
            padding: 20px;
        }
        
        /* Summary statistics */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            text-align: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Department cards grid */
        .department-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .department-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            transition: transform 0.3s;
        }
        
        .department-card:hover {
            transform: translateY(-5px);
        }
        
        .department-card h2 {
            margin-top: 0;
            color: var(--secondary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .department-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .metric {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        /* Different colors for each metric icon */
        .metric:nth-child(1) .metric-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .metric:nth-child(2) .metric-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .metric:nth-child(3) .metric-icon {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        .metric:nth-child(4) .metric-icon {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .metric:nth-child(5) .metric-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .metric:nth-child(6) .metric-icon {
            background-color: rgba(26, 188, 156, 0.1);
            color: #1abc9c;
        }
        
        .metric-value {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--secondary-color);
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        /* Recent activity table */
        .recent-activity {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-top: 30px;
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
        
        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .badge-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .badge-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        /* Enrollment chart container */
        .chart-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .chart-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            color: #7f8c8d;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .department-cards {
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
            
            .department-metrics {
                grid-template-columns: 1fr;
            }
            
            .summary-stats {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .summary-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
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
            <li class="active"><a href="global_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
            <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
            <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
            <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
            <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
            <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
            <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
            <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
            <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
            <li><a href="tutor.php"><i class="fas fa-chalkboard"></i><span>Assign Tutor</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Global Admin Dashboard</h1>
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
            <!-- Summary Statistics -->
            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: var(--primary-color);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo array_sum(array_column($dashboardData, 'total_students')); ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: var(--success-color);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-value"><?php echo array_sum(array_column($dashboardData, 'total_staff')); ?></div>
                    <div class="stat-label">Total Staff</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-value"><?php echo array_sum(array_column($dashboardData, 'total_programmes')); ?></div>
                    <div class="stat-label">Total Programmes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(243, 156, 18, 0.1); color: var(--warning-color);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?php echo array_sum(array_column($dashboardData, 'total_modules')); ?></div>
                    <div class="stat-label">Total Modules</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(26, 188, 156, 0.1); color: #1abc9c;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-value"><?php echo array_sum(array_column($dashboardData, 'total_enrolments')); ?></div>
                    <div class="stat-label">Total Enrolments</div>
                </div>
            </div>

            <!-- Enrollment Trends Chart -->
            <!-- <div class="chart-container">
                <div class="section-header">
                    <h2>Department Enrollment Trends</h2>
                    <a href="enrolment.php">View Details</a>
                </div>
                <div class="chart-placeholder">
                    <p>Enrollment trends chart would appear here (implement with Chart.js)</p>
                </div>
            </div> -->

            <!-- Department Cards -->
            <div class="department-cards">
                <?php foreach ($dashboardData as $departmentId => $data): ?>
                    <div class="department-card">
                        <h2><?php echo htmlspecialchars($data['department_name']); ?></h2>
                        <div class="department-metrics">
                            <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['total_students']; ?></div>
                                    <div class="metric-label">Students</div>
                                </div>
                            </div>
                            <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['total_staff']; ?></div>
                                    <div class="metric-label">Staff</div>
                                </div>
                            </div>
                            <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['total_programmes']; ?></div>
                                    <div class="metric-label">Programmes</div>
                                </div>
                            </div>
                            <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['total_modules']; ?></div>
                                    <div class="metric-label">Modules</div>
                                </div>
                            </div>
                            <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['total_enrolments']; ?></div>
                                    <div class="metric-label">Enrolments</div>
                                </div>
                            </div>
                            <!-- <div class="metric">
                                <div class="metric-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <div class="metric-value"><?php echo $data['upcoming_exams']; ?></div>
                                    <div class="metric-label">Upcoming Exams</div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Activity Table -->
            <div class="recent-activity">
                <div class="section-header">
                    <h2>Recent Department Enrolments</h2>
                    <a href="enrolment.php">View All</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Programme</th>
                            <th>Enrolment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEnrolments as $enrolment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrolment['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($enrolment['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrolment['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrolment['programme_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($enrolment['enrolment_date'])); ?></td>
                                <td>
                                    <span class="badge 
                                        <?php echo $enrolment['status'] === 'Completed' ? 'badge-success' : 
                                              ($enrolment['status'] === 'Pending' ? 'badge-warning' : 'badge-info'); ?>">
                                        <?php echo htmlspecialchars($enrolment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
    
    // You would add Chart.js initialization here for real charts
    // Example for enrollment trends:
    /*
    const ctx = document.createElement('canvas');
    document.querySelector('.chart-placeholder').replaceWith(ctx);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($dashboardData, 'department_name')); ?>,
            datasets: [{
                label: 'Current Enrolments',
                data: <?php echo json_encode(array_column($dashboardData, 'total_enrolments')); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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