<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);
// Function to fetch programmes from a specific database
function fetchProgrammes($dbName) {
    $pdo = getDatabaseConnection($dbName); // Connect to the database
    $stmt = $pdo->prepare(query: "
        SELECT p.*, d.department_name
            FROM programme p
        JOIN departments d ON p.department_id = d.department_id
        ORDER by p.programme_id;
"); // Fetch all programmes
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch programmes from the 'cs' database
$csProgrammes = fetchProgrammes('cs');

// Fetch programmes from the 'bm' database
$bmProgrammes = fetchProgrammes('bm');

// Combine the results into a single array
$programmes = array_merge($csProgrammes, $bmProgrammes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes Management</title>
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
                <li class="active"><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
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
                    <h1>Programmes Management</h1>
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
                    <a href="add_programme.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New 
                    </a>
                </div>    
            <!-- Content Area -->
            <div class="content">
                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="search" placeholder="Search by ID or name">
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
                    <button class="btn" onclick="applyFilters()">Apply</button>
                </div>

                <!-- Programme List Subtitle -->
                <h2 class="programme-list-subtitle">List of Programmes</h2>

                <!-- Programme List Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Programme ID</th>
                                <th>Programme Name</th>
                                <th>Department</th>
                                <th>Duration (Years)</th>
                                <th>Local Fees (£)</th>
                                <th>International Fees (£)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programmes as $programme): ?>
                            <tr>
                                <td>
                                    <a href="programme_details.php?id=<?php echo $programme['programme_id']; ?>&department_id=<?php echo $programme['department_id']; ?>" class="clickable">
                                        <?php echo $programme['programme_id']; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="programme_details.php?id=<?php echo $programme['programme_id']; ?>&department_id=<?php echo $programme['department_id']; ?>" class="clickable">
                                        <?php echo $programme['programme_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo $programme['department_name']; ?></td>
                                <td><?php echo $programme['duration_years']; ?></td>
                                <td><?php echo number_format($programme['local_fees'], 2); ?></td>
                                <td><?php echo number_format($programme['international_fees'], 2); ?></td>
                                <td>
                                    <button class="btn-edit" onclick="editProgramme('<?php echo $programme['programme_id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="delete_programme.php?id=<?php echo $programme['programme_id']; ?>" class="btn-delete" 
                                    onclick="return confirm('Are you sure you want to delete this programme?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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

        // Function to redirect to edit programme page
        function editProgramme(programmeId) {
            window.location.href = `edit_programme.php?programme_id=${programmeId}`;
        }
s
    </script>
</body>
</html>