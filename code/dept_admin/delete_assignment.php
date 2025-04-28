<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(DEPARTMENT_ADMIN);

header('Content-Type: application/json');

// Get assignment ID from request
$data = json_decode(file_get_contents('php://input'), true);
$assignmentId = $data['id'] ?? null;

if (!$assignmentId) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID is missing']);
    exit();
}

// Connect to department database
$departmentId = $_SESSION['department_id'];
$pdo = getDatabaseConnection(strtolower($departmentId));

try {
    // Delete the assignment
    $stmt = $pdo->prepare("DELETE FROM assignment WHERE assignment_id = :id");
    $stmt->execute(['id' => $assignmentId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assignment not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting assignment: ' . $e->getMessage()]);
}