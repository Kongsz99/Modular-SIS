<?php
// get_students.php

require_once '../db_connect.php';

$department = $_GET['department'] ?? '';

if (empty($department)) {
    die(json_encode(['error' => 'Department is required.']));
}

// Connect to the department database
$pdo = getDatabaseConnection(strtolower(string: $department));

// Fetch staff for the selected department
$sql = "
    SELECT s.staff_id, s.first_name, s.last_name
    FROM staff s
    JOIN users u ON s.user_id = u.user_id
    JOIN user_department ud ON u.user_id = ud.user_id
    WHERE ud.department_id = :department;
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['department' => $department]);

$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($staff);