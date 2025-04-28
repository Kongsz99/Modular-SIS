<?php
// exam.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management']
];

$selectedDepartment = $_GET['department'] ?? '';

foreach ($departments as $department) {
    if ($selectedDepartment && $selectedDepartment !== $department['department_id']) {
        continue;
    }

    $pdo = getDatabaseConnection(strtolower($department['department_id']));
    $sql = "SELECT * FROM exam ORDER BY exam_id ASC";
    $stmt = $pdo->query($sql);

    if ($stmt) {
        $departmentExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($departmentExams as $exam) {
            $exam['department'] = strtoupper($department['department_id']);
            $exams[] = $exam;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="global_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li class="active"><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Exam Management</h1>
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
                    <a href="add_exam.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>
        
                <!-- Department Filter -->
                <div class="search-filter">                    
                    <form method="GET" action="exam.php">
                        <!-- <label for="department">Filter by Department:</label> -->
                        <select id="department" name="department" onchange="this.form.submit()">
                            <option value="">All Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($selectedDepartment === $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['department_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- Exam Table -->
                <h2>List of Exam Schedule</h2>
                <table>
                    <thead>
                        <tr>
                            <!-- <th>Department</th> -->
                            <th>Exam ID</th>
                            <th>Module ID</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="8">No exams found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <!-- <td><?php echo htmlspecialchars($exam['department_name']); ?></td> -->
                                    <td><?php echo htmlspecialchars($exam['exam_id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['module_id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['location']); ?></td>
                                    <td>
                                        <a href="edit_exam.php?exam_id=<?php echo $exam['exam_id']; ?>&department=<?php echo $exam['department']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_exam.php?exam_id=<?php echo $exam['exam_id']; ?>&department=<?php echo $exam['department']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this exam?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>

