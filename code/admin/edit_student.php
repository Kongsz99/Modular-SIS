<?php
require_once '../db_connect.php'; // Include the database connection script
require_once '../auth.php'; // Include the functions file
// Ensure the user is an admin

check_role(required_role: GLOBAL_ADMIN);
// Get database connections
$pdo_cs = getDatabaseConnection('cs');
$pdo_bm = getDatabaseConnection('bm');

// Fetch student details
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    die("Student ID not provided.");
}

// Determine which database to use based on the student's department
$sql = "
    SELECT 
        s.*,
        d.department_name,
        p.programme_name,
        sp.current_year
    FROM 
        students s
    JOIN 
        programme_enrolment sp ON s.student_id = sp.student_id
    JOIN 
        programme p ON sp.programme_id = p.programme_id
    JOIN 
        user_department ud ON s.user_id = ud.user_id
    JOIN 
        departments d ON ud.department_id = d.department_id
    WHERE 
        s.student_id = :student_id
";

$stmt = $pdo_cs->prepare($sql);
$stmt->bindValue(':student_id', $student_id);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: student.php"); // Redirect if student not found
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $nationality = $_POST['nationality'];
    $student_type = $_POST['student_type'];
    $personal_email = $_POST['personal_email'];
    $phone = $_POST['phone'];
    $programme_name = $_POST['programme_name'];
    $current_year = $_POST['current_year'] ?? '';
    $status = $_POST['status'];

    // Update student status and current year
    $updateSql = "
        UPDATE students
        SET first_name = :first_name,
            last_name = :last_name,
            gender = :gender,
            date_of_birth = :date_of_birth,
            nationality = :nationality,
            student_type = :student_type,
            personal_email = :personal_email,
            phone = :phone,
            status = :status
        WHERE student_id = :student_id;
        
        UPDATE programme_enrolment
        SET programme_id = :programme_id,
            current_year = :current_year
        WHERE student_id = :student_id;
    ";

    $pdo_cs->beginTransaction();
    try {
        $stmt = $pdo_cs->prepare($updateSql);
        $stmt->bindValue(':first_name', $firstName);
        $stmt->bindValue(':last_name', $lastName);
        $stmt->bindValue(':gender', $gender);
        $stmt->bindValue(':date_of_birth', $date_of_birth);
        $stmt->bindValue(':nationality', $nationality);
        $stmt->bindValue(':student_type', $student_type);
        $stmt->bindValue(':personal_email', $personal_email);
        $stmt->bindValue(':phone', $phone);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':programme_id', $programme_id); // Assuming $programme_name is the ID
        $stmt->bindValue(':current_year', $current_year);
        $stmt->bindValue(':student_id', $student_id);
        $stmt->execute();
        
        $pdo_cs->commit();
    } catch (PDOException $e) {
        $pdo_cs->rollBack();
        die("Error updating student: " . $e->getMessage());
    }

    // Redirect back to the student list page
    header(header: "Location: student.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="template/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<script>
    // Function to fetch programmes based on the selected department
    function fetchProgrammes(departmentSelectId, programmeSelectId) {
        const departmentSelect = document.getElementById(departmentSelectId);
        const programmeSelect = document.getElementById(programmeSelectId);

        // Get the selected department ID
        const selectedDepartmentId = departmentSelect.value;

        // Clear the programme dropdown
        programmeSelect.innerHTML = '<option value="">Select Programme</option>';

        // If no department is selected, return
        if (!selectedDepartmentId) {
            return;
        }

        // Make an AJAX request to fetch programmes
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_programmes.php?department_id=${selectedDepartmentId}`, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                const programmes = JSON.parse(xhr.responseText);

                // Populate the programme dropdown
                programmes.forEach(programme => {
                    const option = document.createElement('option');
                    option.value = programme.programme_id;
                    option.textContent = programme.programme_name;
                    programmeSelect.appendChild(option);
                });
            } else {
                console.error('Error fetching programmes:', xhr.statusText);
            }
        };
        xhr.onerror = function () {
            console.error('Error fetching programmes:', xhr.statusText);
        };
        xhr.send();
    }
</script>
<body>
<div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </div>
            <ul class="nav">
                <li><a href="global_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li class="active"><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Edit Student</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
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
                <!-- Back Button -->
                <a href="student.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Student List
                </a>
                
                <!-- Edit Student Form -->
                <div class="form-container">
                    <form method="POST" action="edit_student.php?id=<?php echo $studentId; ?>">
                        <!-- Personal Details -->
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $student['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth</label>
                                        <input type="date" id="date_of_birth" name="date_of_birth"  value="<?php echo htmlspecialchars($student['date_of_birth']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="nationality">Nationality</label>
                                        <select id="nationality" name="nationality" required>
                                            <option value="">Select Nationality</option>
                                            <option value="Malaysian" <?php echo $student['nationality'] === 'Malaysian' ? 'selected' : ''; ?>>Malaysian</option>
                                            <option value="Singaporean" <?php echo $student['nationality'] === 'Singaporean' ? 'selected' : ''; ?>>Singaporean</option>
                                            <option value="British" <?php echo $student['nationality'] === 'British' ? 'selected' : ''; ?>>British</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="student_type">Student Type</label>
                                        <select id="student_type" name="student_type" required>
                                        <option value="international" <?php echo $student['student_type'] === 'international' ? 'selected' : ''; ?>>International</option>
                                        <option value="local" <?php echo $student['student_type'] === 'local' ? 'selected' : ''; ?>>Local</option>
                                        </select>
                                    </div>
                        <div class="form-group">
                            <label for="personal_email">Personal Email</label>
                            <input type="email" id="personal_email" name="personal_email" value="<?php echo htmlspecialchars($student['personal_email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="programme_name">Programme</label>
                                <select id="programme_name" name="programme_name" required>
                                    <option value="">Select Programme</option>
                                    <option value="UNCS01" <?php echo $student['programme_name'] === 'Bachelor of Computer Science' ? 'selected' : ''; ?>>Barchelor of Computer Science</option>
                                </select>
                        </div>
                                                 
                        <div class="form-group">
                            <label for="current_year">Current Year:</label>
                            <input type="number" id="current_year" name="current_year" value="<?php echo htmlspecialchars($student['current_year']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="completed" <?php echo $student['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="suspended" <?php echo $student['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="withdrawn" <?php echo $student['status'] === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                            </select>
                        </div>
                        <div class="form-group submit-button">
                            <button type="submit" class="btn">Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>