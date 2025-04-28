<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Ensure session variables exist
if (!isset($_SESSION['department_ids']) || !isset($_SESSION['student_id'])) {
    die("Session data is missing. Please log in again.");
}

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exceptional Circumstance & Disability Request</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .disability-form,
    .ec-form {
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
        margin: 20px auto;
    }

    .disability-form h2,
    .ec-form h2 {
        margin-bottom: 20px;
        font-size: 24px;
        color: #333;
    }

    .disability-form label,
    .ec-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .disability-form input,
    .disability-form select,
    .disability-form textarea,
    .ec-form input,
    .ec-form select,
    .ec-form textarea {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
        box-sizing: border-box;
    }

    .disability-form textarea,
    .ec-form textarea {
        resize: vertical;
        height: 150px;
    }

    .form-group.submit-button {
        text-align: center;
        margin-top: 20px;
    }

    /* .btn {
        background-color: #007bff;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    .btn:hover {
        background-color: #0056b3;
    } */
</style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Panel</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li class="active"><a href="disability_request.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Exceptional Circumstance & Disability Request</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    </div>
                    <!-- Logout Button -->
                    <a href="../logout.php" class="logout-btn">
                        <button>Logout</button>
                    </a>
                </div>
            </div>

            <!-- Sidebar Toggle Icon -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <!-- Content Area -->
            <div class="content">
                <!-- Exceptional Circumstances (EC) Request Form -->
                <div class="ec-form">
                    <h2>Exceptional Circumstances (EC) Request</h2>
                    <form action="submit_ec_request.php" method="POST" enctype="multipart/form-data">
                        <label for="ecType">Type of Exceptional Circumstance:</label>
                        <select id="ecType" name="ecType" required>
                            <option value="">Select EC Type</option>
                            <option value="medical">Medical</option>
                            <option value="personal">Personal</option>
                            <option value="family">Family</option>
                            <option value="other">Other</option>
                        </select>

                        <label for="ecDescription">Description of Exceptional Circumstance:</label>
                        <textarea id="ecDescription" name="ecDescription" placeholder="Describe your exceptional circumstance (e.g., illness, bereavement)" required></textarea>

                        <label for="ecSupportingDocument">Upload Supporting Document:</label>
                        <input type="file" id="ecSupportingDocument" name="ecSupportingDocument" accept=".pdf,.doc,.docx">
                        
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Submit EC Request</button>
                        </div>
                    </form>
                </div>
                <div class="disability-form">
                <h2>Disability Advisory Service (DAS) Request</h2>

                    <form action="submit_disability_request.php" method="POST" enctype="multipart/form-data">
                        <label for="disabilityType">Type of Disability:</label>
                        <select id="disabilityType" name="disabilityType" required>
                            <option value="">Select Disability Type</option>
                            <option value="visual">Visual Impairment</option>
                            <option value="hearing">Hearing Impairment</option>
                            <option value="mobility">Mobility Impairment</option>
                            <option value="learning">Learning Disability</option>
                            <option value="other">Other</option>
                        </select>

                        <label for="accommodationRequest">Requested Accommodation:</label>
                        <textarea id="accommodationRequest" name="accommodationRequest" placeholder="Describe the accommodation you need (e.g., extended exam time, special seating)" required></textarea>

                        <label for="supportingDocument">Upload Supporting Document:</label>
                        <input type="file" id="supportingDocument" name="supportingDocument" accept=".pdf,.doc,.docx">
                        
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Submit DAS Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript for sidebar toggle (if needed)
        document.getElementById('sidebar-toggle').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
    <?php
// Flash message logic
if (isset($_SESSION['alert_message'])) {
    $type = $_SESSION['alert_type'] ?? 'success'; // default to success
    $message = $_SESSION['alert_message'];

    echo "<script>
        Swal.fire({
            icon: '$type',
            title: '". ($type === 'success' ? 'Success!' : 'Oops...') ."',
            text: '$message',
            confirmButtonColor: '#3085d6',
        });
    </script>";

    unset($_SESSION['alert_message'], $_SESSION['alert_type']);
}
?>
</body>
</html>