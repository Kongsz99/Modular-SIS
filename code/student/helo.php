<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role (STUDENT)
check_role(STUDENT);

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

// Connect to the department's database
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch student details
$studentId = $_SESSION['user_id']; // Assuming the student's ID is stored in the session
$studentQuery = "
    SELECT CONCAT(s.first_name, ' ', s.last_name) AS student_name, 
           p.programme_name, 
           st.programme_end_date 
    FROM students s
    JOIN student_programme st ON s.student_id = st.student_id
    JOIN programme p ON st.programme_id = p.programme_id
    WHERE s.student_id = :student_id;
";
$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->execute(['student_id' => $studentId]);
$studentDetails = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if student data is found
if (empty($studentDetails)) {
    die("❌ No student data found.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- You can add your CSS styles here -->
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($studentDetails[0]['student_name']); ?></h1>
    
    <h2>Your Enrolled Programmes</h2>
    
    <table>
        <thead>
            <tr>
                <th>Programme Name</th>
                <th>Programme End Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($studentDetails as $student) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['programme_name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['programme_end_date']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
