<?php
// get_programme.php

require_once '../db_connect.php';

$department = $_GET['department'] ?? '';

if (empty($department)) {
    die(json_encode(['error' => 'Department is required.']));
}

// Connect to the selected department's database
$pdo = getDatabaseConnection(strtolower($department)); // e.g., 'cs' or 'bm'

// Fetch modules
$sql = "SELECT programme_id, programme_name FROM programme ORDER BY programme_id";
$stmt = $pdo->query($sql);

if (!$stmt) {
    die(json_encode(['error' => 'Failed to fetch programme.']));
}

$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($programmes);