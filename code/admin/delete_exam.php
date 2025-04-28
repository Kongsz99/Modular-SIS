<?php
// delete_exam.php

require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $examId = $_GET['exam_id'] ?? '';
    $department = $_GET['department'] ?? '';

    if (empty($examId) || empty($department)) {
        die("Error: Exam ID or Department is missing.");
    }

    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department));

    // Delete the exam
    try {
        $sql = "DELETE FROM exam WHERE exam_id = :exam_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId]);

        header("Location: exam.php?success=Exam deleted successfully.");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}