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

// Fetch staff from the central database
$stmt_central = $pdo_central->prepare("
    SELECT
        a.admin_id,
        a.first_name,
        a.last_name,
        a.uni_email,
        a.status,
        d.department_name,
        r.role_name
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
");
$stmt_central->execute();
$staff_central = $stmt_central->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff from the CS database
$stmt_cs = $pdo_cs->prepare("
    SELECT
        s.staff_id,
        s.first_name,
        s.last_name,
        s.uni_email,
        s.status,
        d.department_name,
        r.role_name
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
");
$stmt_cs->execute();
$staff_cs = $stmt_cs->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff from the BM database
$stmt_bm = $pdo_bm->prepare("
    SELECT
        s.staff_id,
        s.first_name,
        s.last_name,
        s.uni_email,
        s.status,
        d.department_name,
        r.role_name
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
");
$stmt_bm->execute();
$staff_bm = $stmt_bm->fetchAll(PDO::FETCH_ASSOC);

// Combine staff data from all databases
$staff = array_merge($staff_central, $staff_cs, $staff_bm);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar and Header (same as before) -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Add Staff</h1>
            </div>

            <!-- Staff List Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $staff_member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff_member['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['uni_email']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['status']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['role_name']); ?></td>
                                <td>
                                    <a href="edit_staff.php?id=<?php echo $staff_member['staff_id']; ?>" class="btn-edit">
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