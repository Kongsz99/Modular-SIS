<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Connect to the central database
$pdo_central = getDatabaseConnection('central');

// Connect to the CS database
$pdo_cs = getDatabaseConnection('cs');

// Connect to the BM database
$pdo_bm = getDatabaseConnection('bm');

// Fetch admin from the central database
$stmt_central = $pdo_central->prepare("
    SELECT
        a.admin_id AS id,
        a.first_name,
        a.last_name,
        a.uni_email,
        a.status,
        d.department_name,
        r.role_name,
        'admin' AS type
    FROM
        admin a
    JOIN
        users u ON a.user_id = u.user_id
    JOIN
        user_department ud ON u.user_id = ud.user_id
    JOIN
        departments d ON ud.department_id = d.department_id
    JOIN
        role r ON u.role_id = r.role_id
    ORDER BY a.admin_id ASC
");
$stmt_central->execute();
$admin_central = $stmt_central->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff from the CS database
$stmt_cs = $pdo_cs->prepare("
    SELECT
        s.staff_id AS id,
        s.first_name,
        s.last_name,
        s.uni_email,
        s.status,
        d.department_name,
        r.role_name,
        'staff' AS type
    FROM
        staff s
    JOIN
        users u ON s.user_id = u.user_id
    JOIN
        user_department ud ON u.user_id = ud.user_id
    JOIN
        departments d ON ud.department_id = d.department_id
    JOIN
        role r ON u.role_id = r.role_id
    ORDER BY s.staff_id ASC
");
$stmt_cs->execute();
$staff_cs = $stmt_cs->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff from the BM database
$stmt_bm = $pdo_bm->prepare("
    SELECT
        s.staff_id AS id,
        s.first_name,
        s.last_name,
        s.uni_email,
        s.status,
        d.department_name,
        r.role_name,
        'staff' AS type
    FROM
        staff s
    JOIN
        users u ON s.user_id = u.user_id
    JOIN
        user_department ud ON u.user_id = ud.user_id
    JOIN
        departments d ON ud.department_id = d.department_id
    JOIN
        role r ON u.role_id = r.role_id
    ORDER BY s.staff_id ASC
");
$stmt_bm->execute();
$staff_bm = $stmt_bm->fetchAll(PDO::FETCH_ASSOC);

// Combine all data
$combined_data = array_merge($admin_central, $staff_cs, $staff_bm);

// Get filter values from request
$selectedDepartment = $_GET['department'] ?? '';
$selectedRole = $_GET['role'] ?? '';
$selectedStatus = $_GET['status'] ?? '';

// Apply filters
if ($selectedDepartment) {
    $combined_data = array_filter($combined_data, function($row) use ($selectedDepartment) {
        return strtolower($row['department_name']) === strtolower($selectedDepartment);
    });
}

if ($selectedRole) {
    $combined_data = array_filter($combined_data, function($row) use ($selectedRole) {
        return strtolower($row['role_name']) === strtolower($selectedRole);
    });
}

if ($selectedStatus) {
    $combined_data = array_filter($combined_data, function($row) use ($selectedStatus) {
        return strtolower($row['status']) === strtolower($selectedStatus);
    });
}

// Sort data - lecturers first, then by ID ascending
usort($combined_data, function($a, $b) {
    // Both are lecturers or both are not - sort by ID
    if (($a['role_name'] === 'lecturer' && $b['role_name'] === 'lecturer') || 
        ($a['role_name'] !== 'lecturer' && $b['role_name'] !== 'lecturer')) {
        return $a['id'] - $b['id'];
    }
    // Lecturer comes first
    return $a['role_name'] === 'lecturer' ? -1 : 1;
});

// Get unique departments for filter dropdown
$stmt_dept = $pdo_central->prepare("SELECT department_name FROM departments");
$stmt_dept->execute();
$departments = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="template/sidebar.js"></script>
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
                <li class="active"><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
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
                    <h1>Staff Management</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
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
                    <a href="add_staff.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>

                <!-- Search and Filter -->
                <div class="search-filter">
                    <input type="text" id="search" placeholder="Search by name, ID" onkeyup="applyFilters()">
                    <select id="filter-role" onchange="applyFilters()">
                        <option value="">All Roles</option>
                        <option value="department_admin" <?= $selectedRole === 'department_admin' ? 'selected' : '' ?>>Department Admin</option>
                        <option value="lecturer" <?= $selectedRole === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
                    </select>
                    <select id="filter-department" onchange="applyFilters()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department['department_name']) ?>" 
                                <?= $selectedDepartment === $department['department_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($department['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filter-status" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="on leave" <?= $selectedStatus === 'on leave' ? 'selected' : '' ?>>On Leave</option>
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
                                <th>Type</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combined_data as $row): ?>
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
                                    <!-- <td><?= htmlspecialchars($row['id']) ?></td> -->
                                    <!-- <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td> -->
                                    <td><?= htmlspecialchars(ucfirst($row['type'])) ?></td>
                                    <td><?= htmlspecialchars($row['department_name']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $row['role_name']))) ?></td>
                                    <td>   
                                        <select class="status-dropdown" onchange="updateStatus('<?= $row['id'] ?>', this.value, '<?= $row['type'] ?>')" disabled>
                                            <option value="active" <?= $row['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= $row['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="on leave" <?= $row['status'] === 'on leave' ? 'selected' : '' ?>>On Leave</option>
                                        </select>
                                    </td>
                                    <td>
                                        <a href="edit_<?= $row['type'] ?>.php?id=<?= $row['id'] ?>" class="btn-edit">
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
    </div>

    <script>
        function applyFilters() {
            const searchQuery = document.getElementById('search').value.toLowerCase();
            const departmentFilter = document.getElementById('filter-department').value.toLowerCase();
            const roleFilter = document.getElementById('filter-role').value.toLowerCase();
            const statusFilter = document.getElementById('filter-status').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const id = row.cells[0].textContent.toLowerCase();
                const department = row.cells[3].textContent.toLowerCase();
                const role = row.cells[4].textContent.toLowerCase().replace(' ', '_');
                const status = row.querySelector('.status-dropdown').value.toLowerCase();

                const matchesSearch = name.includes(searchQuery) || id.includes(searchQuery);
                const matchesDepartment = departmentFilter === '' || department === departmentFilter;
                const matchesRole = roleFilter === '' || role === roleFilter;
                const matchesStatus = statusFilter === '' || status === statusFilter;

                row.style.display = (matchesSearch && matchesDepartment && matchesRole && matchesStatus) ? '' : 'none';
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

        function updateStatus(id, status, type) {
            fetch('update_staff_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&status=${status}&type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status');
            });
        }

        // Apply filters on page load
        document.addEventListener('DOMContentLoaded', applyFilters);
    </script>
</body>
</html>