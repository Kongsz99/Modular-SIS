<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department']; // e.g., 'CS' or 'BM'
    $module_id = $_POST['module_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $academic_year = $_POST['academic_year'];

    // Validate input
    if (empty($department) || empty($module_id) || empty($exam_date) || empty($start_time) || empty($end_time) || empty($location) || empty($year_semester_id)) {
        die("Error: All fields are required.");
    } 

    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department)); // e.g., 'cs' or 'bm'

    // Insert the exam details
    try {
        $sql = "INSERT INTO exam (module_id, exam_date, start_time, end_time, location, academic_year) VALUES (:module_id, :exam_date, :start_time, :end_time, :location, :academic_year)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'module_id' => $module_id,
            'exam_date' => $exam_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'location' => $location,
            'academic_year' => $academic_year,
        ]);

        $success_message = "Exam added successfully!";
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Exam</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        // Fetch departments on page load
    document.addEventListener('DOMContentLoaded', function () {
    // Fetch departments on page load
    console.log('Fetching departments...'); // Debugging
    fetch('get_departments.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Departments fetched:', data); // Debugging
            const departmentSelect = document.getElementById('department');
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.department_id; // e.g., 'CS' or 'BM'
                option.textContent = dept.department_name; // e.g., 'Computer Science'
                departmentSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching departments:', error); // Debugging
        });
    // Fetch modules when department is selected
    document.getElementById('department').addEventListener('change', function () {
        const department = this.value;
        console.log('Department selected:', department); // Debugging

        if (department) {
            fetch(`get_modules.php?department=${department}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Modules fetched:', data); // Debugging
                    const moduleSelect = document.getElementById('module-id');

                    if (!moduleSelect) {
                        console.error('Module select element not found!'); // Debugging
                        return;
                    }

                    // Clear existing options
                    moduleSelect.innerHTML = '<option value="">Select Module</option>';

                    // Populate the module dropdown
                    data.forEach(module => {
                        const option = document.createElement('option');
                        option.value = module.module_id; // Set the value to module_id
                        option.textContent = `${module.module_id} - ${module.module_name}`; // Display both module_id and module_name
                        moduleSelect.appendChild(option);
                    });

                    console.log('Module dropdown populated successfully.'); // Debugging
                })
                .catch(error => {
                    console.error('Error fetching modules:', error); // Debugging
                });
        } else {
            // If no department is selected, clear the module dropdown
            const moduleSelect = document.getElementById('module-id');
            if (moduleSelect) {
                moduleSelect.innerHTML = '<option value="">Select Module</option>';
                console.log('Department selection cleared. Module dropdown reset.'); // Debugging
            } else {
                console.error('Module select element not found!'); // Debugging
            }
        }
    });
});
    </script>
</head>
<body class="add-module-page">
    <div class="dashboard">
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
                    <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                    <li class="active"><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
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
                    <h1>Exam Management</h1>
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
            <a href="exam.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Exam List
            </a>

            <!-- Add New Exam Form -->
            <div class="form-container">
                <form id="add-exam-form" action="add_exam.php" method="POST">
                    <!-- Exam Details -->
                    <fieldset>
                        <legend>Exam Details</legend>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department" required>
                                <option value="">Select Department</option>
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="module-id">Module</label>
                            <select id="module-id" name="module_id" required>
                                <option value="">Select Module</option>
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exam-date">Date</label>
                            <input type="date" id="exam-date" name="exam_date" required>
                        </div>
                        <div class="form-group">
                            <label for="start-time">Start Time</label>
                            <input type="time" id="start-time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end-time">End Time</label>
                            <input type="time" id="end-time" name="end_time" required>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="Enter Location" required>
                        </div>
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <select id="academic_year" name="academic_year" required>
                                <option value="">Select Academic Year</option>
                                <option value="2023/4">2023/4</option>
                                <option value="2024/5">2024/5</option>
                                <option value="2025/6">2025/6</option>
                            </select>
                        </div>
                    </fieldset>

                    <!-- Submit Button -->
                    <div class="form-group submit-button">
                        <button type="submit" class="btn">Add Exam</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<script src="template/sidebar.js"></script>
