<?php
require_once '../db_connect.php'; // Include the database connection script

// Get the department ID from the query string
$department_id = $_GET['department_id'] ?? '';

// Validate the department ID
if (empty($department_id)) {
    die(json_encode([]));
}

// Determine the target database based on the department ID
$target_db = null;
switch ($department_id) {
    case 'CS':
        $target_db = getDatabaseConnection('cs'); // Connection to the CS database
        break;
    case 'BM':
        $target_db = getDatabaseConnection('bm'); // Connection to the BM database
        break;
    default:
        die(json_encode([]));
}

// Fetch programmes from the target database
$programmes = [];
try {
    $query = "SELECT programme_id, programme_name FROM programme";
    $stmt = $target_db->query($query);
    $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode([]));
}

// Return the programmes as JSON
header('Content-Type: application/json');
echo json_encode($programmes);
?>