<?php
// auths.php

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

// Connect to the central database
$pdoCentral = getDatabaseConnection('central');

// Fetch user's departments
$departmentQuery = "
    SELECT department_id
    FROM user_department
    WHERE user_id = :user_id
";

try {
    // Prepare the statement
    $stmt = $pdoCentral->prepare($departmentQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $stmt->execute(['user_id' => $_SESSION['user_id']]);

    // Fetch all department IDs
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($departments)) {
        throw new Exception("User is not associated with any department.");
    }

    // Store the department IDs in the session as an array
    $_SESSION['department_ids'] = $departments;

    // For backward compatibility, store the first department ID in $_SESSION['department_id']
    $_SESSION['department_id'] = $departments[0];
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}

// Connect to the department-specific database (using the first department ID)
$pdoDepartment = getDatabaseConnection(strtolower($_SESSION['department_id']));

// Fetch user details based on their role
$roleId = $_SESSION['role_id'];
$userId = $_SESSION['user_id'];

try {
    if ($roleId == STUDENT) {
        // Fetch student_id and name
        $query = "
            SELECT student_id, first_name, last_name
            FROM students
            WHERE user_id = :user_id
        ";
        $idColumn = 'student_id';
    } elseif ($roleId == STAFF) {
        // Fetch staff_id and name
        $query = "
            SELECT staff_id, first_name, last_name
            FROM staff
            WHERE user_id = :user_id
        ";
        $idColumn = 'staff_id';
    } else {
        throw new Exception("Invalid role.");
    }

    // Prepare the statement
    $stmt = $pdoDepartment->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare the SQL statement.");
    }

    // Bind the parameter and execute the query
    $stmt->execute(['user_id' => $userId]);

    // Fetch the result
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User details not found in the department database.");
    }

    // Store the ID and name in the session
    $_SESSION[$idColumn] = $user[$idColumn];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>