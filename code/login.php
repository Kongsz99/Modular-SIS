<?php
// login.php

session_start();
session_regenerate_id(true); // Regenerate session ID to prevent fixation

// Set session cookie parameters securely (use this in your config or login script)
// session_set_cookie_params([
//     'lifetime' => 0, // Session cookie until browser is closed
//     'secure' => true, // Ensures the cookie is only sent over HTTPS
//     'httponly' => true, // Prevents JavaScript from accessing session cookie
//     'samesite' => 'Strict' // Prevents cross-site request forgery (CSRF)
// ]);


require_once 'db_connect.php'; // Include your database connection file

// Set response header for JSON response
// header('Content-Type: application/json');

// Handle login form submission as an API request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Password should not be sanitized

    // Validate inputs
    if (empty($username) || empty($password)) {
        echo json_encode(["error" => "Username and password are required."]);
        exit();
    }

    // Connect to the central database
    try {
        $pdo = getDatabaseConnection('central'); // Use the central database
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
        exit();
    }

    // Fetch user from the database
    try {
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.role_id, u.password_hash, ud.department_id
            FROM users u
            LEFT JOIN user_department ud ON u.user_id = ud.user_id
            WHERE u.username = :username
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database query failed: " . $e->getMessage()]);
        exit();
    }

    // Verify user and password
    if ($user && password_verify($password, $user['password_hash'])) {
        // Store user data in session (for backend use)
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $username;
        $_SESSION['department_id'] = $user['department_id']; // Store department ID

        // Return a success response with role-based redirect URL
        $response = [
            "success" => true,
            "role_id" => $user['role_id'],
            "redirect_url" => getRedirectURL($user['role_id']) // Helper function for URL redirection
        ];

        echo json_encode($response);
        exit();
    } else {
        // Invalid credentials
        echo json_encode(["error" => "Invalid login credentials."]);
        exit();
    }
}

/**
 * Helper function to get the redirection URL based on the user role.
 */
function getRedirectURL($role_id) {
    switch ($role_id) {
        case 1: // global_admin
            header("Location: admin/global_admin_dashboard.php");
            break;
        case 2: // department_admin
            header("Location: dept_admin/dept_admin_dashboard.php");
            break;
        case 3: // lecturer
            header("Location: lecturer/lecturer_dashboard.php");
            break;
        case 4: // student
            header("Location: student/student_dashboard.php");
            break;
        default:
            die("Invalid role.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>
<style>
 * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background-color: white;
    padding: 40px; /* Increased padding for a larger form */
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    text-align: center;
    width: 90%; /* Responsive width */
    max-width: 400px; /* Maximum width for larger screens */
}

.logo {
    max-width: 300px; /* Adjust the size of the logo */
    margin-bottom: 20px;
}

.pointer-page {
    color: black;
}

.login-form {
    display: flex;
    flex-direction: column;
}

label {
    margin: 15px 0 5px;
    text-align: left;
    font-size: 1.2em; /* Larger font size for labels */
}

input[type="text"],
input[type="password"] {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
}

.sign-in-button {
    background-color: #007BFF; /* Blue color */
    color: white;
    padding: 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.2em; /* Larger font size for button */
    transition: background-color 0.3s;
}

.sign-in-button:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

.problem-signin {
    margin-top: 10px;
    color: #007BFF;
    text-decoration: none;
    font-size: 1em; /* Larger font size for the link */
}

.problem-signin:hover {
    text-decoration: underline;
}
</style>
<body>
    <div class="container">
        <img src="img/logo.png" alt="Logo" class="logo"> <!-- Replace 'logo.png' with your logo file -->
        <form class="login-form" method="POST" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button id='login-btn' type="submit" class="sign-in-button">Sign In</button>
            <a href="#" class="problem-signin">Problem Signing In?</a>
        </form>
    </div>
</body>
</html>