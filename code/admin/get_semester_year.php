<?php
// get_year_semesters.php

require_once '../db_connect.php';

// Connect to the database
$pdo = getDatabaseConnection('central'); // Adjust based on your database setup

// Fetch year_semester data
$sql = "SELECT year_semester_id, academic_year, semester_name FROM year_semester";
$stmt = $pdo->query($sql);

if (!$stmt) {
    die(json_encode(['error' => 'Failed to fetch year_semester data.']));
}

$yearSemesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($yearSemesters);