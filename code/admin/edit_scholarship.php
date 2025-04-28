<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Connect to the central database
$pdo = getDatabaseConnection('central');

// Fetch programme details based on programme_id
if (isset($_GET['id'])) {
    $scholarship_id = $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT scholarship_id, scholarship_name, scholarship_type, amount
        FROM scholarship
        WHERE scholarship_id = :scholarship_id
    ");
    $stmt->execute([':scholarship_id' => $scholarship_id]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scholarship) {
        die("scholarship not found.");
    }
} else {
    die("scholarship ID not provided.");
}

// Fetch departments for the dropdown
$stmt_departments = $pdo->prepare("SELECT department_id, department_name FROM departments");
$stmt_departments->execute();
$departments = $stmt_departments->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $programme_name = trim($_POST['scholarship_name']);
    $scholarship_type = trim($_POST['scholarship_type']);
    $amount = trim($_POST['amount']);
   
    // Update programme in the database
    try {
        $stmt = $pdo->prepare("
            UPDATE scholarship
            SET scholarship_name = :scholarship_name,
                scholarship_type = :scholarship_type,
                amount = :amount
        ");
        $stmt->execute([
            ':scholarship_name' => $scholarship_name,
            ':scholarship_type' => $scholarship_type,
            ':amount' => $amount
        ]);

        // Set a success message
        $_SESSION['message'] = "Scholarship updated successfully!";
    } catch (PDOException $e) {
        // Set an error message
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Redirect back to the programme list
    header("Location: scholarship.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scholarships</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="edit-programme-page">
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Edit Scholarship</h1>
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
                <a href="scholarship.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Scholarship List
                </a>

                <!-- Edit Programme Form -->
                <div class="form-container">
                    <!-- Display success/error messages -->
                    <?php
                    if (isset($_SESSION['message'])) {
                        echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
                        unset($_SESSION['message']);
                    }
                    if (isset($_SESSION['error'])) {
                        echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
                        unset($_SESSION['error']);
                    }
                    ?>

                    <form method="POST">
                        <!-- Programme Details -->
                        
                            <div class="form-group">
                                <label for="scholarship_id">Scholarship ID</label> 
                                <input type="text" id="scholarship_id" name="scholarship_id" value="<?php echo htmlspecialchars($scholarship['scholarship_id']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="scholarship_name">Scholarship Name</label>
                                <input type="text" id="scholarship_name" name="scholarship_name" value="<?php echo htmlspecialchars($scholarship['scholarship_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="scholarship_type">Scholarship Type</label>
                                <select id="scholarship_type" name="scholarship_type" required>
                                    <option value="">Select Scholarship Type</option>
                                    <option value="merit based"<?php echo $scholarship['scholarship_type'] === 'merit based' ? 'selected' : ''; ?>>Merit-based</option>
                                    <option value="need based"><?php echo $scholarship['scholarship_type'] === 'need based' ? 'selected' : ''; ?>Need-based</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount (£)</label>
                                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($scholarship['amount']); ?>" required>
                            </div>

                        <!-- Submit Button (Centered) -->
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>