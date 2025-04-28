<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Determine which database to use based on the module's department
function getDatabaseForModule($module_id) {
    // Example logic: If module_id starts with "CS", use CS database; otherwise, use BM
    if (strpos($module_id, 'CS') === 0) {
        return getDatabaseConnection('cs');
    } else {
        return getDatabaseConnection('bm');
    }
}

if (isset($_GET['id'])) {
    $module_id = $_GET['id'];

    // Connect to the appropriate database
    $pdo = getDatabaseForModule($module_id);

    // Debugging: Check if the module exists before deletion
    $stmt_check = $pdo->prepare("SELECT * FROM modules WHERE module_id = :module_id");
    $stmt_check->execute([':module_id' => $module_id]);
    $module = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        die("Module not found in the database.");
    }

    // Delete the module
    $stmt = $pdo->prepare("DELETE FROM modules WHERE module_id = :module_id");
    $stmt->execute([':module_id' => $module_id]);

    // Check if the deletion was successful
    if ($stmt->rowCount() > 0) {
        echo "Module ID $module_id successfully deleted.";
    } else {
        echo "Failed to delete Module ID $module_id.";
    }

    // Redirect back to the module list
    header("Location: module.php");
    exit();
} else {
    die("Module ID not provided.");
}

?>