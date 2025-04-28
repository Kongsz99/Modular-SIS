<?php
// session_start();
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Connect to the central database
$pdo = getDatabaseConnection('central');

if (isset($_GET['id'])) {
    $programme_id = $_GET['id'];

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Step 2: Delete the programme from the programme table
        $stmt_delete_scholarship = $pdo->prepare("DELETE FROM scholarship WHERE scholarship_id = :scholarship_id");
        $stmt_delete_scholarship->execute([':scholarship_id' => $scholarship_id]);

        // Commit the transaction
        $pdo->commit();

        // Set a success message
        $_SESSION['message'] = "Scholarship ID $scholarship_id successfully deleted.";
    } catch (PDOException $e) {
        // Roll back the transaction if an error occurs
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Redirect back to the scholarship list
    header("Location: scholarship.php");
    exit();
} else {
    die("Scholarship ID not provided.");
}
?>
