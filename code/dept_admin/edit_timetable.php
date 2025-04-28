<?php
// Include the database connection
require_once '../db_connect.php';
require_once '../auth.php';

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

$pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database

// Fetch the selected timetable entry
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['module_id'])) {
    $moduleId = $_GET['module_id']; // Get the module_id from the URL
    $entry = [];
    try {
        $stmt = $pdo->prepare("
             SELECT
            mt.timetable_id,
            mt.module_id,
            m.module_name,
            mt.staff_id,
            CONCAT(s.first_name, ' ', s.last_name) AS lecturer_name,
            mt.type,
            mt.start_time,
            mt.end_time,
            mt.location,
            STRING_AGG(mt.date::text, ', ' ORDER BY mt.date) AS dates
        FROM module_timetable mt
        JOIN modules m ON mt.module_id = m.module_id
        JOIN staff s ON mt.staff_id = s.staff_id
        GROUP BY mt.timetable_id, mt.module_id, m.module_name, lecturer_name, mt.staff_id, mt.type, mt.start_time, mt.end_time, mt.location;
        ");
        $stmt->execute([':module_id' => $moduleId]); // Bind the parameter correctly
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching timetable entry: " . $e->getMessage();
    }
}

// Handle form submission for updating the entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = $_POST['module_id'];
    $staffId = $_POST['lecturer'];
    $type = $_POST['type'];
    $startTime = $_POST['start-time'];
    $endTime = $_POST['end-time'];
    $location = $_POST['location'];
    $dates = explode(',', $_POST['dates']); // Split dates into an array

    try {
       
        header("Location: edit_timetable.php"); // Redirect back to the timetable page
    } catch (PDOException $e) {
        echo "Error updating timetable entry: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Timetable Entry</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Department Admin Portal</span>
            </div>
            <ul class="nav">
                <li><a href="dept_admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i> Staff</a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i> Enrolment</a></li>       
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i> Programmes</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li class="active"><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Edit Timetable</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
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
               <a href="timetable.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Timetable
               </a>
            <div class="container">
                <h1>Edit Timetable Entry</h1>
                <form method="POST" action="edit_timetable.php">
                    <!-- Hidden field for module_id -->
                    <input type="hidden" name="module_id" value="<?php echo $entry['module_id']; ?>">

                    <!-- Module Name (Read-only) -->
                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" id="module_name" name="module_name" value="<?php echo htmlspecialchars($entry['module_name']); ?>" readonly>
                    </div>

                    <!-- Lecturer Dropdown -->
                    <div class="form-group">
                        <label for="lecturer">Lecturer</label>
                        <select id="lecturer" name="lecturer" required>
                            <?php
                            // Fetch lecturers for the dropdown
                            $lecturers = [];
                            try {
                                $stmt = $pdo->query("SELECT staff_id, CONCAT(first_name, ' ', last_name) AS lecturer_name FROM staff");
                                $lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                echo "Error fetching lecturers: " . $e->getMessage();
                            }
                            foreach ($lecturers as $lecturer): ?>
                                <option value="<?php echo $lecturer['staff_id']; ?>" <?php echo ($lecturer['staff_id'] == $entry['staff_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lecturer['lecturer_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type Dropdown (Lecture or Tutorial) -->
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="Lecture" <?php echo ($entry['type'] == 'Lecture') ? 'selected' : ''; ?>>Lecture</option>
                            <option value="Tutorial" <?php echo ($entry['type'] == 'Tutorial') ? 'selected' : ''; ?>>Tutorial</option>
                        </select>
                    </div>

                    <!-- Start Time -->
                    <div class="form-group">
                        <label for="start-time">Start Time</label>
                        <input type="time" id="start-time" name="start-time" value="<?php echo htmlspecialchars($entry['start_time']); ?>" required>
                    </div>

                    <!-- End Time -->
                    <div class="form-group">
                        <label for="end-time">End Time</label>
                        <input type="time" id="end-time" name="end-time" value="<?php echo htmlspecialchars($entry['end_time']); ?>" required>
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($entry['location']); ?>" required>
                    </div>

                    <!-- Multi-Date Picker -->
                    <div class="form-group">
                        <label for="date-picker">Dates</label>
                        <input type="text" id="date-picker" class="flatpickr-input" name="dates" value="<?php echo htmlspecialchars($entry['dates']); ?>" placeholder="Select Dates" readonly>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn">Update</button>
                </form>
            </div>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Flatpickr for multi-date selection
        const datePicker = flatpickr("#date-picker", {
            mode: "multiple", // Enable multi-date selection
            dateFormat: "Y-m-d", // Date format
            defaultDate: "<?php echo $entry['dates']; ?>".split(', '), // Set default dates
            placeholder: "Select Dates", // Placeholder text
        });
    </script>
</body>
</html>