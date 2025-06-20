<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Connect to the CS database
$pdo_cs = getDatabaseConnection('cs');

// Connect to the BM database
$pdo_bm = getDatabaseConnection('bm');

// Fetch students from both departments
$stmtCs = $pdo_cs->prepare("
    SELECT DISTINCT
        sp.student_id,
        s.first_name,
        s.last_name,
        s.uni_email,
        p.programme_name,
        sp.current_year,
        s.status,
        d.department_name
    FROM 
        programme_enrolment sp
    JOIN 
        students s ON sp.student_id = s.student_id
    JOIN 
        programme p ON sp.programme_id = p.programme_id
    JOIN 
        user_department ud ON s.user_id = ud.user_id
    JOIN 
        departments d ON ud.department_id = d.department_id
    WHERE 
        d.department_name = 'Computer Science'
    ORDER BY sp.student_id;
");
$stmtCs->execute();
$studentsCs = $stmtCs->fetchAll(PDO::FETCH_ASSOC);

$stmtBm = $pdo_bm->prepare("
    SELECT DISTINCT
        sp.student_id,
        s.first_name,
        s.last_name,
        s.uni_email,
        p.programme_name,
        sp.current_year,
        s.status,
        d.department_name
    FROM 
        programme_enrolment sp
    JOIN 
        students s ON sp.student_id = s.student_id
    JOIN 
        programme p ON sp.programme_id = p.programme_id
    JOIN 
        user_department ud ON s.user_id = ud.user_id
    JOIN 
        departments d ON ud.department_id = d.department_id
    WHERE 
        d.department_name = 'Business Management'
    ORDER BY sp.student_id;
");
$stmtBm->execute();
$studentsBm = $stmtBm->fetchAll(PDO::FETCH_ASSOC);

// Combine all students
$allStudents = array_merge($studentsCs, $studentsBm);

// Process the students to combine duplicates
$combinedStudents = [];
foreach ($allStudents as $student) {
    $id = $student['student_id'];
    
    if (!isset($combinedStudents[$id])) {
        // If student not seen before, add to array
        $combinedStudents[$id] = [
            'student_id' => $id,
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'uni_email' => $student['uni_email'],
            'departments' => [$student['department_name']],
            'programmes' => [$student['programme_name']],
            'current_year' => $student['current_year'],
            'status' => $student['status']
        ];
    } else {
        // If student exists, add department and programme if not already present
        if (!in_array($student['department_name'], $combinedStudents[$id]['departments'])) {
            $combinedStudents[$id]['departments'][] = $student['department_name'];
        }
        if (!in_array($student['programme_name'], $combinedStudents[$id]['programmes'])) {
            $combinedStudents[$id]['programmes'][] = $student['programme_name'];
        }
    }
}

// Apply department filter if selected
$selectedDepartment = $_GET['department'] ?? '';
if ($selectedDepartment === 'cs') {
    $students = array_filter($combinedStudents, function($student) {
        return in_array('Computer Science', $student['departments']);
    });
} elseif ($selectedDepartment === 'bm') {
    $students = array_filter($combinedStudents, function($student) {
        return in_array('Business Management', $student['departments']);
    });
} else {
    $students = $combinedStudents;
}

// Sort by student ID
usort($students, function($a, $b) {
    return $a['student_id'] - $b['student_id'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="template/sidebar.js"></script>
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
        
</style>
    <script>
        function applyFilters() {
    const searchQuery = document.getElementById('search').value.toLowerCase();
    const departmentFilter = document.getElementById('filter-department').value.toLowerCase();
    const statusFilter = document.getElementById('filter-status').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase(); // Student Name
        const id = row.cells[0].textContent.toLowerCase(); // Student ID
        const department = row.cells[3].textContent.toLowerCase(); // Department
        const status = row.querySelector('.status-dropdown').value.toLowerCase(); // Status

        const matchesSearch = name.includes(searchQuery) || id.includes(searchQuery);
        const matchesDepartment = departmentFilter === '' || department.includes(departmentFilter);
        const matchesStatus = statusFilter === '' || status === statusFilter;

        if (matchesSearch && matchesDepartment && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
    //     function applyFilters() {
    //     const searchQuery = document.getElementById('search').value.toLowerCase();
    //     const departmentFilter = document.getElementById('filter-department').value.toLowerCase();
    //     const statusFilter = document.getElementById('filter-status').value.toLowerCase();
    //     const rows = document.querySelectorAll('tbody tr');

    //     rows.forEach(row => {
    //         const name = row.cells[1].textContent.toLowerCase(); // Student Name
    //         const id = row.cells[0].textContent.toLowerCase(); // Student ID
    //         const department = row.cells[3].textContent.toLowerCase(); // Department
    //         const status = row.querySelector('.status-dropdown').value.toLowerCase(); // Status

    //         const matchesSearch = name.includes(searchQuery) || id.includes(searchQuery);
    //         const matchesDepartment = departmentFilter === '' || department === departmentFilter;
    //         const matchesStatus = statusFilter === '' || status === statusFilter;

    //         if (matchesSearch && matchesDepartment && matchesStatus) {
    //             row.style.display = '';
    //         } else {
    //             row.style.display = 'none';
    //         }
    //     });
    // }

        function toggleEditStatus() {
            console.log('toggleEditStatus called'); // Debugging line
            const statusDropdowns = document.querySelectorAll('.status-dropdown');
            const editStatusButton = document.getElementById('editStatusButton');
            const isEditing = editStatusButton.textContent.trim() === 'Disable Edit Status';

            console.log('Dropdowns:', statusDropdowns); // Debugging line
            console.log('Is Editing:', isEditing); // Debugging line

            statusDropdowns.forEach(dropdown => {
                dropdown.disabled = isEditing;
            });

            editStatusButton.textContent = isEditing ? 'Enable Edit Status' : 'Disable Edit Status';
        }

        function updateStatus(studentId, status) {
            // Send an AJAX request to update the student status
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Status updated successfully!');
                    } else {
                        alert('Failed to update status: ' + (response.error || 'Unknown error'));
                    }
                }
            };
            xhr.send('student_id=' + studentId + '&status=' + status);
        }

    </script>
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
                <li><a href="global_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li class="active"><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
            </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Student Management</h1>
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
                <a href="add_student.php" class="btn-new">
                    <i class="fas fa-plus"></i>
                    New
                </a>
            </div>

            <div class="content">
                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="search" placeholder="Search by name, ID" value="<?php echo $_GET['search'] ?? ''; ?>">
                    <select id="filter-department">
                        <option value="">All Department</option>
                        <?php
                        // Fetch departments for the filter dropdown
                        $stmt = $pdo->prepare("SELECT department_name FROM departments");
                        $stmt->execute();
                        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($departments as $department): ?>
                            <option value="<?php echo htmlspecialchars($department['department_name']); ?>">
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filter-status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="suspended" <?php echo ($_GET['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="withdraw" <?php echo ($_GET['status'] ?? '') === 'withdraw' ? 'selected' : ''; ?>>Withdraw</option>
                    </select>
                    <button class="btn" onclick="applyFilters()">Apply</button>
                </div>

            <!-- Student List Subtitle -->
            <h2 class="list-subtitle">List of Students</h2>

            <!-- Edit Status Button -->
            <button id="editStatusButton" class="btn" onclick="toggleEditStatus()">Enable Edit Status</button>

        <!-- Student List Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <!-- <th>Email</th> -->
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Current Year</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="clickable">
                                    <?php echo htmlspecialchars($student['student_id']); ?>
                                </a>
                            </td>
                            <td>
                                <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="clickable">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </a>
                            </td>
                            <!-- <td><?php echo htmlspecialchars($student['uni_email']); ?></td> -->
                            <td><?php echo htmlspecialchars(implode(' & ', $student['departments'])); ?></td>
                            <td><?php echo htmlspecialchars(implode(' & ', $student['programmes'])); ?></td>
                            <td><?php echo htmlspecialchars($student['current_year']); ?></td>
                            <td>
                                <select class="status-dropdown" onchange="updateStatus(<?php echo $student['student_id']; ?>, this.value)" disabled>
                                    <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="completed" <?php echo $student['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="suspended" <?php echo $student['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="withdraw" <?php echo $student['status'] === 'withdraw' ? 'selected' : ''; ?>>Withdraw</option>
                                </select>
                            </td>
                            <td>
                                <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>