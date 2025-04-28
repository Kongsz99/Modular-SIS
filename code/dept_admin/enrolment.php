<?php
// Include the database connection and authentication
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

    $stmt = $pdo->prepare(query: "
        SELECT pe.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name, p.programme_name
        FROM programme_enrolment pe
        JOIN programme p ON pe.programme_id = p.programme_id
        JOIN students s ON pe.student_id = s.student_id
        ORDER BY enrolment_date DESC ;

"); // Fetch all programmes
    $stmt->execute();
    $Students = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // $stmtprog = $pdo->prepare(query:"
    //     SELECT DISTINCT FROM programme 
    // ");

    // $stmtprog->execute();
    // $programmes = $stmtprog->fetchAll(PDO::FETCH_ASSOC);
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
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li class="active"><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
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
        <select id="filter-programme">
            <option value="">All Programme</option>
            <?php
            // Fetch programmes for the filter dropdown
            $stmt = $pdo->prepare("SELECT programme_name FROM programme ORDER BY programme_id");
            $stmt->execute();
            $progs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($progs as $prog): ?>
                <option value="<?php echo htmlspecialchars($prog['programme_name']); ?>">
                    <?php echo htmlspecialchars($prog['programme_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="filter-year">
            <option value="">All Years</option>
            <option value="2023">2023</option>
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
        </select>
        <select id="filter-status">
            <option value="">All Status</option>
            <option value="not-enroled">Not Enroled</option>
            <option value="in-progress">In-Progress</option>
            <option value="enroled">Enroled</option>
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
                        <td><a href="student_details.php?id=<?php echo $Student['student_id']; ?>" class="clickable">
                            <?php echo $Student['student_id']; ?>
                        </a></td>
                        <td><a href="student_details.php?id=<?php echo $Student['student_id']; ?>" class="clickable">
                            <?php echo $Student['student_name']; ?>
                        </a></td>
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

<script>
    // Function to apply search and filters
    function applyFilters() {
        const searchQuery = document.getElementById('search').value.trim().toLowerCase();
        const programmeFilter = document.getElementById('filter-programme').value.trim().toLowerCase();
        const statusFilter = document.getElementById('filter-status').value.trim().toLowerCase();
        const yearFilter = document.getElementById('filter-year').value.trim().toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const id = row.cells[0].textContent.trim().toLowerCase();
            const name = row.cells[1].textContent.trim().toLowerCase();
            const programme = row.cells[2].textContent.trim().toLowerCase();
            const status = row.cells[5].textContent.trim().toLowerCase(); // Corrected index for status
            const enrolmentDate = row.cells[3].textContent.trim();
            const year = enrolmentDate.substring(0, 4); // Extract year from enrolment date

            const matchesSearch = id.includes(searchQuery) || name.includes(searchQuery);
            const matchesProgramme = programmeFilter === '' || programme === programmeFilter;
            const matchesStatus = statusFilter === '' || status === statusFilter;
            const matchesYear = yearFilter === '' || year === yearFilter;

            if (matchesSearch && matchesProgramme && matchesStatus && matchesYear) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });
    }

    // Optional: Add event listeners for real-time filtering
    document.getElementById('search').addEventListener('input', applyFilters);
    document.getElementById('filter-programme').addEventListener('change', applyFilters);
    document.getElementById('filter-status').addEventListener('change', applyFilters);
    document.getElementById('filter-year').addEventListener('change', applyFilters);

         // Function to redirect to edit student page
        function editStudent(studentId) {
            window.location.href = `edit_enrolment.html?id=${studentId}`;
        }
    </script>
</body>
</html>