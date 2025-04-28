<?php
// assign_tutor.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

// Fetch departments for the filter dropdown
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
    // Add more departments as needed
];

// Fetch assigned tutors based on the selected department (if any)
$selectedDepartment = $_GET['department'] ?? '';
$assignedTutors = [];

if ($selectedDepartment) {
    try {
        $pdo = getDatabaseConnection(strtolower($selectedDepartment));
        $sql = "
             SELECT ata.student_id, ata.staff_id,
                    st.first_name AS student_first_name, st.last_name AS student_last_name,
                    sf.first_name AS staff_first_name, sf.last_name AS staff_last_name
                FROM academic_tutor_assigned ata
                JOIN staff sf ON ata.staff_id = sf.staff_id
                JOIN users sfu ON sf.user_id = sfu.user_id
                JOIN students st ON ata.student_id = st.student_id
                JOIN users stu ON st.user_id = stu.user_id
                JOIN user_department ud ON sf.user_id = ud.user_id
                WHERE ud.department_id = :department;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['department' => $selectedDepartment]);

        if ($stmt) {
            $assignedTutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Add department information to each assigned tutor
            foreach ($assignedTutors as &$tutor) {
                $tutor['department'] = strtoupper($selectedDepartment);
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching assigned tutors: " . $e->getMessage();
    }
} else {
    // Fetch assigned tutors from all departments
    foreach ($departments as $department) {
        try {
            $pdo = getDatabaseConnection(strtolower($department['department_id']));
            $sql = "
                SELECT ata.student_id, ata.staff_id,
                    st.first_name AS student_first_name, st.last_name AS student_last_name,
                    sf.first_name AS staff_first_name, sf.last_name AS staff_last_name
                FROM academic_tutor_assigned ata
                JOIN staff sf ON ata.staff_id = sf.staff_id
                JOIN users sfu ON sf.user_id = sfu.user_id
                JOIN students st ON ata.student_id = st.student_id
                JOIN users stu ON st.user_id = stu.user_id
                JOIN user_department ud ON sf.user_id = ud.user_id
                WHERE ud.department_id = :department;
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['department' => $department['department_id']]);

            if ($stmt) {
                $departmentTutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($departmentTutors as $tutor) {
                    $tutor['department'] = strtoupper($department['department_id']); // Add department info
                    $assignedTutors[] = $tutor;
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error fetching assigned tutors for department {$department['department_id']}: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Academic Tutor</title>
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
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li class="active"><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Academic Tutor Management</h1>
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
                    <a href="assign_tutor.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>
        
                <!-- Department Filter -->
                <div class="search-filter">                    
                    <form method="GET" action="tutor.php">
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

                <!-- Assigned Tutor Table -->
                <h2>Assigned Tutors</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Academic Tutor</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignedTutors)): ?>
                            <tr>
                                <td colspan="5">No assigned tutors found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedTutors as $tutor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tutor['staff_id']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['staff_first_name'] . ' ' . $tutor['staff_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($tutor['student_first_name'] . ' ' . $tutor['student_last_name']); ?></td>
                                    <td>
                                    <a href="delete_tutor_assignment.php?staff_id=<?php echo $tutor['staff_id']; ?>&student_id=<?php echo $tutor['student_id']; ?>&department=<?php echo urlencode($tutor['department']); ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this assignment?');">
                                    <i class="fas fa-trash-alt"></i>
                                    </a>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>