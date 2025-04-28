<?php
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

// Get the POST data
$data = json_decode(file_get_contents(filename: 'php://input'), true);

$studentId = $data['student_id'];
$studentName = $data['student_name'];
$moduleId = $data['module_id'];
$academicYear = $data['academic_year'];

// Insert the student into the student_modules table
$insertQuery = "
    INSERT INTO student_modules (student_id, module_id, academic_year, status)
    VALUES (:student_id, :module_id, :academic_year, 'Enroled')
";
$insertStmt = $pdo->prepare($insertQuery);
$insertStmt->execute([
    'student_id' => $studentId,
    'module_id' => $moduleId,
    'academic_year' => $academicYear,
]);

// Return a JSON response
echo json_encode(['success' => true]);
?>