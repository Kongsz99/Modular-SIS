<?php
// delete_tutor_assignment.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

// Get the staff_id, student_id, and department from the query parameters
$staff_id = $_GET['staff_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$department = $_GET['department'] ?? null;

// Validate the input
if (empty($staff_id) || empty($student_id) || empty($department)) {
    die("Error: Invalid input. Staff ID, Student ID, and Department are required.");
}

try {
    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department));

    // Prepare the SQL query to delete the assigned tutor
    $sql = "DELETE FROM academic_tutor_assigned WHERE staff_id = :staff_id AND student_id = :student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'staff_id' => $staff_id,
        'student_id' => $student_id,
    ]);

    // Check if the deletion was successful
    if ($stmt->rowCount() > 0) {
        // Redirect back to the main page with a success message
        header("Location: tutor.php?message=Tutor+assignment+deleted+successfully");
        exit();
    } else {
        // Redirect back to the main page with an error message
        header("Location: tutor.php?error=Tutor+assignment+not+found");
        exit();
    }
} catch (PDOException $e) {
    // Handle database errors
    die("Error deleting tutor assignment: " . $e->getMessage());
}
?>