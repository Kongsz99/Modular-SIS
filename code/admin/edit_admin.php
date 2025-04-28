<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Connect to the central database
$pdo_central = getDatabaseConnection('central');

// Check if an admin ID is provided in the URL
if (isset($_GET['id'])) {
    $admin_id = $_GET['id'];

    // Fetch admin details from the central database
    $stmt = $pdo_central->prepare("SELECT
            a.admin_id AS id,
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
        WHERE
            a.admin_id = :admin_id");
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // If admin not found, redirect back to the list
    if (!$admin) {
        header('Location: staff.php');
        exit;
    }

    // Handle form submission for editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $status = $_POST['status'];
        // Update the admin details in the central database
        $updateStmt = $pdo_central->prepare("UPDATE admin SET
            first_name = :first_name,
            last_name = :last_name,
            status = :status
            WHERE admin_id = :admin_id");
        $updateStmt->bindParam(':first_name', $first_name);
        $updateStmt->bindParam(':last_name', $last_name);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':admin_id', $admin_id);
        $updateStmt->execute();

        // Redirect to the list after update
        header('Location: staff.php');
        exit;
    }
} else {
    // If no ID is provided, redirect back to the list
    header('Location: staff.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
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
                    <h1>Edit Admin</h1>
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
            <!-- Edit Admin Form -->
            <div class="form-container" >
                <form method="POST" action="edit_admin.php?id=<?php echo $adminId; ?>">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
              </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['uni_email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($admin['status']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($admin['department_name']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($admin['role_name']); ?>" disabled>
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
