<?php
require_once '../db_connect.php';

// Get the search query from the request
$query = $_GET['query'] ?? '';

// Function to search students in a specific database
function searchStudents($dbName, $query) {
    $pdo = getDatabaseConnection($dbName);
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.first_name, s.last_name, d.department_name
        FROM students s
        JOIN user_department ud ON s.user_id = ud.user_id
        JOIN departments d ON ud.department_id = d.department_id
        WHERE s.student_id::TEXT ILIKE :query OR s.first_name ILIKE :query OR s.last_name ILIKE :query
    ");
    $stmt->execute([':query' => "%$query%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Search students in the 'cs' database
$csStudents = searchStudents('cs', $query);

// Search students in the 'bm' database
$bmStudents = searchStudents('bm', $query);

// Combine the results into a single array
$students = array_merge($csStudents, $bmStudents);

// Remove duplicates based on student_id
$uniqueStudents = [];
foreach ($students as $student) {
    $studentId = $student['student_id'];
    if (!isset($uniqueStudents[$studentId])) {
        $uniqueStudents[$studentId] = $student;
    }
}

// Convert the associative array back to a indexed array
$uniqueStudents = array_values($uniqueStudents);

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode($uniqueStudents);
?>