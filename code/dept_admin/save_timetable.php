<?php
require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = $_POST['module'];
    $staffId = $_POST['lecturer'];
    $type = $_POST['type'];
    $startTime = $_POST['start-time'];
    $endTime = $_POST['end-time'];
    $location = $_POST['location'];
    $dates = explode(',', $_POST['dates']); // Split dates into an array

    try {
        foreach ($dates as $date) {
            $pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database
            $stmt = $pdo->prepare("
                INSERT INTO module_timetable (module_id, staff_id, type, start_time, end_time, date, location)
                VALUES (:module_id, :staff_id, :type, :start_time, :end_time, :date, :location)
            ");
            $stmt->execute([
                ':module_id' => $moduleId,
                ':staff_id' => $staffId,
                ':type' => $type,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
                ':date' => $date,
                ':location' => $location,
            ]);
        }
        header("Location: timetable.php"); // Redirect back to the timetable page
    } catch (PDOException $e) {
        echo "Error saving timetable entry: " . $e->getMessage();
    }
}
?>