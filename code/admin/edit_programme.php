<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Fetch departments for the filter dropdown
$pdo_central = getDatabaseConnection('central'); // Connect to the central database
$stmt = $pdo_central->prepare("SELECT department_id, department_name FROM departments");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $programme_id = trim($_POST['programme_id']);
    $programme_name = trim($_POST['programme_name']);
    $department_id = trim($_POST['department_id']);
    $duration_years = trim($_POST['duration']);
    $local_fees = trim($_POST['local_fees']);
    $international_fees = trim($_POST['international_fees']);
    $description = trim($_POST['description']);

    // Determine which database to use based on the department
    if ($department_id == 'CS') {
        $pdo = getDatabaseConnection('cs');
    } elseif ($department_id == 'BM') {
        $pdo = getDatabaseConnection('bm');
    } else {
        die("Invalid department selected.");
    }

    // Update the programme in the database
    try {
        $stmt = $pdo->prepare("
            UPDATE programme 
            SET programme_name = :programme_name, 
                department_id = :department_id, 
                duration_years = :duration_years, 
                local_fees = :local_fees, 
                international_fees = :international_fees, 
                description = :description 
            WHERE programme_id = :programme_id
        ");
        $stmt->execute([
            ':programme_id' => $programme_id,
            ':programme_name' => $programme_name,
            ':department_id' => $department_id,
            ':duration_years' => $duration_years,
            ':local_fees' => $local_fees,
            ':international_fees' => $international_fees,
            ':description' => $description
        ]);

        // Set a success message
        $_SESSION['message'] = "Programme updated successfully!";
    } catch (PDOException $e) {
        // Set an error message
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    // Redirect back to the form
    header("Location: edit_programme.php?programme_id=" . $programme_id);
    exit();
}

// Fetch the programme details if programme_id is provided in the URL
if (isset($_GET['programme_id'])) {
    $programme_id = $_GET['programme_id'];

    // First, determine which database to use by checking the department
    $pdo_central = getDatabaseConnection('central');
    $stmt = $pdo_central->prepare("SELECT department_id FROM departments WHERE department_id IN ('CS', 'BM')");
    $stmt->execute();
    $valid_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($valid_departments)) {
        die("No valid departments found.");
    }

    // Loop through valid departments to find the programme
    $programme = null;
    foreach ($valid_departments as $department_id) {
        if ($department_id == 'CS') {
            $pdo = getDatabaseConnection('cs');
        } elseif ($department_id == 'BM') {
            $pdo = getDatabaseConnection('bm');
        } else {
            continue;
        }

        // Fetch the programme details
        $stmt = $pdo->prepare("SELECT * FROM programme WHERE programme_id = :programme_id");
        $stmt->execute([':programme_id' => $programme_id]);
        $programme = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($programme) {
            break; // Exit the loop if the programme is found
        }
    }

    if (!$programme) {
        die("Programme not found in any department database.");
    }
} else {
    die("Programme ID not provided in the URL.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Programme</title>
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
                    <h1>Edit Programme</h1>
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
                <!-- Back Button -->
               <a href="programme.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Programme List
               </a>
                <div class="form-container">
                <form method="POST">
                <!-- Programme Details -->
                            <div class="form-group">
                                <label for="programme-id">Programme ID</label>
                                <input type="text" id="programme-id" name="programme_id" value="<?php echo htmlspecialchars($programme['programme_id']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="programme-name">Programme Name</label>
                                <input type="text" id="programme-name" name="programme_name" value="<?php echo htmlspecialchars($programme['programme_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department_id">
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department['department_id']); ?>" <?php echo ($department['department_id'] == $programme['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration (Years)</label>
                                <input type="number" id="duration" name="duration" value="<?php echo htmlspecialchars($programme['duration_years']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="local_fees">Local fees Per Year</label>
                                <input type="number" id="local_fees" name="local_fees" value="<?php echo htmlspecialchars($programme['local_fees']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="international_fees">International Fees Per Year</label>
                                <input type="number" id="international_fees" name="international_fees" value="<?php echo htmlspecialchars($programme['international_fees']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" placeholder="Enter programme description" rows="6" required><?php echo htmlspecialchars($programme['description']); ?></textarea>
                            </div>

                        <!-- Submit Button (Centered) -->
                        <div class="form-group submit-button">
                            <button type="submit" class="btn" onclick="return alert('Programme Updated Successfully!')">Update Programme</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</body>
</html>