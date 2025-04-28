
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

if (isset($_POST['student_id']) && isset($_POST['status'])) {
    $studentId = $_POST['student_id'];
    $status = $_POST['status'];

    // Update the status in CS database
    $stmt = $pdo->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();

    // Return a success message
    echo json_encode(['status' => 'success']);
} else {
    // Invalid request
    echo json_encode(['status' => 'error', 'message' => 'Missing student_id or status']);
}

// Assuming you're using PDO to handle the database update
// Perform the status update query and send a JSON response
if ($statusUpdateSuccess) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to update status.']);
}

?>
