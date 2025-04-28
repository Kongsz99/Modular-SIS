<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the student ID from the session
$studentId = $_SESSION['student_id'];

// Get the user's department IDs from the session (assuming it's an array)
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Array to store student details from all departments
$students = [];

// Loop through each department ID to fetch student details
foreach ($departmentIds as $departmentId) {
    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch the student's personal details from the database
    $stmt = $pdo->prepare("
        SELECT s.*, CONCAT(a.address, ', ', a.city, ', ', a.state, ', ', a.postcode, ', ', a.country) AS full_address
        FROM students s
        JOIN address a ON s.address_id = a.address_id
        WHERE s.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Student not found in department ID: $departmentId.");
    }

    // Add department ID to the student data
    $student['department_id'] = $departmentId;

    // Store student details in the array
    $students[] = $student;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the checkbox is ticked
    $acknowledged = isset($_POST['personal-details-correct']) ? 1 : 0;

    // Save the checkbox state in the session
    $_SESSION['personal_details_acknowledged'] = $acknowledged;

    // Update the student's progress_step to 2 for all departments if the checkbox is ticked
    if ($acknowledged) {
        foreach ($departmentIds as $departmentId) {
            // Connect to the department's database
            $pdo = getDatabaseConnection(strtolower($departmentId));

            // Update the progress_step to 2
            $stmt = $pdo->prepare("UPDATE programme_enrolment SET progress_step = 2 WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $studentId]);

            // Check if the update was successful
            if ($stmt->rowCount() === 0) {
                die("❌ Error: Failed to update enrollment for department ID: $departmentId.");
            }
        }

        // Redirect to the next page
        header('Location: enrol_3.php');
        exit();
    }
}

// Fetch the student's enrollment status from the first department (for display purposes)
$pdo = getDatabaseConnection(strtolower($departmentIds[0]));
$stmt = $pdo->prepare("SELECT progress_step FROM programme_enrolment WHERE student_id = :student_id");
$stmt->execute(['student_id' => $studentId]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve the checkbox state from the session
$acknowledged = $_SESSION['personal_details_acknowledged'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Details - Enrollment</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        // JavaScript to show/hide the "Next" button based on checkbox state
        function toggleNextButton() {
            const checkbox = document.getElementById('personal-details-correct');
            const nextButton = document.getElementById('nextButton');

            if (checkbox.checked) {
                nextButton.style.display = 'inline-block';
            } else {
                nextButton.style.display = 'none';
            }
        }

        // Initialize the checkbox state on page load
        window.onload = function() {
            const checkbox = document.getElementById('personal-details-correct');
            const nextButton = document.getElementById('nextButton');

            // Set the checkbox state from the session
            checkbox.checked = <?php echo $acknowledged ? 'true' : 'false'; ?>;

            // Update the button visibility
            toggleNextButton();
        };
    </script>
</head>
<body class="enrol-page">

    <div class="container">
        <h1>Personal Details</h1>
        <h2>Personal Information</h2>

        <!-- Display student's personal details -->
        <div class="form-group">
            <label for="full-name">Full Name:</label>
            <input type="text" id="full-name" name="full-name" value="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="student-id">Student ID:</label>
            <input type="text" id="student-id" name="student-id" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($student['full_address']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="email">Personal Email:</label>
            <input type="email" id="pemail" name="pemail" value="<?php echo htmlspecialchars($student['personal_email']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="email">University Email:</label>
            <input type="email" id="uemail" name="uemail" value="<?php echo htmlspecialchars($student['uni_email']); ?>" readonly>
        </div>

        <!-- Confirmation Checkbox -->
        <form method="POST" action="enrol_2.php">
            <div class="checkbox-container">
                <input type="checkbox" id="personal-details-correct" name="personal-details-correct" onchange="toggleNextButton()">
                <label for="personal-details-correct">Yes, the personal details are correct. 
                    <br> If not, <a href="#">email here</a>
                </label>
            </div>

            <!-- Navigation Buttons -->
            <div class="footer">
                <a href="enrol_1.php"><button type="button" class="btn">Back</button></a>
                <?php if ($enrollment['progress_step'] >= 1): ?>
                    <button type="submit" id="nextButton" class="btn" style="display: none;">Next</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

</body>
</html>