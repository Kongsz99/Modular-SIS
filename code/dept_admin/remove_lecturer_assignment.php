<?php
// remove_assignment.php

require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    die("Invalid request method. Expected DELETE, got " . $_SERVER['REQUEST_METHOD']);
}

// Parse the input data from the request body
$input = json_decode(file_get_contents('php://input'), true);
$staffId = $input['staff_id'] ?? '';
$moduleId = $input['module_id'] ?? '';

// Validate the input
if (empty($staffId) || empty($moduleId)) {
    http_response_code(400); // Bad Request
    die("Staff ID and Module ID are required.");
}

try {
    // Connect to the CS database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Delete the assignment from the assigned_lecturers table
    $query = "DELETE FROM assigned_lecturers WHERE staff_id = :staff_id AND module_id = :module_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'staff_id' => $staffId,
        'module_id' => $moduleId,
    ]);

    // Return a success response
    echo json_encode(['success' => true, 'message' => 'Assignment removed successfully.']);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>