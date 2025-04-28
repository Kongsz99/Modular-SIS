<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

function fetchStudents($dbName) {
    $pdo = getDatabaseConnection($dbName); // Connect to the database
    $stmt = $pdo->prepare(query: "
        SELECT pe.*, d.department_name, CONCAT(s.first_name, ' ', s.last_name) AS student_name, d.department_id, programme_name
        FROM programme_enrolment pe
        JOIN programme p ON pe.programme_id = p.programme_id
        JOIN students s ON pe.student_id = s.student_id
        JOIN departments d ON p.department_id = p.department_id
        WHERE p.department_id = d.department_id
        ORDER BY enrolment_date DESC ;

"); // Fetch all programmes
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch programmes from the 'cs' database
$csStudents = fetchStudents('cs');

// Fetch programmes from the 'bm' database
$bmStudents = fetchStudents('bm');

// Combine the results into a single array
$Students = array_merge($csStudents, $bmStudents);


// Fetch departments for the filter dropdown
$pdo_central = getDatabaseConnection('central'); // Connect to the central database
$stmt = $pdo_central->prepare("SELECT department_name FROM departments");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolment Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li class="active"><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
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

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Enrolment Management</h1>
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
                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="search" placeholder="Search by ID or name">
                    <select id="filter-department">
                        <option value="">All Department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo htmlspecialchars($department['department_name']); ?>">
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filter-programme">
                        <option value="">Filter by Programme</option>
                        <option value="computer science">Computer Science</option>
                        <option value="mathematics">Mathematics</option>
                        <option value="physics">Physics</option>
                    </select>
                    <select id="filter-year">
                        <option value="">Filter by Year</option>
                        <option value="2021">2021</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                        <option value="2024">2024</option>
                    </select>
                    <button class="btn" onclick="applyFilters()">Apply</button>
                </div>

                <!-- Enrolment List Subtitle -->
                <h2 class="list-subtitle">List of Enrolments</h2>

                <!-- Enrolment List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Programme</th>
                                <th>Enrolment Date</th>
                                <th>Current Year</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($Students as $Student): ?>
                            <tr>
                                <td><a href="student_details.php?id=<?php echo $Student['student_id']; ?>&department_id=<?php echo $Student['department_id']; ?>" class="clickable">
                                    <?php echo $Student['student_id']; ?>
                                </a></td>
                                <td><a href="student_details.php?id=<?php echo $Student['student_id']; ?>&department_id=<?php echo $Student['department_id']; ?>" class="clickable">
                                    <?php echo $Student['student_name']; ?>
                                </a></td>
                                <td><?php echo $Student['department_name']; ?></td>
                                <td><?php echo $Student['programme_name']; ?></td>
                                <td><?php echo $Student['enrolment_date']; ?></td>
                                <td><?php echo $Student['current_year']; ?></td>
                                <td><?php echo $Student['status']; ?></td>
                                <td>
                                    <button class="btn-edit" onclick="editStudent(<?php echo $Student['student_id']; ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteStudent(<?php echo $Student['student_id']; ?>)"><i class="fas fa-trash-alt"></i></button>
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
        // Function to apply search and filters
        function applyFilters() {
            const searchQuery = document.getElementById('search').value.trim().toLowerCase();
            const departmentFilter = document.getElementById('filter-department').value.trim().toLowerCase();
            const programmeFilter = document.getElementById('filter-programme').value.trim().toLowerCase();
            const yearFilter = document.getElementById('filter-year').value.trim().toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const id = row.cells[0].textContent.trim().toLowerCase();
                const name = row.cells[1].textContent.trim().toLowerCase();
                const department = row.cells[2].textContent.trim().toLowerCase();
                const programme = row.cells[3].textContent.trim().toLowerCase();
                const enrolmentDate = row.cells[4].textContent.trim();
                const year = enrolmentDate.substring(0, 4);

                const matchesSearch = id.includes(searchQuery) || name.includes(searchQuery);
                const matchesDepartment = departmentFilter === '' || department === departmentFilter;
                const matchesProgramme = programmeFilter === '' || programme === programmeFilter;
                const matchesYear = yearFilter === '' || year === yearFilter;

                if (matchesSearch && matchesDepartment && matchesProgramme && matchesYear) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

         // Function to redirect to edit student page
        function editStudent(studentId) {
            window.location.href = `edit_enrolment.html?id=${studentId}`;
        }
    </script>
</body>
</html>