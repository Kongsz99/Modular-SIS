<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auth.php';

check_role(DEPARTMENT_ADMIN);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Get the module ID from the query parameter
$moduleId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$moduleId) {
    die("Module ID is missing.");
}

// Fetch module details from the database
$moduleStmt = $pdo->prepare("
    SELECT m.*, CONCAT(s.first_name, ' ', s.last_name) AS module_leader_name
        FROM modules m
        LEFT JOIN assigned_lecturers al ON m.module_id = al.module_id
        LEFT JOIN staff s ON al.staff_id = s.staff_id
        WHERE m.module_id = :module_id;
");
$moduleStmt->execute(['module_id' => $moduleId]);
$module = $moduleStmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    die("Module not found.");
}

// Extract module details
$moduleCode = $module['module_id'];
$moduleName = $module['module_name'];

// Fetch all academic years from the student_modules table
$academicYearsQuery = "SELECT DISTINCT academic_year FROM student_modules ORDER BY academic_year DESC";
$academicYearsResult = $pdo->query($academicYearsQuery);
$academicYears = $academicYearsResult->fetchAll(PDO::FETCH_COLUMN);

// Get the selected academic year from the query parameter
$selectedAcademicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

// Fetch students enrolled in the module for the selected academic year
$students = [];
if ($selectedAcademicYear) {
    $studentsQuery = "
        SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name
        FROM students s
        JOIN student_modules sm ON s.student_id = sm.student_id
        WHERE sm.module_id = :module_id AND sm.academic_year = :academic_year
        ORDER BY s.student_id
    ";
    $studentsStmt = $pdo->prepare($studentsQuery);
    $studentsStmt->execute([
        'module_id' => $moduleId,
        'academic_year' => $selectedAcademicYear,
    ]);
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Enrolled in Module</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Add styles for the table and buttons */


        .add-student-button {
            padding: 8px 16px;
            font-size: 14px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .add-student-button:hover {
            background-color: #218838;
        }
        .toggle-action-button {
            padding: 8px 16px;
            font-size: 14px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .toggle-action-button:hover {
            background-color: #5a6268;
        }
        .btn-delete {
            padding: 5px 10px;
            font-size: 14px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        /* Hide the Action column by default */
        .students-table th.action-column,
        .students-table td.action-column {
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Staff Panel</span>
            </div>
            <ul class="nav">
                <li><a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.html"><i class="fas fa-users"></i>Students</a></li>
                <li><a href="module.html"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.html"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="grade.html"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.html"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="profile.html"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.html"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Students Enrolled in Module</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Staff</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Toggle Icon -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Back Button -->
                <a href="module_details.php?id=<?php echo $moduleId; ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Module
                </a>

                <!-- Module Title -->
                <h2><?php echo htmlspecialchars("$moduleCode : $moduleName"); ?></h2>

                <!-- Academic Year Selector -->
                <div class="academic-year-selector">
                    <label for="academic_year">Select Academic Year:</label>
                    <select id="academic_year" onchange="location = 'module_students.php?id=<?php echo $moduleId; ?>&academic_year=' + this.value;">
                        <option value="">Select an academic year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($selectedAcademicYear == $year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Add Student Button -->
                <button class="add-student-button" onclick="addStudent()">
                    <i class="fas fa-user-plus"></i> Add Student
                </button>

                <!-- Toggle Action Button -->
                <button class="toggle-action-button" onclick="toggleActions()">
                    <i class="fas fa-eye"></i> Show Actions
                </button>

                <!-- Students Table -->
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th class="action-column">Action</th>
                        </tr>
                    </thead>
                    <tbody id="students-list">
                        <?php if ($selectedAcademicYear): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td class="action-column">
                                        <button class="btn-delete" onclick="removeStudent('<?php echo $student['student_id']; ?>')"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">Please select an academic year to view students.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Function to handle "Add Student" button click
        function addStudent() {
            const studentId = prompt("Enter the Student ID:");
            const studentName = prompt("Enter the Student Name:");

            if (studentId && studentName) {
                const academicYear = document.getElementById('academic_year').value;
                const moduleId = '<?php echo $moduleId; ?>';

                // Send an AJAX request to add the student
                fetch('add_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        student_name: studentName,
                        module_id: moduleId,
                        academic_year: academicYear,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student added successfully!');
                        location.reload(); // Refresh the page to show the updated list
                    } else {
                        alert('Failed to add student: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the student.');
                });
            } else {
                alert("Please enter both Student ID and Name.");
            }
        }

        // Function to remove a student from the list
        function removeStudent(studentId) {
            if (confirm(`Are you sure you want to remove student ${studentId}?`)) {
                const academicYear = document.getElementById('academic_year').value;
                const moduleId = '<?php echo $moduleId; ?>';

                // Log the data being sent (for debugging)
                console.log('Removing student:', {
                    student_id: studentId,
                    module_id: moduleId,
                    academic_year: academicYear,
                });

                // Send an AJAX request to remove the student
                fetch('remove_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        module_id: moduleId,
                        academic_year: academicYear,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student removed successfully!');
                        location.reload(); // Refresh the page to show the updated list
                    } else {
                        alert('Failed to remove student: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the student.');
                });
            }
        }

        // Function to toggle the visibility of the Action column
        function toggleActions() {
            const actionColumns = document.querySelectorAll('.students-table th.action-column, .students-table td.action-column');
            const toggleButton = document.querySelector('.toggle-action-button');

            actionColumns.forEach(column => {
                if (column.style.display === 'none' || column.style.display === '') {
                    column.style.display = 'table-cell'; // Show the column
                    toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Actions';
                } else {
                    column.style.display = 'none'; // Hide the column
                    toggleButton.innerHTML = '<i class="fas fa-eye"></i> Show Actions';
                }
            });
        }
    </script>
</body>
</html>