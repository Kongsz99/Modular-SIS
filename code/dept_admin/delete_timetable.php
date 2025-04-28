<?php
require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Get the timetable_id from the URL parameter
if (isset($_GET['timetable_id'])) {
    $timetable_id = $_GET['timetable_id'];

    // Sanitize the timetable_id to prevent SQL injection
    $timetable_id = filter_var($timetable_id, FILTER_SANITIZE_STRING);

    // Prepare the SQL query to delete the timetable entry
    $sql = "DELETE FROM module_timetable WHERE timetable_id = :timetable_id";

    // Use prepared statements to prevent SQL injection
    $stmt = $pdo->prepare($sql);
    if ($stmt) {
        $stmt->bindParam(':timetable_id', $timetable_id, PDO::PARAM_STR); // Bind as string
        if ($stmt->execute()) {
            echo "Timetable entry deleted successfully.";
        } else {
            echo "Error deleting timetable entry: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Error preparing statement: " . $pdo->errorInfo()[2];
    }
} else {
    echo "Invalid request. Timetable ID not provided.";
}

// Redirect back to the timetable page (or any other page)
header("Location: timetable.php"); // Replace with the actual page you want to redirect to
exit();
?>