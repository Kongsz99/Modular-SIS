<?php
// Include database connection
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the form data
    // $scholarship_id = $_POST['scholarship-id'];
    $scholarship_name = $_POST['scholarship-name'];
    $scholarship_type = $_POST['scholarship-type'];
    $amount = $_POST['amount'];

    $pdo = getDatabaseConnection('central'); // Adjust dynamically based on the department

    // Prepare SQL insert query
    $query = "INSERT INTO scholarship (scholarship_name, scholarship_type, amount) 
              VALUES (:scholarship_name, :scholarship_type, :amount)";
    
    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind parameters
    // $stmt->bindParam(':scholarship_id', var: $scholarship_id);
    $stmt->bindParam(':scholarship_name', $scholarship_name);
    $stmt->bindParam(':scholarship_type', $scholarship_type);
    $stmt->bindParam(':amount', $amount);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Scholarship added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding scholarship!');</script>";
    }
     // Redirect back to the form
     header("Location: add_scholarship.php");
     exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Scholarship</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="add-scholarship-page">
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
                    <h1>Add Scholarship</h1>
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
               <a href="scholarship.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Scholarship List
               </a>

                <!-- Add New Scholarship Form -->
                <div class="form-container">
                    <form method="POST" action="">
                        <!-- Scholarship Details -->
                        <fieldset>
                            <legend>Scholarship Details</legend>
                            <!-- <div class="form-group">
                                <label for="scholarship-id">Scholarship ID</label>
                                <input type="text" id="scholarship-id" name="scholarship-id" placeholder="Enter scholarship ID" required>
                            </div> -->
                            <div class="form-group">
                                <label for="scholarship-name">Scholarship Name</label>
                                <input type="text" id="scholarship-name" name="scholarship-name" placeholder="Enter scholarship name" required>
                            </div>
                            <div class="form-group">
                                <label for="scholarship-type">Scholarship Type</label>
                                <select id="scholarship-type" name="scholarship-type" required>
                                    <option value="">Select Scholarship Type</option>
                                    <option value="merit based">Merit-based</option>
                                    <option value="need based">Need-based</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="amount">Scholarship Amount</label>
                                <input type="number" id="amount" name="amount" placeholder="Enter scholarship amount" required>
                            </div>
                        </fieldset>

                        <!-- Submit Button (Centered) -->
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Add Scholarship</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
