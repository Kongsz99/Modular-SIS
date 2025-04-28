<?php
// dashboard.php

// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch modules for the dropdown
$modules = [];
try {
    $stmt = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_id ASC");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching modules: " . $e->getMessage();
}

// Initialize success message variable
$successMessage = '';

// Handle form submission for creating a new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['assignmentTitle'];
    $description = $_POST['assignmentDescription'];
    $dueDate = $_POST['assignmentDueDate'];
    $moduleId = $_POST['module'];
    
    // Handle file upload
    if (isset($_FILES['assignmentFile'])) {
        $file = $_FILES['assignmentFile'];
        $fileName = basename($file['name']);
        $uploadDir = '../uploads/';
        $uploadPath = $uploadDir . $fileName;

        // Move the uploaded file to the directory
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            try {
                // Insert the assignment into the database
                $stmt = $pdo->prepare("INSERT INTO assignment (module_id, title, description, due_date, document) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$moduleId, $title, $description, $dueDate, $fileName]);

                // Set success message
                $successMessage = "Assignment created and uploaded successfully!";
            } catch (PDOException $e) {
                echo "Error inserting assignment: " . $e->getMessage();
            }
        } else {
            echo "File upload failed.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Assignment Page</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .module-selector, .assignment-form {
            margin-bottom: 20px;
        }

        .module-selector label, .assignment-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .module-selector select {
            width: 93%;
            padding: 10px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px; 
        }

        .assignment-form input, .assignment-form textarea {
            width: 90%;
            padding: 10px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px; 
        }

        .assignment-form input[type="date"] {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Department Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span> Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span> Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span> Module Timetable</span></a></li>
                <li class="active"><a href="assignment.php"><i class="fas fa-file-alt"></i><span> Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span> Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span> Grade</span></a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Assignment Management</h1>
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
               <a href="assignment.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Assignment List
               </a>
                <div class="form-container">
                <h2>Create and Upload Assignment</h2>

                <!-- Module Selector -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="module-selector">
                        <label for="module">Select Module:</label>
                        <select id="module" name="module" required>
                                    <option value="">Select a Module</option>
                                    <?php foreach ($modules as $module): ?>
                                        <option value="<?php echo $module['module_id']; ?>">
                                            <?php echo htmlspecialchars($module['module_id'] . ' - ' . $module['module_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                        </select>
                    </div>

                <!-- Assignment Form -->
                <div class="assignment-form">
                        <label for="assignmentTitle">Assignment Title:</label>
                        <input type="text" id="assignmentTitle" name="assignmentTitle" placeholder="Assignment Title" required>

                        <label for="assignmentDescription">Assignment Description:</label>
                        <textarea id="assignmentDescription" name="assignmentDescription" placeholder="Assignment Description" rows="4" required></textarea>

                        <label for="assignmentDueDate">Due Date:</label>
                        <input type="date" id="assignmentDueDate" name="assignmentDueDate" required>

                        <label for="assignmentFile">Upload File:</label>
                        <input type="file" id="assignmentFile" name="assignmentFile" accept=".pdf,.doc,.docx,.ppt,.pptx" required>

                        <div class="form-group submit-button">
                            <button class="btn" type="submit">Create and Upload Assignment</button>
                        </div>
                    </form>
                </div>
        </div>

        <!-- Add SweetAlert JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <?php if (!empty($successMessage)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo $successMessage; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect or do something else if needed
                        // window.location.href = 'assignment.php';
                    }
                });
            });
        </script>
        <?php endif; ?>
</body>
</html>