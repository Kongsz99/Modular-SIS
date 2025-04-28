<?php
// dashboard.php

// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];
$staffId = $_SESSION['staff_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch modules for the dropdown
$modules = [];
try {
    // Fetch modules taught by this staff member
    $moduleQuery = "
    SELECT m.module_id, m.module_name, m.level, m.semester, m.credits 
    FROM modules m
    JOIN assigned_lecturers al ON m.module_id = al.module_id
    WHERE al.staff_id = :staff_id
    ORDER BY m.module_id
    ";
    $moduleStmt = $pdo->prepare($moduleQuery);
    $moduleStmt->execute(['staff_id' => $staffId]);
    $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$assignments = [];
try {  
    // Prepare the SQL statement with JOINs
    $stmt = $pdo->prepare("
        SELECT a.*, m.module_name 
        FROM assignment a 
        JOIN modules m ON a.module_id = m.module_id 
        JOIN assigned_lecturers al ON m.module_id = al.module_id 
        WHERE al.staff_id = :staff_id 
        ORDER BY a.module_id ASC
    ");
    
    // Bind the staff_id parameter
    $stmt->bindParam(':staff_id', $staffId, PDO::PARAM_INT);
    
    // Execute the statement
    $stmt->execute();
    
    // Fetch all results as an associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching assignments: " . $e->getMessage();
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
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li class="active"><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
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
                <div class="new-button-container">
                        <a href="add_assignment.php" class="btn-new">
                            <i class="fas fa-plus"></i>
                                New
                        </a>
                    </div> 
                <!-- Assignment Table -->
                <h2>List of Assignments</h2>
                <table class="assignment-table" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Module ID</th>
                            <th>Module Name</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- Assignments will be dynamically populated here -->
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?= $assignment['module_id']; ?></td>
                                <td><?= $assignment['module_name']; ?></td>
                                <td><?= $assignment['title']; ?></td>
                                <td><?= $assignment['description']; ?></td>
                                <td><?= $assignment['document']; ?></td>
                                <td><?= $assignment['due_date']; ?></td>
                                <td>
                                <button class="btn-edit" onclick="editAssignment('<?= $assignment['assignment_id']; ?>')">
                                        <i class='fas fa-edit'></i>
                                     </button>
                                    <button class="btn-delete" onclick="deleteAssignment('<?= $assignment['assignment_id']; ?>')">
                                        <i class='fas fa-trash-alt'></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- // Update the existing script in assignment.php -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                function editAssignment(assignmentId) {
                    window.location.href = `edit_assignment.php?id=${assignmentId}`;
                }

                function deleteAssignment(assignmentId) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('delete_assignment.php', {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id: assignmentId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: data.message,
                                        confirmButtonColor: '#28a745',
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message,
                                        confirmButtonColor: '#dc3545',
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while deleting the assignment.',
                                    confirmButtonColor: '#dc3545',
                                });
                            });
                        }
                    });
                }
            </script>
       </div>
    </div>
</body>
</html>
