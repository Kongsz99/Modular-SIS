<?php
// session_start();
require_once '../db_connect.php'; // Include the database connection script
require_once '../auth.php';

// check_role(required_role: GLOBAL_ADMIN);
// Ensure the user is an admin
// check_role(required_role: ROLE_ADMIN); // Assuming ROLE_ADMIN is defined in constants.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $personal_email = trim($_POST['personal_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $role_id = (int)$_POST["role_id"];
    $department_id = $_POST['department_id'] ?? null; // Optional department ID
    $start_date = $_POST['start_date'] ?? '';
    $status = 'active'; // Default status

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($role_id)) {
        die("❌ First name, last name, and role ID are required.");
    }

    // Generate password: lastname + dob (YYYYMMDD)
    $password = strtolower($last_name) . date('Ymd', strtotime($dob));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Connect to the share_database
    $share_db_conn = getDatabaseConnection('central');

    // Generate address_id from share_database sequences
    $address_id = get_formatted_id($share_db_conn, 'address_id_seq', 'A', 8);

    // Generate username based on role
    if ($role_id == 1 || $role_id == 2) {
        // For admins: username = "ad" + admin_id
        $admin_id = get_nextval($share_db_conn, 'admin_id_seq');
        $username = 'ad' . $admin_id;
    } else {
        // For staff: username = "st" + staff_id
        $staff_id = get_nextval($share_db_conn, 'staff_id_seq');
        $username = 'sf' . $staff_id;
    }

    // Generate a university email
    $uni_email = $username . '@university.edu';

    // Insert the user into the share_database (user_id is auto-incremented)
    $query = "INSERT INTO users (username, password_hash, role_id)
              VALUES (?, ?, ?)";
    $stmt = $share_db_conn->prepare($query);
    $stmt->execute([$username, $password_hash, $role_id]);

    // Get the auto-incremented user_id
    $user_id = $share_db_conn->lastInsertId();

    // Insert into user_department in share_database if department_id is provided
    if ($department_id) {
        $query = "INSERT INTO user_department (user_id, department_id)
                  VALUES (?, ?)";
        $stmt = $share_db_conn->prepare($query);
        $stmt->execute([$user_id, $department_id]);
    }

    // If role_id is 1 (Global Admin) or 2 (Department Admin), proceed with admin table in share database
    if ($role_id == 1 || $role_id == 2) {
        // Insert the address into the share_database
        $query = "INSERT INTO address (address_id, address, postcode, city, state, country)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $share_db_conn->prepare($query);
        $stmt->execute([$address_id, $address, $postcode, $city, $state, $country]);

        // Insert the admin into the share_database
        $query = "INSERT INTO admin (
            admin_id, user_id, first_name, last_name, gender, date_of_birth, personal_email,
            uni_email, phone, address_id, start_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $share_db_conn->prepare($query);
        $stmt->execute([
            $admin_id, $user_id, $first_name, $last_name, $gender, $dob, $personal_email,
            $uni_email, $phone, $address_id, $start_date, $status
        ]);
    } 
    // If role_id is 3 (Staff), proceed with staff table in department-specific database
    elseif ($role_id == 3) {
        // Validate department_id for staff
        if (empty($department_id)) {
            die("❌ Department ID is required for staff.");
        }

        // Determine the target database based on the department_id
        $target_db = null;
        switch ($department_id) {
            case 'CS':
                $target_db = getDatabaseConnection('cs'); // Connection to the CS database
                break;
            case 'BM':
                $target_db = getDatabaseConnection('bm'); // Connection to the BM database
                break;
            default:
                die("❌ Invalid department ID: $department_id");
        }

        // Insert the address into the target database
        $query = "INSERT INTO address (address_id, address, postcode, city, state, country)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $target_db->prepare($query);
        $stmt->execute([$address_id, $address, $postcode, $city, $state, $country]);

        // Insert the staff into the target database
        $query = "INSERT INTO staff (
            staff_id, user_id, first_name, last_name, gender, date_of_birth, personal_email,
            uni_email, phone, address_id, start_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $target_db->prepare($query);
        $stmt->execute([
            $staff_id, $user_id, $first_name, $last_name, $gender, $dob, $personal_email,
            $uni_email, $phone, $address_id, $start_date, $status
        ]);
    } else {
        die("❌ Invalid role ID: $role_id");
    }

    echo "✅ Staff added successfully!";
} else {
    // die("❌ Invalid request method.");
}

/**
 * Gets the next value from a sequence in the share_database.
 */
function get_nextval($share_db_conn, $sequence_name) {
    $query = "SELECT nextval(?) AS nextval";
    $stmt = $share_db_conn->prepare($query);
    $stmt->execute([$sequence_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['nextval'];
}

/**
 * Generates a formatted ID using a sequence in the share_database.
 */
function get_formatted_id($share_db_conn, $sequence_name, $prefix, $padding_length) {
    $nextval = get_nextval($share_db_conn, $sequence_name);
    return $prefix . str_pad($nextval, $padding_length, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Staff</title>
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

        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Add Staff</h1>
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
            <a href="staff.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Staff List
            </a>
            <!-- Add New Staff Form -->
            <div class="form-container">
                <form method="POST">
                    <!-- Two-Column Layout -->
                    <div class="two-column-layout">
                        <!-- First Column: Personal Details, Contact Information, Address -->
                        <div class="column">
                            <!-- Personal Details -->
                            <fieldset>
                                <legend>Personal Details</legend>
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter last name" required>
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                                </div>
                            </fieldset>

                            <!-- Contact Information -->
                            <fieldset>
                                <legend>Contact Information</legend>
                                <div class="form-group">
                                    <label for="personal_email">Personal Email</label>
                                    <input type="email" id="personal_email" name="personal_email" placeholder="Enter email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required>
                                </div>
                            </fieldset>

                            <!-- Address -->
                            <fieldset>
                                <legend>Address</legend>
                                <div class="form-group">
                                    <label for="address">Address Line</label>
                                    <input type="text" id="address" name="address" placeholder="Enter address line" required>
                                </div>
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" placeholder="Enter city" required>
                                </div>
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input type="text" id="state" name="state" placeholder="Enter state" required>
                                </div>
                                <div class="form-group">
                                    <label for="postcode">Post Code</label>
                                    <input type="text" id="postcode" name="postcode" placeholder="Enter post code" required>
                                </div>
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country" placeholder="Enter country" required>
                                </div>
                            </fieldset>
                        </div>

                        <!-- Second Column: Role, Department, Employment Details -->
                        <div class="column">
                            <!-- Role and Department -->
                            <fieldset>
                                <legend>Role and Department</legend>
                                <div class="form-group">
                                    <label for="role_id">Role</label>
                                    <select id="role_id" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <option value="1">Administrator</option>
                                        <option value="2">Department Admin</option>
                                        <option value="3">Lecturer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="department_id">Department</label>
                                    <select id="department_id" name="department_id">
                                        <option value="">Select Department</option>
                                        <option value="CS">Computer Science</option>
                                        <option value="BM">Business</option>
                                        </select>
                                    </select>
                                </div>
                            </fieldset>

                            <!-- Employment Details -->
                            <fieldset>
                                <legend>Employment Details</legend>
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="status">Employment Status</label>
                                    <select id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on-leave">On Leave</option>
                                    </select>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    <!-- Submit Button (Centered) -->
                    <div class="form-group submit-button">
                        <button type="submit" class="btn" onclick="return alert('Staff Added Successfully!')">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>



