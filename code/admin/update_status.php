
// require_once '../db_connect.php'; // DB connection
// require_once '../auth.php'; // Authentication check

// // Ensure user is authorized to perform this action
// check_role(GLOBAL_ADMIN);

// // Get the student ID and new status from the request
// $studentId = $_POST['student_id'] ?? null;
// $newStatus = $_POST['status'] ?? null;

// // Check if the student ID and status are valid
// if ($studentId && $newStatus) {
//     // Update the student status in the database
//     try {
//         $pdo = getDatabaseConnection(); // Get DB connection (update this with the correct db if needed)
//         $stmt = $pdo->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
//         $stmt->bindParam(':status', $newStatus);
//         $stmt->bindParam(':student_id', $studentId);
//         $stmt->execute();
//         echo json_encode(['success' => true]);
//     } catch (PDOException $e) {
//         echo json_encode(['success' => false, 'error' => $e->getMessage()]);
//     }
// } else {
//     echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
// }
<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

if (isset($_POST['student_id']) && isset($_POST['status'])) {
    $studentId = $_POST['student_id'];
    $status = $_POST['status'];

    // Update the status in CS database
    $pdo_cs = getDatabaseConnection('cs');
    $stmtCs = $pdo_cs->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
    $stmtCs->bindValue(':status', $status);
    $stmtCs->bindValue(':student_id', $studentId);
    $stmtCs->execute();

    // Update the status in BM database if the student exists there
    $pdo_bm = getDatabaseConnection('bm');
    $stmtBm = $pdo_bm->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
    $stmtBm->bindValue(':status', $status);
    $stmtBm->bindValue(':student_id', $studentId);
    $stmtBm->execute();

    // Return a success message
    echo json_encode(['status' => 'success']);
} else {
    // Invalid request
    echo json_encode(['status' => 'error', 'message' => 'Missing student_id or status']);
}

// Assuming you're using PDO to handle the database update
// Perform the status update query and send a JSON response
if ($statusUpdateSuccess) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unable to update status.']);
}

?>

// require_once '../db_connect.php';
// require_once '../auth.php';

// check_role(GLOBAL_ADMIN);

// if (isset($_POST['student_id']) && isset($_POST['status'])) {
//     $studentId = $_POST['student_id'];
//     $status = $_POST['status'];

//     // Update the status in CS database
//     $pdo_cs = getDatabaseConnection('cs');
//     $stmtCs = $pdo_cs->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
//     $stmtCs->bindValue(':status', $status);
//     $stmtCs->bindValue(':student_id', $studentId);
//     $stmtCs->execute();

//     // Update the status in BM database if the student exists there
//     $pdo_bm = getDatabaseConnection('bm');
//     $stmtBm = $pdo_bm->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
//     $stmtBm->bindValue(':status', $status);
//     $stmtBm->bindValue(':student_id', $studentId);
//     $stmtBm->execute();

//     // Return a success message
//     echo json_encode(['status' => 'success']);
// } else {
//     // Invalid request
//     echo json_encode(['status' => 'error', 'message' => 'Missing student_id or status']);
// }

