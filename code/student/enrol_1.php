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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledgment-policies'])) {
    // Loop through each department ID
    foreach ($departmentIds as $departmentId) {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Update the student's progress_step to 1 and status to 'in-progress'
        $stmt = $pdo->prepare("
            UPDATE programme_enrolment 
            SET progress_step = 1, status = 'in-progress' 
            WHERE student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);

        // Check if the update was successful
        if ($stmt->rowCount() === 0) {
            die("❌ Error: Failed to update enrollment for department ID: $departmentId.");
        }
    }

    // Redirect to the next page
    header('Location: enrol_2.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Rules and Regulations</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        #nextButton {
            transition: opacity 0.3s ease;
        }
    </style>
    <script>
        // JavaScript to show/hide the "Next" button based on checkbox state
        function toggleNextButton() {
            const checkbox = document.getElementById('acknowledgment-policies');
            const nextButton = document.getElementById('nextButton');

            if (checkbox.checked) {
                nextButton.style.display = 'inline-block';
                // Save the checkbox state in session storage
                sessionStorage.setItem('acknowledged', 'true');
            } else {
                nextButton.style.display = 'none';
                // Remove the checkbox state from session storage
                sessionStorage.removeItem('acknowledged');
            }
        }
    </script>
</head>
<body class="enrol-page">

    <div class="container">
        <h1>Enrollment Rules and Regulations</h1>

        <h2>1. General Enrollment Guidelines</h2>
        <ul>
            <li>All students must complete the enrollment process before the start of the semester.</li>
            <li>Students are required to provide accurate personal and academic information during enrollment.</li>
            <li>Enrollment is subject to the availability of courses and seats.</li>
        </ul>

        <h2>2. Eligibility Criteria</h2>
        <ul>
            <li>Students must meet the minimum academic requirements set by the university.</li>
            <li>All prerequisite courses must be completed before enrolling in advanced courses.</li>
            <li>Transfer students must provide official transcripts from previous institutions.</li>
        </ul>

        <h2>3. Enrollment Process</h2>
        <ul>
            <li>Students must log in to the university portal to access the enrollment system.</li>
            <li>Course selection must be done within the designated enrollment period.</li>
            <li>Students should consult with academic advisors for course recommendations.</li>
        </ul>

        <h2>4. Payment of Fees</h2>
        <ul>
            <li>All tuition and fees must be paid by the enrollment deadline.</li>
            <li>Payment plans may be available for eligible students.</li>
            <li>Failure to pay fees may result in the cancellation of enrollment.</li>
        </ul>

        <h2>5. Important Dates</h2>
        <ul>
            <li>Enrollment Period: January 1 - January 15</li>
            <li>Last Day to Add/Drop Courses: January 22</li>
            <li>Tuition Payment Deadline: January 30</li>
        </ul>

        <h2>Acknowledgment of Rules and Regulations</h2>
        <form method="POST" action="enrol_1.php">
            <p>
                By confirming this document, I acknowledge that I have read, understood, and agree to comply with all the rules and regulations outlined above. I also confirm that I will adhere to all university policies during my time at Enzo University.
            </p>
            <p>
                I understand that failure to comply with these terms may result in disciplinary action, including the possible suspension or expulsion from the university.
            </p>

            <div class="checkbox-container">
                <input type="checkbox" id="acknowledgment-policies" name="acknowledgment-policies" onchange="toggleNextButton()">
                <label for="acknowledgment-policies">I Agree</label>
            </div>

            <div class="footer">
                <a href="student_dashboard.php"><button type="button" class="btn">Back</button></a>
                <button type="submit" id="nextButton" class="btn" style="display: none;">Next</button>
            </div>
        </form>
    </div>

</body>
</html>