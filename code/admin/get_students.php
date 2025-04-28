<?php
// get_students.php

// require_once '../db_connect.php';

// $department = $_GET['department'] ?? '';

// if (empty($department)) {
//     die(json_encode(['error' => 'Department is required.']));
// }

// //Connect to the department database
// $pdo = getDatabaseConnection(strtolower(string: $department));

// // Fetch students for the selected department
// $sql = "
//     SELECT s.student_id, u.first_name, u.last_name
//     FROM foreign_students s
//     JOIN foreign_users u ON s.user_id = u.user_id
//     JOIN foreign_user_department ud ON u.user_id = ud.user_id
//     WHERE ud.department_id = :department;
// ";
// $stmt = $pdo->prepare($sql);
// $stmt->execute(['department' => $department]);

// $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// header('Content-Type: application/json');
// echo json_encode($students);

// get_students.php

// get_students.php

require_once '../db_connect.php';

$department = $_GET['department'] ?? '';
$search = $_GET['search'] ?? '';

if (empty($department)) {
    die(json_encode(['error' => 'Department is required.']));
}

// Connect to the department database
$pdo = getDatabaseConnection(strtolower($department));

// Fetch students for the selected department with optional search
$sql = "
    SELECT s.student_id, s.first_name, s.last_name
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN user_department ud ON u.user_id = ud.user_id
    WHERE ud.department_id = :department
";

if (!empty($search)) {
    // Cast student_id to text for the LIKE comparison
    $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR CAST(s.student_id AS TEXT) LIKE :search)";
    $searchTerm = "%$search%";
}

$stmt = $pdo->prepare($sql);
$params = ['department' => $department];
if (!empty($search)) {
    $params['search'] = $searchTerm;
}

$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($students);
?>