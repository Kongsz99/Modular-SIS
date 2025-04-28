<?php
// delete_module.php

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
$moduleId = $input['id'] ?? '';

// Validate the input
if (empty($moduleId)) {
    http_response_code(400); // Bad Request
    die("Module ID is required.");
}

try {
    // Connect to the CS database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Delete the module from the modules table
    $query = "DELETE FROM modules WHERE module_id = :module_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['module_id' => $moduleId]);

    // Return a success response
    echo json_encode(['success' => true, 'message' => 'Module deleted successfully.']);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>