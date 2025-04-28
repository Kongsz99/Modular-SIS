<?php
require_once '../db_connect.php'; // Include the database connection script
require_once '../auth.php';
// // Ensure the user is an admin

check_role(required_role: GLOBAL_ADMIN);
// Fetch departments from the share database
$share_db_conn = getDatabaseConnection(dbKey: 'central');
$departments = [];

try {
    // Fetch departments
    $query = "SELECT department_id, department_name FROM departments";
    $stmt = $share_db_conn->query($query);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Error fetching departments: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $personal_email = trim($_POST['personal_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $student_type = trim($_POST['student_type'] ?? '');
    $education_level = trim($_POST['education_level'] ?? '');
    $institution_name = trim($_POST['institution_name'] ?? '');
    $department1 = $_POST['department_1'] ?? '';
    $programme1 = $_POST['p1'] ?? '';
    $p1_start_date = $_POST['p1_start_date'] ?? '';
    $p1_current_year = $_POST['p1_current_year'] ?? '';
    $p1_academic_year = $_POST['p1_academic_year'] ?? '';

    $department2 = $_POST['department_2'] ?? null;
    $programme2 = $_POST['p2'] ?? null;
    $p2_start_date = $_POST['p2_start_date'] ?? null;
    $p2_current_year = $_POST['p2_current_year'] ?? null;
    $p2_academic_year = $_POST['p2_academic_year'] ?? null;
    $role_id = 4; // Student role
    $status = 'active';

    // Validate required fields
    if (empty($department1) || empty($programme1)) {
        die("❌ Department 1 and Programme 1 are required.");
    }

    // Ensure department1 is valid
    if (!in_array($department1, array_column($departments, 'department_id'))) {
        die("❌ Invalid department ID: $department1");
    }

    // Generate password: lastname + dob (YYYYMMDD)
    $password = strtolower($last_name) . date('Ymd', strtotime($date_of_birth));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generate student_id and address_id from share_database sequences
    $student_id = get_nextval($share_db_conn, 'student_id_seq');
    $address_id = get_formatted_id($share_db_conn, 'address_id_seq', 'A', 8);
    // Generate username using the last 6 digits of student_id
    $formatted_id = substr($student_id, -6); // Extract the last 6 digits
    $username = 'st' . str_pad($formatted_id, 6, "0", STR_PAD_LEFT);

    // Generate a university email
    $uni_email = $username . '@student.university.edu';

    // Insert the user into the share_database (user_id is auto-incremented)
    $query = "INSERT INTO users (username, password_hash, role_id)
              VALUES (?, ?, ?)";
    $stmt = $share_db_conn->prepare($query);
    $stmt->execute([$username, $password_hash, $role_id]);

    // Get the auto-incremented user_id
    $user_id = $share_db_conn->lastInsertId();
    
    // Insert the user_department records into the share_database
    $departments_selected = [$department1];
    if ($department2) {
        $departments_selected[] = $department2;
    }

    foreach ($departments_selected as $department_id) {
        if (empty($department_id)) {
            continue; // Skip empty department IDs
        }

        $query = "INSERT INTO user_department (user_id, department_id)
                  VALUES (?, ?)";
        $stmt = $share_db_conn->prepare($query);
        $stmt->execute([$user_id, $department_id]);
    }

    // Insert the student into the appropriate department database(s)
    $programmes_selected = [
        ['department' => $department1, 'programme' => $programme1, 'start_date' => $p1_start_date, 'academic_year' => $p1_academic_year, 'current_year' => $p1_current_year],
    ];

    if ($department2) {
        $programmes_selected[] = ['department' => $department2, 'programme' => $programme2, 'start_date' => $p2_start_date, 'academic_year' => $p2_academic_year,'current_year' => $p2_current_year];
    }

    foreach ($programmes_selected as $programme) {
        $department_id = $programme['department'];
        $target_db = null;

        // Determine the target database based on the department_id
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

        // Insert the student into the target database
        $query = "INSERT INTO students (
            student_id, user_id, first_name, last_name, gender, date_of_birth, personal_email, uni_email,
            phone, address_id, nationality, student_type, education_level, institution_name, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $target_db->prepare($query);
        $stmt->execute([
            $student_id, $user_id, $first_name, $last_name, $gender, $date_of_birth, $personal_email, $uni_email,
            $phone, $address_id, $nationality, $student_type, $education_level, $institution_name, $status
        ]);

        // Insert the student's programme enrolment into the target database
        $query = "INSERT INTO programme_enrolment (
            student_id, programme_id, programme_start_date, academic_year, current_year
                 ) VALUES (?, ?, ?, ?, ?)";

        $stmt = $target_db->prepare($query);
        $stmt->execute([
            $student_id, $programme['programme'], $programme['start_date'], $programme['academic_year'], $programme['current_year']
        ]);
    }

    echo "✅ Student added successfully to all relevant departments!";
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
    <title>Add student</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="template/sidebar.js"></script>
</head>
<script>
    // Function to fetch programmes based on the selected department
    function fetchProgrammes(departmentSelectId, programmeSelectId) {
        const departmentSelect = document.getElementById(departmentSelectId);
        const programmeSelect = document.getElementById(programmeSelectId);

        // Get the selected department ID
        const selectedDepartmentId = departmentSelect.value;

        // Clear the programme dropdown
        programmeSelect.innerHTML = '<option value="">Select Programme</option>';

        // If no department is selected, return
        if (!selectedDepartmentId) {
            return;
        }

        // Make an AJAX request to fetch programmes
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_programmes.php?department_id=${selectedDepartmentId}`, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                const programmes = JSON.parse(xhr.responseText);

                // Populate the programme dropdown
                programmes.forEach(programme => {
                    const option = document.createElement('option');
                    option.value = programme.programme_id;
                    option.textContent = programme.programme_name;
                    programmeSelect.appendChild(option);
                });
            } else {
                console.error('Error fetching programmes:', xhr.statusText);
            }
        };
        xhr.onerror = function () {
            console.error('Error fetching programmes:', xhr.statusText);
        };
        xhr.send();
    }
</script>
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
                <li class="active"><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="semester.php"><i class="fas fa-calendar"></i><span>Semesters</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="disability_requests.php"><i class="fas fa-wheelchair"></i> Disability Requests</a></li>
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
                    <h1>Add Student</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>                    </div>
                
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
                <a href="student.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Student List
                </a>

                <!-- Add Student Form -->
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
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" placeholder="Enter first name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" placeholder="Enter last name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender" required>
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
                                    <div class="form-group">
                                        <label for="nationality">Nationality</label>
                                        <select id="nationality" name="nationality" required>
                                            <option value="">Select Nationality</option>
                                            <option value="Malaysian">Malaysian</option>
                                            <option value="Singaporean">Singaporean</option>
                                            <option value="British">British</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="student_type">Student Type</label>
                                        <select id="student_type" name="student_type" required>
                                            <option value="local">Local</option>
                                            <option value="international">International</option>
                                        </select>
                                    </div>
                                </fieldset>

                                <!-- Contact Information -->
                                <fieldset>
                                    <legend>Contact Information</legend>
                                    <div class="form-group">
                                        <label for="personal_email">Personal Email</label>
                                        <input type="email" id="personal_email" name="personal_email" placeholder="Enter personal email" required>
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

                            <!-- Second Column: Education History, Programme Selection -->
                            <div class="column">
                                <!-- Education History -->
                                <fieldset>
                                    <legend>Education History</legend>
                                    <div class="form-group">
                                        <label for="education_level">Education Level</label>
                                            <input type="text" id="education_level" name="education_level" placeholder="Enter education_level" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="institution_name">Institution Name</label>
                                        <input type="text" id="institution_name" name="institution_name" placeholder="Enter institution name" required>
                                    </div>
                                </fieldset>

                                <!-- Programme Selection -->
                                <fieldset>
                                    <legend>Programme Selection</legend>
                                    <!-- Programme 1 (Required) -->
                                    <div class="form-group">
                                        <label for="department_1">Department</label>
                                        <select id="department_1" name="department_1" onchange="fetchProgrammes('department_1', 'p1')" required>
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
                                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Programme 1 -->
                                    <div class="form-group">
                                        <label for="p1">Programme 1</label>
                                        <select id="p1" name="p1" required>
                                            <option value="">Select Programme</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="p1_start_date">Start Date</label>
                                        <input type="date" id="p1_start_date" name="p1_start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="p1_academic_year">Academic Year</label>
                                        <select id="p1_academic_year" name="p1_academic_year" required>
                                            <option value="">Select Academic Year</option>
                                            <option value="2024/5">2024/5</option>
                                            <option value="2025/6">2025/6</option>
                                            <option value="2026/7">2026/7</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="p1_current_year">Current Year</label>
                                        <input type="number" id="p1_current_year" name="p1_current_year" min="1" max="4" required>
                                    </div>

                                    <!-- Programme 2 (Optional) -->
                                     <!-- Department 2 (Optional) -->
                                    <div class="form-group">
                                        <label for="department_2">Department (Optional)</label>
                                        <select id="department_2" name="department_2" onchange="fetchProgrammes('department_2', 'p2')">
                                            <option value="">Select Department</option>
                                            <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
                                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Programme 2 (Optional) -->
                                    <div class="form-group">
                                        <label for="p2">Programme 2 (Optional)</label>
                                        <select id="p2" name="p2">
                                            <option value="">Select Programme</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="p2_start_date">Start Date (Optional)</label>
                                        <input type="date" id="p2_start_date" name="p2_start_date">
                                    </div>    
                                    <div class="form-group">
                                        <label for="p2_academic_year">Academic Year</label>
                                        <select id="p2_academic_year" name="p2_academic_year">
                                            <option value="">Select Academic Year</option>
                                            <option value="2024/5">2024/5</option>
                                            <option value="2025/6">2025/6</option>
                                            <option value="2026/7">2026/7</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="p2_current_year">Current  (Optional)</label>
                                        <input type="number" id="p2_current_year" name="p2_current_year" min="1" max="4">
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Add Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script src="template/sidebar.js"></script>
