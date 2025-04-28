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
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

$studentId = $data['student_id'];
$moduleId = $data['module_id'];
$academicYear = $data['academic_year'];

if (!$studentId || !$moduleId || !$academicYear) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

try {
    // Remove the student from the student_modules table
    $deleteQuery = "
        DELETE FROM student_modules
        WHERE student_id = :student_id
          AND module_id = :module_id
          AND academic_year = :academic_year
    ";
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleteStmt->execute([
        'student_id' => $studentId,
        'module_id' => $moduleId,
        'academic_year' => $academicYear,
    ]);

    // Check if any rows were affected
    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No student found to remove.']);
    }
} catch (PDOException $e) {
    // Log the error
    error_log("Error removing student: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
?>