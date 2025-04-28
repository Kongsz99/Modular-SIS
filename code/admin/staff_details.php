<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Define departments
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
];

// Get the staff ID and department ID from the URL
$staffId = $_GET['id'] ?? '';
$selectedDepartment = $_GET['department_id'] ?? '';

if (empty($staffId)) {
    die("Staff ID is required.");
}

// Initialize staff details
$staffDetails = null;
$departmentConnections = [];
$modules = [];

// First check if this is an admin (check central database)
$pdoCentral = getDatabaseConnection('central');
$adminQuery = "
    SELECT 
        a.*, 
        u.username, 
        CONCAT(a.first_name, ' ', a.last_name) AS full_name,
        d.department_name,
        r.role_name,
        CONCAT(ad.address, ', ', ad.city, ', ', ad.state, ', ', ad.postcode, ', ', ad.country) AS full_address
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
    LEFT JOIN
        address ad ON a.address_id = ad.address_id
    WHERE 
        a.admin_id = :id
";

$adminStmt = $pdoCentral->prepare($adminQuery);
$adminStmt->execute(['id' => $staffId]);
$adminResult = $adminStmt->fetch(PDO::FETCH_ASSOC);

if ($adminResult) {
    // This is an admin - use central database data
    $staffDetails = $adminResult;
    $staffDetails['database'] = 'central';
    
    // Get department connections for admin
    $deptQuery = "
        SELECT d.department_name 
        FROM user_department ud
        JOIN departments d ON ud.department_id = d.department_id
        JOIN users u ON ud.user_id = u.user_id
        JOIN admin a ON u.user_id = a.user_id
        WHERE a.admin_id = :id
    ";
    $deptStmt = $pdoCentral->prepare($deptQuery);
    $deptStmt->execute(['id' => $staffId]);
    $departmentConnections = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Not an admin - check department databases
    if ($selectedDepartment) {
        // Connect to the selected department's database
        $pdo = getDatabaseConnection(strtolower($selectedDepartment));
        
        $query = "
            SELECT 
                s.*, 
                u.username, 
                CONCAT(s.first_name, ' ', s.last_name) AS full_name,
                d.department_name,
                r.role_name,
                CONCAT(ad.address, ', ', ad.city, ', ', ad.state, ', ', ad.postcode, ', ', ad.country) AS full_address
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
            LEFT JOIN
                address ad ON s.address_id = ad.address_id
            WHERE 
                s.staff_id = :id
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $staffId]);
        $staffDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staffDetails) {
            $staffDetails['database'] = strtolower($selectedDepartment);
            
            // Get department connections (only one since staff belongs to one department)
            $deptQuery = "
                SELECT d.department_name 
                FROM user_department ud
                JOIN departments d ON ud.department_id = d.department_id
                JOIN users u ON ud.user_id = u.user_id
                JOIN staff s ON u.user_id = s.user_id
                WHERE s.staff_id = :id
            ";
            $deptStmt = $pdo->prepare($deptQuery);
            $deptStmt->execute(['id' => $staffId]);
            $departmentConnections = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
        
            // Get modules taught (for lecturers)
            if ($staffDetails['role_name'] === 'lecturer') {
                $modQuery = "
                    SELECT m.module_name, m.module_id
                    FROM assigned_lecturers ml
                    JOIN modules m ON ml.module_id = m.module_id
                    JOIN staff s ON ml.staff_id = s.staff_id
                    WHERE s.staff_id = :id
                ";
                $modStmt = $pdo->prepare($modQuery);
                $modStmt->execute(['id' => $staffId]);
                $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } else {
        // Search all departments if no specific department is selected
        foreach ($departments as $department) {
            $pdo = getDatabaseConnection(strtolower($department['department_id']));
            
            $query = "
                SELECT 
                    s.*, 
                    u.username, 
                    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
                    d.department_name,
                    r.role_name,
                    CONCAT(ad.address, ', ', ad.city, ', ', ad.state, ', ', ad.postcode, ', ', ad.country) AS full_address
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
                LEFT JOIN
                    address ad ON s.address_id = ad.address_id
                WHERE 
                    s.staff_id = :id
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $staffId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $staffDetails = $result;
                $staffDetails['database'] = strtolower($department['department_id']);
                
                // Get department connections
                $deptQuery = "
                    SELECT d.department_name 
                    FROM user_department ud
                    JOIN departments d ON ud.department_id = d.department_id
                    JOIN users u ON ud.user_id = u.user_id
                    JOIN staff s ON u.user_id = s.user_id
                    WHERE s.staff_id = :id
                ";
                $deptStmt = $pdo->prepare($deptQuery);
                $deptStmt->execute(['id' => $staffId]);
                $departmentConnections = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get modules taught (for lecturers)
                if ($staffDetails['role_name'] === 'lecturer') {
                    $modQuery = "
                        SELECT m.module_name, m.module_id
                        FROM assigned_lecturers ml
                        JOIN modules m ON ml.module_id = m.module_id
                        JOIN staff s ON ml.staff_id = s.staff_id
                        WHERE s.staff_id = :id
                    ";
                    $modStmt = $pdo->prepare($modQuery);
                    $modStmt->execute(['id' => $staffId]);
                    $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                break; // Stop searching once staff is found
            }
        }
    }
}

if (!$staffDetails) {
    die("No staff found with ID: " . htmlspecialchars($staffId));
}

// Determine the type for display and edit links
$type = ($staffDetails['database'] === 'central') ? 'admin' : 'staff';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Details</title>
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
                    <h1>Staff Details</h1>
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
                <!-- Back Button -->
                <a href="staff.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Staff List
                </a>

            <!-- Staff Details Container -->
            <div class="person-details-container">
                <div class="details-section">
                    <h2>Personal Information</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Full Name:</label>
                            <span><?= htmlspecialchars($staffDetails['full_name']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Staff ID:</label>
                            <span><?= htmlspecialchars($staffId) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Gender:</label>
                            <span><?= htmlspecialchars($staffDetails['gender']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Username:</label>
                            <span><?= htmlspecialchars($staffDetails['username']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Date of Birth:</label>
                            <span><?= htmlspecialchars($staffDetails['date_of_birth']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Contact Number:</label>
                            <span><?= htmlspecialchars($staffDetails['phone']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Personal Email:</label>
                            <span><?= htmlspecialchars($staffDetails['personal_email']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Address:</label>
                            <span><?= htmlspecialchars($staffDetails['full_address']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>University Email:</label>
                            <span><?= htmlspecialchars($staffDetails['uni_email']) ?></span>
                        </div>
                        <!-- Add other personal details fields as needed -->
                    </div>
                </div>

                <div class="details-section">
                    <h2>Employment Details</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Role:</label>
                            <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $staffDetails['role_name']))) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Department:</label>
                            <span><?= htmlspecialchars($staffDetails['department_name']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Start Date:</label>
                            <span><?= htmlspecialchars($staffDetails['start_date']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge <?= strtolower($staffDetails['status']) ?>">
                                <?= htmlspecialchars(ucfirst($staffDetails['status'])) ?>
                            </span>
                        </div>
                        <!-- Add other employment details fields as needed -->
                    </div>
                </div>

                <?php if ($staffDetails['role_name'] === 'lecturer'): ?>
                    <?php if (!empty($programmes)): ?>
                    <div class="details-section">
                        <h2>Programmes Teaching</h2>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Programme Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programmes as $programme): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($programme['programme_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($modules)): ?>
                    <div class="details-section">
                        <h2>Modules Teaching</h2>
                        <!-- <div class="table-container"> -->
                            <table>
                                <thead>
                                    <tr>
                                        <th>Module ID</th>
                                        <th>Module Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $module): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($module['module_id']) ?></td>
                                            <td><?= htmlspecialchars($module['module_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>