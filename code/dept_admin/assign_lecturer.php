<?php
// assign_lecturer.php

require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die("Invalid request method.");
}

// Get the form data
$moduleId = $_POST['module'] ?? '';
$staffId = $_POST['lecturer'] ?? '';

// Validate the input
if (empty($moduleId) || empty($staffId)) {
    http_response_code(400); // Bad Request
    die("Module ID and Lecturer ID are required.");
}

try {
    // Connect to the CS database
    $pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database

    // Insert the assignment into the assigned_lecturers table
    $query = "INSERT INTO assigned_lecturers (staff_id, module_id) VALUES (:staff_id, :module_id)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'staff_id' => $staffId,
        'module_id' => $moduleId,
    ]);

    // Return a success response
    echo json_encode(['success' => true, 'message' => 'Lecturer assigned successfully.']);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// // Execute the statement
// if ($stmt->execute()) {
//     echo "<script>alert('Lecturer assign to module successfully!');</script>";
// } else {
//     echo "<script>alert('Error assigning lecturer!');</script>";
// }
 // Redirect back to the form
 header("Location: module.php");
 exit();
?>