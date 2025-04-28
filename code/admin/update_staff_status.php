<?php
require_once '../db_connect.php';

// Get the data from the AJAX request
$id = $_POST['id'];
$status = $_POST['status'];
$type = $_POST['type'];

try {
    if ($type === 'admin') {
        // Update admin status in the central database
        $pdo = getDatabaseConnection('central');
        $sql = "UPDATE admin SET status = :status WHERE admin_id = :id";
        $stmt = $pdo->prepare(query: $sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    } else {
        // Update staff (lecturer) status in the appropriate department database
        // Fetch the user_id of the staff member from the central database
        $pdo_central = getDatabaseConnection('cs');
        $sql = "SELECT u.user_id 
                FROM users u
                WHERE u.user_id = (SELECT user_id FROM staff WHERE staff_id = :id)";
        $stmt = $pdo_central->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Staff member not found.");
        }

        $user_id = $user['user_id'];

        // Fetch the department_id of the staff member from the central database
        $sql = "SELECT ud.department_id 
                FROM user_department ud
                WHERE ud.user_id = :user_id";
        $stmt = $pdo_central->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$department) {
            throw new Exception("Department not assigned to staff member.");
        }

        // Determine which database to use based on the department ID
        $department_id = $department['department_id'];
        $database_name = strtolower($department_id); // Convert department ID to lowercase (e.g., 'CS' → 'cs')
        $pdo = getDatabaseConnection($database_name); // Connect to the appropriate department database

        // Update the status in the department-specific database
        $sql = "UPDATE staff SET status = :status WHERE staff_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>