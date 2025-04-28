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

// Fetch admin from the central database
$stmt = $pdo->prepare("
    SELECT
        s.staff_id AS id, -- Standardize column name to 'id'
        s.first_name,
        s.last_name,
        s.uni_email,
        s.status
    FROM
        staff s
    ORDER BY id
");
$stmt->execute();
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<script>
    function applyFilters() {
        const searchQuery = document.getElementById('search').value.toLowerCase();
        const statusFilter = document.getElementById('filter-status').value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase(); // Staff/Admin Name
            const id = row.cells[0].textContent.toLowerCase(); // Staff/Admin ID
            const status = row.querySelector('.status-dropdown').value.toLowerCase(); // Status

            const matchesSearch = name.includes(searchQuery) || id.includes(searchQuery);
            const matchesStatus = statusFilter === '' || status === statusFilter;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function toggleEditStatus() {
        const statusDropdowns = document.querySelectorAll('.status-dropdown');
        const editStatusButton = document.getElementById('editStatusButton');
        const isEditing = editStatusButton.textContent.trim() === 'Disable Edit Status';

        statusDropdowns.forEach(dropdown => {
            dropdown.disabled = isEditing;
        });

        editStatusButton.textContent = isEditing ? 'Enable Edit Status' : 'Disable Edit Status';
    }
    function updateStatus(Id, status) {
            // Send an AJAX request to update the student status
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_staff_status.php', true);
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
            xhr.send('staff_id=' +  Id + '&status=' + status);
        }

</script>
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
                <li class="active"><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
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
                    <h1>Staff Management</h1>
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
                    <input type="text" id="search" placeholder="Search by name, ID "> 
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

                <!-- Staff List Subtitle -->
                <h2 class="list-subtitle">List of Staff</h2>

                 <!-- Edit Status Button -->
                <button id="editStatusButton" class="btn" onclick="toggleEditStatus()">Enable Edit Status</button>

            <!-- Staff List Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <!-- <th>Email</th> -->
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $row): ?>
                            <tr>
                                <td>
                                     <a href="staff_details.php?id=<?php echo $row['id']; ?>" class="clickable">
                                            <?php echo htmlspecialchars($row['id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="staff_details.php?id=<?php echo $row['id']; ?>" class="clickable">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                    </a>
                                </td>
                                <!-- <td><?php echo htmlspecialchars($row['uni_email']); ?></td> -->
                                <td>   
                                    <select class="status-dropdown" onchange="updateStatus(<?php echo $row['id']; ?>, this.value)" disabled>
                                        <option value="active" <?php echo $row['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $row['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="on leave" <?php echo $row['status'] === 'on leave' ? 'selected' : ''; ?>>On Leave</option>
                                    </select>
                                </td>
                                <td>
                                    <a href="edit_staff.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>