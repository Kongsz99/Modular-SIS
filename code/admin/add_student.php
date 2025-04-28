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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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

    $(document).ready(function() {
    $("#studentForm").on("submit", function(event) {
        event.preventDefault(); // Prevent page reload
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: "register_student.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    // Create a more detailed popup message
                    Swal.fire({
                        title: 'Success!',
                        html: `Student ID: <strong>${response.data.student_id}</strong> has been successfully registered.<br><br>
                               Username: ${response.data.username}`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        willClose: () => {
                            // Reload the page after the alert is closed
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Restore button state
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});
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
                    <form id="studentForm" method="POST">
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
