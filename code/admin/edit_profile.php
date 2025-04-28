<?php
// session_start(); // Start the session
// if (!isset($_SESSION['user_id'])) {
//     die("Unauthorized access. Please log in.");
// }

require_once '../db_connect.php';
require_once '../auth.php';

// Fetch admin details
try {
    $pdo = getDatabaseConnection('central');
    $sql = "
        SELECT a.phone, a.personal_email, ad.address,
               ad.city, ad.state, ad.postcode, ad.country
        FROM admin a
        JOIN users u ON a.user_id = u.user_id
        JOIN address ad ON a.address_id = ad.address_id
        WHERE u.user_id = :user_id;
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
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

    try {
        $pdo->beginTransaction();

        // Update users table
        $sql1 = "
            UPDATE admin a
            SET phone = :phone,
                personal_email = :personal_email
            WHERE a.user_id = :user_id;
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
                SELECT address_id FROM admin a WHERE a.user_id = :user_id);
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
        echo "Profile updated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error updating profile: " . $e->getMessage());
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
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
               <a href="profile.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to My Profile
               </a>
                <!-- Edit Profile Form -->
                <div class="form-container">
                    <h2>Update Your Contact Number, Address, Email</h2>
                    <form id="edit-profile-form" method="POST" action="edit_profile.php">
                    <!-- Contact Number -->
                        <div class="form-group">
                            <label for="phone">Contact Number:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address Line:</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($admin['address']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($admin['city']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($admin['state']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="postcode">Post Code:</label>
                            <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($admin['postcode']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($admin['country']); ?>" required>
                        </div>
                        <!-- Personal Email -->
                        <div class="form-group">
                            <label for="personal-email">Personal Email:</label>
                            <input type="email" id="personal-email" name="personal_email" value="<?php echo htmlspecialchars($admin['personal_email']); ?>" required>
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