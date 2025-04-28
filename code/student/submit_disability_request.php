<?php
// Include database connection
require_once '../db_connect.php';
require_once '../auths.php';

// Ensure the user is logged in
if (!isset($_SESSION['student_id'])) {
    die("You must be logged in to submit a disability request.");
}

// Get form data
$disabilityType = $_POST['disabilityType'];
$accommodationRequest = $_POST['accommodationRequest'];
$studentId = $_SESSION['student_id'];

// Get the shared database connection for the central database
$share_db_conn = getDatabaseConnection(dbKey: 'central');

// Handle file upload
$supportingDocumentPath = null;
if (isset($_FILES['supportingDocument']) && $_FILES['supportingDocument']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/disability_documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $fileName = uniqid() . '_' . basename($_FILES['supportingDocument']['name']);
    $supportingDocumentPath = $uploadDir . $fileName;

    // Debug: Print file details
    echo "<pre>";
    print_r($_FILES['supportingDocument']);
    echo "</pre>";

    if (move_uploaded_file($_FILES['supportingDocument']['tmp_name'], $supportingDocumentPath)) {
        echo "File uploaded successfully: $supportingDocumentPath<br>";
    } else {
        echo "File upload failed: Unable to move the file.<br>";
    }
} elseif (isset($_FILES['supportingDocument'])) {
    // Handle specific upload errors
    switch ($_FILES['supportingDocument']['error']) {
        case UPLOAD_ERR_NO_FILE:
            echo "No file was uploaded.<br>";
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            echo "File size exceeds the maximum limit.<br>";
            break;
        default:
            echo "File upload error: " . $_FILES['supportingDocument']['error'] . "<br>";
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
    // Generate a formatted disability ID using the central database
    $disabilityId = get_formatted_id($share_db_conn, 'disability_id_seq', 'D', 4);

    // Debug: Print disability ID and document path
    echo "Disability ID: $disabilityId<br>";
    echo "Document Path: " . ($supportingDocumentPath ?? "No file uploaded") . "<br>";

    // Loop through each department ID
    foreach ($departmentIds as $departmentId) {
        // Get the database connection for the department
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Start a transaction for the department
        $pdo->beginTransaction();

        // Insert the disability request into the department's table
        $stmt = $pdo->prepare(query: "
            INSERT INTO disability_requests (disability_id, student_id, disability_type, requested_accommodation, document)
            VALUES (:disability_id, :student_id, :disability_type, :requested_accommodation, :document)
        ");
        $stmt->execute([
            'disability_id' => $disabilityId,
            'student_id' => $studentId,
            'disability_type' => $disabilityType,
            'requested_accommodation' => $accommodationRequest,
            'document' => $supportingDocumentPath, // This will be NULL if no file is uploaded
        ]);

        // Commit the transaction for the department
        $pdo->commit();

        echo "Disability request submitted to department: $departmentId<br>";
    }
    
    $_SESSION['alert_message'] = "Your DAS request has been submitted successfully.";
    $_SESSION['alert_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['alert_message'] = "Failed to submit DAS request: " . $e->getMessage();
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