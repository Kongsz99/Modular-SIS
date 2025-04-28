<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Handle form submission to update grades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $moduleId = $_POST['module_id'];
    $academicYear = $_POST['academic_year'];

    // Fetch assignment and exam weights for the selected module
    $weightQuery = "
        SELECT assignment_weight, exam_weight
        FROM modules
        WHERE module_id = :module_id
    ";
    $weightStmt = $pdo->prepare($weightQuery);
    $weightStmt->execute(['module_id' => $moduleId]);
    $weights = $weightStmt->fetch(PDO::FETCH_ASSOC);

    if (!$weights) {
        die("Module weights not found.");
    }

    $assignmentWeight = $weights['assignment_weight'];
    $examWeight = $weights['exam_weight'];

    foreach ($_POST['grades'] as $studentId => $marks) {
        $assignmentMarks = $marks['assignment'];
        $examMarks = $marks['exam'];

        // Calculate total marks using the module weights
        $totalMarks = ($assignmentMarks * ($assignmentWeight / 100)) + ($examMarks * ($examWeight / 100));
        $grade = calculateGrade($totalMarks); // Calculate grade based on total marks

        // Check if a grade record already exists for this student and module
        $checkQuery = "
            SELECT grade_id
            FROM grade
            WHERE module_id = :module_id
              AND student_id = :student_id
              AND academic_year = :academic_year
        ";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([
            'module_id' => $moduleId,
            'student_id' => $studentId,
            'academic_year' => $academicYear,
        ]);
        $existingGrade = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingGrade) {
            // Update the existing grade record
            $updateQuery = "
                UPDATE grade
                SET assignment_marks = :assignment_marks,
                    exam_marks = :exam_marks,
                    total_marks = :total_marks,
                    grade = :grade
                WHERE grade_id = :grade_id
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                'assignment_marks' => $assignmentMarks,
                'exam_marks' => $examMarks,
                'total_marks' => $totalMarks,
                'grade' => $grade,
                'grade_id' => $existingGrade['grade_id'],
            ]);
        } else {
            // Insert a new grade record
            $insertQuery = "
                INSERT INTO grade (module_id, academic_year, student_id, assignment_marks, exam_marks, total_marks, grade)
                VALUES (:module_id, :academic_year, :student_id, :assignment_marks, :exam_marks, :total_marks, :grade)
            ";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                'module_id' => $moduleId,
                'academic_year' => $academicYear,
                'student_id' => $studentId,
                'assignment_marks' => $assignmentMarks,
                'exam_marks' => $examMarks,
                'total_marks' => $totalMarks,
                'grade' => $grade,
            ]);
        }
    }

    // Redirect to avoid form resubmission
    header("Location: grade.php?academic_year=$academicYear&module=$moduleId");
    exit();
}

// Function to calculate grade based on total marks
function calculateGrade($totalMarks) {
    if ($totalMarks >= 90) return 'A+';
    if ($totalMarks >= 80) return 'A';
    if ($totalMarks >= 70) return 'B';
    if ($totalMarks >= 60) return 'C';
    if ($totalMarks >= 50) return 'D';
    return 'F';
}

// Fetch academic years
$academicYears = [];
$academicYearQuery = "SELECT DISTINCT academic_year FROM student_modules ORDER BY academic_year DESC";
$academicYearResult = $pdo->query($academicYearQuery);
if ($academicYearResult) {
    $academicYears = $academicYearResult->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch modules based on selected academic year
$selectedAcademicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;
$modules = [];

if ($selectedAcademicYear) {
    try {
        // Prepare the SQL statement with JOINs
        $stmt = $pdo->prepare("
            SELECT DISTINCT m.module_id, m.module_name
            FROM assigned_lecturers al
            JOIN modules m ON al.module_id = m.module_id
            JOIN student_modules sm ON sm.module_id = m.module_id
            WHERE al.staff_id = :staff_id
            AND sm.academic_year = :academic_year
            ORDER BY m.module_id ASC
        ");
        
        // Bind the parameters
        $stmt->bindParam(':staff_id', $staffId, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year', $selectedAcademicYear, PDO::PARAM_STR);
        
        // Execute the statement
        $stmt->execute();
        
        // Fetch all results as an associative array
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching modules: " . $e->getMessage();
    }
}

// Fetch students and grades for a selected module
$selectedModuleId = isset($_GET['module']) ? $_GET['module'] : null;
$students = [];
$grades = [];

if ($selectedModuleId) {
    // Fetch students enrolled in the selected module
    $studentQuery = "
        SELECT s.student_id, CONCAT(s.first_name, ' ' , s.last_name) AS student_name, sm.academic_year
        FROM students s
        JOIN student_modules sm ON s.student_id = sm.student_id
        WHERE sm.module_id = :module_id AND sm.academic_year = :academic_year
    ";
    $studentStmt = $pdo->prepare($studentQuery);
    $studentStmt->execute(['module_id' => $selectedModuleId, 'academic_year' => $selectedAcademicYear]);
    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch grades for the selected module
    $gradeQuery = "
        SELECT student_id, assignment_marks, exam_marks, total_marks, grade
        FROM grade
        WHERE module_id = :module_id AND academic_year = :academic_year
    ";
    $gradeStmt = $pdo->prepare($gradeQuery);
    $gradeStmt->execute(['module_id' => $selectedModuleId, 'academic_year' => $selectedAcademicYear]);
    $gradeResult = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize grades by student_id for easy lookup
    foreach ($gradeResult as $grade) {
        $grades[$grade['student_id']] = $grade;
    }
}

// Fetch assignment and exam weights for the selected module
$weights = [];
if ($selectedModuleId) {
    $weightQuery = "
        SELECT assignment_weight, exam_weight
        FROM modules
        WHERE module_id = :module_id
    ";
    $weightStmt = $pdo->prepare($weightQuery);
    $weightStmt->execute(['module_id' => $selectedModuleId]);
    $weights = $weightStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

        .btn {
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .edit-mode input {
            border: 1px solid #ccc;
            background-color: #fff;
        }
    </style>
</head>
<body>
<div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li class="active"><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
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
                    <h1>Grade Management</h1>
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
                <!-- Academic Year Selector -->
                <div class="search-filter">
                    <label for="academic_year">Select Academic Year:</label>
                    <select id="academic_year" onchange="location = 'grade.php?academic_year=' + this.value;">
                        <option value="">Select an academic year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($selectedAcademicYear == $year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Module Selector -->
                <div class="search-filter">
                    <label for="module">Select Module:</label>
                    <select id="module" onchange="location = 'grade.php?academic_year=<?php echo $selectedAcademicYear; ?>&module=' + this.value;">
                        <option value="">Select a module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['module_id']; ?>" <?php echo ($selectedModuleId == $module['module_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($module['module_id']); ?> - <?php echo htmlspecialchars($module['module_name']); ?> (<?php echo htmlspecialchars($module['academic_year']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Edit Mode Button -->
                <button id="editModeButton" class="btn" onclick="toggleEditMode()">Enable Edit Mode</button>

                <!-- Student Table -->
                <form method="POST" action="">
                    <input type="hidden" name="module_id" value="<?php echo $selectedModuleId; ?>">
                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">
                    <table class="student-table" id="studentTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Assignment Marks (<?php echo isset($weights['assignment_weight']) ? $weights['assignment_weight'] : 'N/A'; ?>%)</th>
                                <th>Exam Marks (<?php echo isset($weights['exam_weight']) ? $weights['exam_weight'] : 'N/A'; ?>%)</th>
                                <th>Total Marks (100%)</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($selectedModuleId): ?>
                                <?php foreach ($students as $student): ?>
                                    <?php $grade = $grades[$student['student_id']] ?? null; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                        <td>
                                            <input type="number" name="grades[<?php echo $student['student_id']; ?>][assignment]" value="<?php echo $grade ? $grade['assignment_marks'] : ''; ?>" disabled>
                                        </td>
                                        <td>
                                            <input type="number" name="grades[<?php echo $student['student_id']; ?>][exam]" value="<?php echo $grade ? $grade['exam_marks'] : ''; ?>" disabled>
                                        </td>
                                        <td><?php echo $grade ? $grade['total_marks'] : ''; ?></td>
                                        <td><?php echo $grade ? $grade['grade'] : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Please select an academic year and a module to view grades.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="update_grades" class="btn" style="display: none;" id="updateButton">Update Grades</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle Edit Mode
        function toggleEditMode() {
            const inputs = document.querySelectorAll('#studentTable input');
            const updateButton = document.getElementById('updateButton');
            const editModeButton = document.getElementById('editModeButton');

            inputs.forEach(input => {
                input.disabled = !input.disabled;
            });

            updateButton.style.display = updateButton.style.display === 'none' ? 'block' : 'none';
            editModeButton.textContent = editModeButton.textContent === 'Enable Edit Mode' ? 'Disable Edit Mode' : 'Enable Edit Mode';
        }
    </script>
</body>
</html>