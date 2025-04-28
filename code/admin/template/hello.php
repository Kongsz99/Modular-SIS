<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Admin Dashboard"; ?></title>
    <link rel="stylesheet" href="script/styles.css">
    <script src="sidebar.js"></script>
    <script src="script/nav.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
<!-- Sidebar -->
<div class="dashboard">
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-user-shield"></i>
            <span>Admin Panel</span>
        </div>
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1><?php echo isset($pageTitle) ? $pageTitle : "Dashboard"; ?></h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin</span>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content">
                <div class="cards">
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3>Total Students</h3>
                            <p>1,234</p>
                        </div>
                    </div>
                    <!-- More Cards here -->
                </div>

                <!-- Recent Activity Table -->
                <div class="recent-activity">
                    <h2>Recent Activity</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>101</td>
                                <td>John Doe</td>
                                <td>Computer Science</td>
                                <td>Enrolled</td>
                            </tr>
                            <!-- More rows here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php error_reporting(E_ALL);
ini_set('display_errors', 1);
 ?>

</body>
</html>
