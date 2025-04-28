<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $module_id = trim($_POST['module_id']);
    $module_name = trim($_POST['module_name']);
    $department_id = trim($_POST['department']);
    $level = trim($_POST['level']);
    $semester = trim($_POST['semester']);
    $credits = trim($_POST['credits']);
    $exam_weight = trim($_POST['exam_weight']);
    $assignment_weight = trim($_POST['assignment_weight']);


    // Determine which database to use based on the department
    if ($department_id == 'CS') {
        $pdo = getDatabaseConnection('cs');
    } elseif ($department_id == 'BM') {
        $pdo = getDatabaseConnection('bm');
    } else {
        die("Invalid department selected.");
    }

    // Insert the module into the database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO modules (module_id, module_name, department_id, level, semester, credits, exam_weight, assignment_weight)
            VALUES (:module_id, :module_name, :department_id, :level, :semester, :credits, :exam_weight, :assignment_weight)
        ");
        $stmt->execute([
            ':module_id' => $module_id,
            ':module_name' => $module_name,
            ':department_id' => $department_id,
            ':level' => $level,
            ':semester' => $semester,
            ':credits' => $credits,
            ':exam_weight' => $exam_weight,
            ':assignment_weight' => $assignment_weight
        ]);

        // Set a success message
        $_SESSION['message'] = "Module added successfully!";
    } catch (PDOException $e) {
        // Set an error message
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Redirect back to the form
    header("Location: add_module.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Management</title>
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
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li class="active"><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
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
                    <h1>Add Module</h1>
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
               <a href="module.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Module List
               </a>
                <div class="form-container">
                    <form method="POST">
                        <!-- Module Details -->
                            <div class="form-group">
                                <label for="module_id">Module ID</label>
                                <input type="text" id="module_id" name="module_id" placeholder="Enter module ID" required>
                            </div>
                            <div class="form-group">
                                <label for="module_name">Module Name</label>
                                <input type="text" id="module_name" name="module_name" placeholder="Enter module name" required>
                            </div>
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department" required>
                                    <option value="">Select Department</option> 
                                    <option value="CS">Computer Science</option>
                                    <option value="BM">Business Management</option>
                                </select>
                            </div> 
                            <div class="form-group">
                                <label for="level">Level</label>
                                <select id="level" name="level" required>
                                    <option value="">Select Level</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="semester">Semester</label>
                                <select id="semester" name="semester" required>
                                    <option value="">Select semester</option>
                                    <option value="1">semester 1</option>
                                    <option value="2">semester 2</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="credits">Credits</label>
                                <select id="credits" name="credits" required>
                                    <option value="">Select credits</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                    <option value="40">40</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exam_weight">Exam Weight</label>
                                <select id="exam_weight" name="exam_weight" required>
                                    <option value="">Select exam weight</option>
                                    <option value="30">30%</option>
                                    <option value="40">40%</option>
                                    <option value="50">50%</option>
                                    <option value="60">60%</option>
                                    <option value="70">70%</option>
                                    <option value="80">80%</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assignment_weight">Assignment Weight</label>
                                <select id="assignment_weight" name="assignment_weight" required>
                                    <option value="">Select assignment weight</option>
                                    <option value="30">30%</option>
                                    <option value="40">40%</option>
                                    <option value="50">50%</option>
                                    <option value="60">60%</option>
                                    <option value="70">70%</option>
                                    <option value="80">80%</option>
                                </select>
                            </div>
                        <div class="form-group submit-button">
                            <button type="submit" class="btn" onclick="return alert('Module Added Successfully!')">Add Module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>      
</html>