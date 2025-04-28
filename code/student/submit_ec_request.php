<?php
// Include database connection
require_once '../db_connect.php';
require_once '../auths.php';

// Ensure the user is logged in
if (!isset($_SESSION['student_id'])) {
    die("You must be logged in to submit an EC request.");
}

// Get form data
$ecType = $_POST['ecType'];
$ecDescription = $_POST['ecDescription'];
$studentId = $_SESSION['student_id'];

// Get the shared database connection for the central database
$share_db_conn = getDatabaseConnection(dbKey: 'central');

// Handle file upload
$supportingDocumentPath = null;
if (isset($_FILES['ecSupportingDocument']) && $_FILES['ecSupportingDocument']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/ec_documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $fileName = uniqid() . '_' . basename($_FILES['ecSupportingDocument']['name']);
    $supportingDocumentPath = $uploadDir . $fileName;

    // Debug: Print file details
    echo "<pre>";
    print_r($_FILES['ecSupportingDocument']);
    echo "</pre>";

    if (move_uploaded_file($_FILES['ecSupportingDocument']['tmp_name'], $supportingDocumentPath)) {
        echo "File uploaded successfully: $supportingDocumentPath<br>";
    } else {
        echo "File upload failed: Unable to move the file.<br>";
    }
} elseif (isset($_FILES['ecSupportingDocument'])) {
    // Handle specific upload errors
    switch ($_FILES['ecSupportingDocument']['error']) {
        case UPLOAD_ERR_NO_FILE:
            echo "No file was uploaded.<br>";
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            echo "File size exceeds the maximum limit.<br>";
            break;
        default:
            echo "File upload error: " . $_FILES['ecSupportingDocument']['error'] . "<br>";
    }
} else {
    echo "File upload not attempted.<br>";
}

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

try {
    // Generate a formatted EC ID using the central database
    $ecId = get_formatted_id($share_db_conn, 'ec_id_seq', 'EC', 8);

    // Debug: Print EC ID and document path
    echo "EC ID: $ecId<br>";
    echo "Document Path: " . ($supportingDocumentPath ?? "No file uploaded") . "<br>";

    // Loop through each department ID
    foreach ($departmentIds as $departmentId) {
        // Get the database connection for the department
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Start a transaction for the department
        $pdo->beginTransaction();

        // Insert the EC request into the department's table
        $stmt = $pdo->prepare("
            INSERT INTO ec_requests (ec_id, student_id, ec_type, ec_description, supporting_document)
            VALUES (:ec_id, :student_id, :ec_type, :ec_description, :supporting_document)
        ");
        $stmt->execute([
            'ec_id' => $ecId,
            'student_id' => $studentId,
            'ec_type' => $ecType,
            'ec_description' => $ecDescription,
            'supporting_document' => $supportingDocumentPath, // This will be NULL if no file is uploaded
        ]);

        // Commit the transaction for the department
        $pdo->commit();

        echo "EC request submitted to department: $departmentId<br>";
    }

    $_SESSION['alert_message'] = "Your Exceptional Circumstances (EC) request has been submitted successfully.";
    $_SESSION['alert_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['alert_message'] = "Failed to submit EC request: " . $e->getMessage();
    $_SESSION['alert_type'] = 'error';
}

header("Location: disability_request.php");
exit;

/**
 * Function to generate a formatted ID using a sequence.
 */
function get_nextval($share_db_conn, $sequence_name) {
    $query = "SELECT nextval(?) AS nextval";
    $stmt = $share_db_conn->prepare($query);
    $stmt->execute([$sequence_name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['nextval'];
}

function get_formatted_id($share_db_conn, $sequence_name, $prefix, $padding_length) {
    $nextval = get_nextval($share_db_conn, $sequence_name);
    return $prefix . str_pad($nextval, $padding_length, '0', STR_PAD_LEFT);
}
?>