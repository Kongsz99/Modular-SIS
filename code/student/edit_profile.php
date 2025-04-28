<?php

require_once '../db_connect.php';
require_once '../auths.php';

check_role(required_role: STUDENT);

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Arrays to store combined student and address data
$combinedStudents = [];
$combinedAddresses = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details
        $sql = "
            SELECT s.phone, s.personal_email, a.address, a.city, a.state, a.postcode, a.country
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN address a ON s.address_id = a.address_id
            WHERE u.user_id = :user_id;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $student['department_id'] = $departmentId;
            $combinedStudents[] = $student;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $postcode = $_POST['postcode'];
    $country = $_POST['country'];
    $personal_email = $_POST['personal_email'];

    if (empty($phone) || empty($address) || empty($personal_email)) {
        die("Error: All fields are required.");
    }

    // Loop through each department ID to update the student's profile
    foreach ($departmentIds as $departmentId) {
        try {
            $pdo = getDatabaseConnection(strtolower($departmentId));
            $pdo->beginTransaction();

            // Update students table
            $sql1 = "
                UPDATE students s
                SET phone = :phone,
                    personal_email = :personal_email
                WHERE s.user_id = :user_id;
            ";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                'phone' => $phone,
                'personal_email' => $personal_email,
                'user_id' => $_SESSION['user_id']
            ]);

            // Update address table
            $sql2 = "
                UPDATE address
                SET address = :address,
                    city = :city,
                    state = :state,
                    postcode = :postcode,
                    country = :country
                WHERE address_id = (
                    SELECT address_id FROM students s WHERE s.user_id = :user_id);
            ";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'postcode' => $postcode,
                'country' => $country,
                'user_id' => $_SESSION['user_id']
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error updating profile: " . $e->getMessage());
        }
    }

    // Redirect to the same page after successful update
    header("Location: edit_profile.php");
    exit; // Ensure no further code is executed after the redirect
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<style>
   
</style>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li><a href="disability_request.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Edit Profile</h1>
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
                <a href="profile.php" class="back-button" style="text-decoration: none;">
                   <i class="fas fa-arrow-left"></i> Back to My Profile
               </a>
                <!-- Edit Profile Form -->
                <div class="form-container">
                    <h2>Update Your Contact Number, Address, Email</h2>
                    <form id="edit-profile-form" method="POST" action="edit_profile.php">
                    <!-- Contact Number -->
                        <div class="form-group">
                            <label for="phone">Contact Number:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($combinedStudents[0]['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address Line:</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($combinedStudents[0]['address']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($combinedStudents[0]['city']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($combinedStudents[0]['state']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="postcode">Post Code:</label>
                            <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($combinedStudents[0]['postcode']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($combinedStudents[0]['country']); ?>" required>
                        </div>
                        <!-- Personal Email -->
                        <div class="form-group">
                            <label for="personal-email">Personal Email:</label>
                            <input type="email" id="personal-email" name="personal_email" value="<?php echo htmlspecialchars($combinedStudents[0]['personal_email']); ?>" required>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>