<?php
// auth.php

session_start();
require "constants.php"; // Include role constants
require_once 'db_connect.php';

function check_role($required_role) {
    // Ensure user is logged in
    if (!isset($_SESSION['role_id'])) {
        header("Location: ../login.html"); // Redirect to login if not authenticated
        exit();
    }

    // Check if user has the required role
    if ($_SESSION['role_id'] != $required_role) {
        die("❌ Unauthorized Access!");
    }  
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login if not authenticated
    exit();
}

// Connect to the database
$pdo = getDatabaseConnection('central');

// Fetch user details
$query = "
    SELECT a.first_name, a.last_name
    FROM admin a
    JOIN users u ON a.user_id = u.user_id
    WHERE u.user_id = :user_id
";

try {
    // Prepare the statement
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $stmt->execute(['user_id' => $_SESSION['user_id']]);

    // Fetch the user details
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}



?>
