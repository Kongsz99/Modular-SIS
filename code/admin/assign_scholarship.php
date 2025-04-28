<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);
// Fetch scholarships from the central database
$pdo_central = getDatabaseConnection('central');
$stmt = $pdo_central->prepare("SELECT scholarship_id, scholarship_name FROM scholarship");
$stmt->execute();
$scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch students from a specific database
function fetchStudentsinCS($dbName) {
    $pdo = getDatabaseConnection($dbName); // Connect to the database
    $stmt = $pdo->prepare("
    SELECT DISTINCT
            s.student_id,
            s.first_name,
            s.last_name,
            d.department_name
        FROM 
            students s
        JOIN 
            user_department ud ON s.user_id = ud.user_id
        JOIN 
            departments d ON ud.department_id = d.department_id
        WHERE 
            d.department_name = 'Computer Science' -- Filter by department
        ORDER BY s.student_id;
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchStudentsinBM($dbName) {
    $pdo = getDatabaseConnection($dbName); // Connect to the database
    $stmt = $pdo->prepare("
    SELECT DISTINCT
            s.student_id,
            s.first_name,
            s.last_name,
            d.department_name
        FROM 
            students s
        JOIN 
            user_department ud ON s.user_id = ud.user_id
        JOIN 
            departments d ON ud.department_id = d.department_id
        WHERE 
            d.department_name = 'Business Management' -- Filter by department
        ORDER BY s.student_id;
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fetch students from the 'cs' database
$csStudents = fetchStudentsinCS('cs');

// Fetch students from the 'bm' database
$bmStudents = fetchStudentsinBM('bm');

// Combine the results into a single array
$students = array_merge($csStudents, $bmStudents);

// Handle scholarship assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $scholarship_id = $_POST['scholarship_id'];

    // Determine which databases the student belongs to
    $student_in_cs = in_array($student_id, array_column($csStudents, 'student_id'));
    $student_in_bm = in_array($student_id, array_column($bmStudents, 'student_id'));

    // Assign scholarship in the respective databases
    if ($student_in_cs) {
        $pdo_cs = getDatabaseConnection('cs');
        $stmt = $pdo_cs->prepare("
            INSERT INTO scholarship_assignment (scholarship_id, student_id)
            VALUES (:scholarship_id, :student_id)
        ");
        $stmt->execute([
            ':scholarship_id' => $scholarship_id,
            ':student_id' => $student_id
        ]);
    }

    if ($student_in_bm) {
        $pdo_bm = getDatabaseConnection('bm');
        $stmt = $pdo_bm->prepare("
            INSERT INTO scholarship_assignment (scholarship_id, student_id)
            VALUES (:scholarship_id, :student_id)
        ");
        $stmt->execute([
            ':scholarship_id' => $scholarship_id,
            ':student_id' => $student_id
        ]);
    }

    // Set a success message
    $_SESSION['message'] = "Scholarship assigned successfully!";
    header("Location: assign_scholarship.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Scholarship</title>
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
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li class="active"><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
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
                    <h1>Assign Scholarship</h1>
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
                    <button class="btn" onclick="applyFilters()">Apply</button>
                </div>

                <!-- Student List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                <td><?php echo $student['department_name']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <select name="scholarship_id" required>
                                            <option value="">Select Scholarship</option>
                                            <?php foreach ($scholarships as $scholarship): ?>
                                                <option value="<?php echo $scholarship['scholarship_id']; ?>">
                                                    <?php echo $scholarship['scholarship_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn">Assign</button>
                                    </form>
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
        // Function to apply search and filter
        function applyFilters() {
            const searchQuery = document.getElementById('search').value.toLowerCase();
            const departmentFilter = document.getElementById('filter-department').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                const department = row.cells[2].textContent.toLowerCase();

                const matchesSearch = id.includes(searchQuery) || name.includes(searchQuery);
                const matchesDepartment = departmentFilter === '' || department === departmentFilter;

                if (matchesSearch && matchesDepartment) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>