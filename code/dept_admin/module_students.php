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
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="template/sidebar.js"></script>
    <style>
        /* Improved Academic Year Selector */
        .academic-year-selector {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .academic-year-selector label {
            font-weight: bold;
            color: #333;
        }
        
        .academic-year-selector select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            transition: all 0.3s;
            min-width: 200px;
        }
        
        .academic-year-selector select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        /* Button Styles */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .add-student-button {
            padding: 10px 16px;
            font-size: 14px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        
        .add-student-button:hover {
            background-color: #218838;
        }
        
        .toggle-action-button {
            padding: 10px 16px;
            font-size: 14px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
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
            transition: background-color 0.3s;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        /* Table Styles */
        /* .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        } */
        
        /* .students-table th, .students-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        } */
        
        /* .students-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        } */
        
        /* .students-table tr:hover {
            background-color: #f5f5f5;
        } */
        
        /* Hide the Action column by default */
        .students-table th.action-column,
        .students-table td.action-column {
            display: none;
        }
        
        /* Module Title */
        .module-title {
            font-size: 24px;
            margin: 20px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .module-title i {
            color: #4a90e2;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
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
            <h2 class="module-title">
                <i class="fas fa-book"></i>
                <?php echo htmlspecialchars("$moduleCode : $moduleName"); ?>
            </h2>

            <!-- Academic Year Selector -->
            <div class="academic-year-selector">
                <label for="academic_year">Academic Year:</label>
                <select id="academic_year" class="styled-select" onchange="location = 'module_students.php?id=<?php echo $moduleId; ?>&academic_year=' + this.value;">
                    <option value="">Select an academic year</option>
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo ($selectedAcademicYear == $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="add-student-button" onclick="showAddStudentModal()">
                    <i class="fas fa-user-plus"></i> Add Student
                </button>
                <button class="toggle-action-button" onclick="toggleActions()">
                    <i class="fas fa-eye"></i> Show Actions
                </button>
            </div>

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
                                    <button class="btn-delete" onclick="confirmRemoveStudent('<?php echo $student['student_id']; ?>', '<?php echo htmlspecialchars($student['student_name']); ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
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

     <!-- SweetAlert2 JS -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Function to show the add student modal
        function showAddStudentModal() {
            const academicYear = document.getElementById('academic_year').value;
            
            if (!academicYear) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Academic Year',
                    text: 'Please select an academic year first',
                    confirmButtonColor: '#3085d6',
                });
                return;
            }
            
            Swal.fire({
                title: 'Add Student to Module',
                html: `
                    <div class="swal2-form">
                        <input id="swal-input1" class="swal2-input" placeholder="Student ID" required>
                        <input id="swal-input2" class="swal2-input" placeholder="Student Name" required>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Add Student',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const studentId = document.getElementById('swal-input1').value.trim();
                    const studentName = document.getElementById('swal-input2').value.trim();
                    
                    if (!studentId || !studentName) {
                        Swal.showValidationMessage('Please fill in all fields');
                        return false;
                    }
                    
                    return { studentId, studentName };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    addStudent(result.value.studentId, result.value.studentName);
                }
            });
        }

        // Function to add a student
        function addStudent(studentId, studentName) {
            const academicYear = document.getElementById('academic_year').value;
            const moduleId = '<?php echo $moduleId; ?>';
            
            // Show loading indicator
            Swal.fire({
                title: 'Adding Student...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Student added successfully',
                        confirmButtonColor: '#28a745',
                    }).then(() => {
                        location.reload(); // Refresh the page to show the updated list
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: data.message || 'Failed to add student',
                        confirmButtonColor: '#dc3545',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while adding the student',
                    confirmButtonColor: '#dc3545',
                });
            });
        }

        // Function to confirm student removal
        function confirmRemoveStudent(studentId, studentName) {
            Swal.fire({
                title: 'Remove Student?',
                html: `Are you sure you want to remove <b>${studentName}</b> (ID: ${studentId}) from this module?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    removeStudent(studentId);
                }
            });
        }

        // Function to remove a student
        function removeStudent(studentId) {
            const academicYear = document.getElementById('academic_year').value;
            const moduleId = '<?php echo $moduleId; ?>';
            
            // Show loading indicator
            Swal.fire({
                title: 'Removing Student...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Student removed successfully',
                        confirmButtonColor: '#28a745',
                    }).then(() => {
                        location.reload(); // Refresh the page to show the updated list
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: data.message || 'Failed to remove student',
                        confirmButtonColor: '#dc3545',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while removing the student',
                    confirmButtonColor: '#dc3545',
                });
            });
        }

        // Function to toggle the visibility of the Action column
        function toggleActions() {
            const actionColumns = document.querySelectorAll('.students-table th.action-column, .students-table td.action-column');
            const toggleButton = document.querySelector('.toggle-action-button');
            const icon = toggleButton.querySelector('i');

            actionColumns.forEach(column => {
                if (column.style.display === 'none' || column.style.display === '') {
                    column.style.display = 'table-cell';
                    icon.className = 'fas fa-eye-slash';
                    toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Actions';
                } else {
                    column.style.display = 'none';
                    icon.className = 'fas fa-eye';
                    toggleButton.innerHTML = '<i class="fas fa-eye"></i> Show Actions';
                }
            });
        }
    </script>
</body>
</html>