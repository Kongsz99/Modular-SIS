<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Get the database connection (CS or BM)
$database = isset($_GET['db']) && in_array($_GET['db'], ['cs', 'bm']) ? $_GET['db'] : 'cs';
$pdo = getDatabaseConnection($database);

// Check if a staff ID is provided in the URL
if (isset($_GET['id'])) {
    $staff_id = $_GET['id'];

    // Fetch staff details from the respective database
    $stmt = $pdo->prepare("SELECT
            s.staff_id AS id,
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
        WHERE
            s.staff_id = :staff_id");
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->execute();
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    // If staff not found, redirect back to the list
    if (!$staff) {
        header('Location: index.php');
        exit;
    }

    // Handle form submission for editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $status = $_POST['status'];
        $department = $_POST['department'];
        $role = $_POST['role'];

        // Update the staff details in the respective database
        $updateStmt = $pdo->prepare("UPDATE staff SET
            first_name = :first_name,
            last_name = :last_name,
            uni_email = :email,
            status = :status
            WHERE staff_id = :staff_id");
        $updateStmt->bindParam(':first_name', $first_name);
        $updateStmt->bindParam(':last_name', $last_name);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':staff_id', $staff_id);
        $updateStmt->execute();

        // Redirect to the list after update
        header('Location: index.php');
        exit;
    }
} else {
    // If no ID is provided, redirect back to the list
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<script>
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
                    <h1>Edit Staff</h1>
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
            
            <div class="content">
                 <!-- Back Button -->
                 <a href="staff.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Staff List
                </a>

            <!-- Edit Staff Form -->
            <div class="form-container">
                <form method="POST">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" required>

                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($staff['last_name']); ?>" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['uni_email']); ?>" required>

                    <label for="status">Status</label>
                    <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($staff['status']); ?>" required>

                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($staff['department_name']); ?>" disabled>

                    <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($staff['role_name']); ?>" disabled>
                </div>
                <div class="form-group submit-button">
                    <button type="submit" class="btn">Update Admin</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
