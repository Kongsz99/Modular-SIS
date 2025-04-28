<?php
// module.php

require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is logged in and has the required role
check_role(required_role: DEPARTMENT_ADMIN);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}
// Fetch modules and lecturers from the CS database
try {
    // Connect to the CS department's database
    $pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database

    // Fetch all modules from the CS database
    $moduleQuery = "SELECT module_id, module_name, level, semester, credits FROM modules ORDER BY module_id ";
    $moduleStmt = $pdo->prepare($moduleQuery);
    $moduleStmt->execute();
    $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all staff members who are lecturers from the CS database
    $lecturerQuery = "SELECT staff_id, CONCAT(first_name, ' ', last_name) AS staff_name 
                      FROM staff 
                      ";
    $lecturerStmt = $pdo->prepare($lecturerQuery);
    $lecturerStmt->execute();
    $lecturers = $lecturerStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch assigned lecturers to modules from the CS database
    $assignedQuery = "SELECT al.staff_id, CONCAT(s.first_name, ' ', s.last_name) AS staff_name, al.module_id, m.module_name 
                      FROM assigned_lecturers al
                      JOIN staff s ON al.staff_id = s.staff_id
                      JOIN modules m ON al.module_id = m.module_id
                      ORDER BY m.module_id ASC";
    $assignedStmt = $pdo->prepare($assignedQuery);
    $assignedStmt->execute();
    $assignedLecturers = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
                <li class="active"><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
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
                    <h1>Module Management</h1>
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
                    <a href="add_module.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>

                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="search" placeholder="Search by ID or name">
                    <select id="levelSelect" name="level">
                        <option value="">Filter by level</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <select id="semesterSelect" name="semester">
                        <option value="">Filter by semester</option>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                    <button class="btn" onclick="applyFilters()">Apply</button>
                </div>

                <!-- Module List Subtitle -->
                <h2 class="list-subtitle">List of Modules</h2>

                <!-- Module List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Module ID</th>
                                <th>Module Name</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                                <tr>
                                    <td><a href="module_details.php?id=<?php echo htmlspecialchars($module['module_id']); ?>" class="clickable">
                                        <?php echo htmlspecialchars($module['module_id']); ?>
                                    </a></td>
                                    <td><a href="module_details.php?id=<?php echo htmlspecialchars($module['module_id']); ?>" class="clickable">
                                        <?php echo htmlspecialchars($module['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($module['level']); ?></td>
                                    <td><?php echo htmlspecialchars($module['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($module['credits']); ?></td>
                                    <td>
                                        <button class="btn-edit" onclick="editModule('<?php echo htmlspecialchars($module['module_id']); ?>')"><i class='fas fa-edit'></i></button>
                                        <button class="btn-delete" onclick="deleteModule('<?php echo htmlspecialchars($module['module_id']); ?>')"><i class='fas fa-trash-alt'></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h2>Modules Assignment</h2>

                <!-- Assign Lecturer to Module Form -->
                <div class="search-filter">
                    <form id="assign-lecturer-form" method="POST" action="assign_lecturer.php">
                        <select id="module" name="module" required>
                            <option value="">Select a Module</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module['module_id']); ?>">
                                    <?php echo htmlspecialchars($module['module_id']); ?> - <?php echo htmlspecialchars($module['module_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="lecturer" name="lecturer" required>
                            <option value="">Select a Lecturer</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <option value="<?php echo htmlspecialchars($lecturer['staff_id']); ?>">
                                    <?php echo htmlspecialchars($lecturer['staff_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn" id="assign-btn">Assign Lecturer</button>
                    </form>
                </div>

                <!-- Assigned Lecturers Table -->
                <h3>Assigned Lecturers</h3>
                <table id="assigned-lecturers">
                    <thead>
                        <tr>
                            <th>Lecturer ID</th>
                            <th>Lecturer Name</th>
                            <th>Module ID</th>
                            <th>Module Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedLecturers as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['module_id']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['module_name']); ?></td>
                                <td>
                                    <button class="btn-delete" onclick="removeAssignment('<?php echo htmlspecialchars($assignment['staff_id']); ?>', '<?php echo htmlspecialchars($assignment['module_id']); ?>')"><i class='fas fa-trash-alt'></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Function to apply search and filter
        function applyFilters() {
            const searchQuery = document.getElementById('search').value.toLowerCase();
            const levelFilter = document.getElementById('levelSelect').value;
            const semesterFilter = document.getElementById('semesterSelect').value;
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                const level = row.cells[2].textContent;
                const semester = row.cells[3].textContent;

                const matchesSearch = id.includes(searchQuery) || name.includes(searchQuery);
                const matchesLevel = levelFilter === '' || level === levelFilter;
                const matchesSemester = semesterFilter === '' || semester === semesterFilter;

                if (matchesSearch && matchesLevel && matchesSemester) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Function to redirect to edit module page
        function editModule(moduleId) {
            window.location.href = `edit_module.php?id=${moduleId}`;
        }

        function deleteModule(moduleId) {
            if (confirm(`Are you sure you want to delete Module ID ${moduleId}?`)) {
                fetch(`delete_module.php`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: moduleId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Module ID ${moduleId} deleted`);
                        location.reload(); // Refresh the page
                    } else {
                        alert('Failed to delete module.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function removeAssignment(staffId, moduleId) {
            if (confirm("Are you sure you want to remove this assignment?")) {
                fetch(`remove_lecturer_assignment.php`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ staff_id: staffId, module_id: moduleId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Assignment removed successfully!');
                        location.reload(); // Refresh the page
                    } else {
                        alert('Failed to remove assignment.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>