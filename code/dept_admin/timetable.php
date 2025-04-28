<?php
// Include the database connection
require_once '../db_connect.php';
require_once '../auth.php';

// Check if the user is logged in and has the appropriate role
check_role(required_role: DEPARTMENT_ADMIN); // Adjust the role as needed

// Get the user's department ID from the session
$departmentId = $_SESSION['department_id'];

// Ensure the department ID is valid
if (empty($departmentId)) {
    die("You are not associated with any department.");
}

$pdo = getDatabaseConnection(strtolower($departmentId)); // Connect to the department's database

// Fetch modules for the dropdown
$modules = [];
try {
    $stmt = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_id ASC");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching modules: " . $e->getMessage();
}

// Fetch lecturers for the dropdown
$lecturers = [];
try {
    $stmt = $pdo->query("SELECT staff_id, CONCAT(first_name, ' ', last_name) AS full_name FROM staff");
    $lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching lecturers: " . $e->getMessage();
}

// Fetch timetable entries
$timetableEntries = [];
try {
    $stmt = $pdo->query("
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
        GROUP BY mt.timetable_id, mt.module_id, m.module_name, lecturer_name, mt.staff_id, mt.type, mt.start_time, mt.end_time, mt.location 
        ORDER BY mt.date;
    ");
    $timetableEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching timetable entries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Add styles for the date input and table */
        .flatpickr-input {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            margin-bottom: 10px;
        }
        .timetable-entries td {
            vertical-align: top;
        }
    </style>
</head>
<body>
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
                    <h1>Modules Timetable Management</h1>
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
                <!-- Module Timetable Section -->
                <h2 class="list-subtitle">Create Timetable Here</h2>
                <div class="content">
                    <!-- Assign Lecturer to Module Form -->
                    <div class="search-filter">
                        <form id="assign-lecturer-form" method="POST" action="save_timetable.php">
                            <!-- Module Dropdown -->
                            <select id="module" name="module" required>
                                <option value="">Select a Module</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?php echo $module['module_id']; ?>">
                                        <?php echo htmlspecialchars($module['module_id'] . ' - ' . $module['module_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Lecturer Dropdown -->
                            <select id="lecturer" name="lecturer" required>
                                <option value="">Select a Lecturer</option>
                                <?php foreach ($lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['staff_id']; ?>">
                                        <?php echo htmlspecialchars($lecturer['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Type Dropdown (Lecture or Tutorial) -->
                            <select id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Lecture">Lecture</option>
                                <option value="Tutorial">Tutorial</option>
                            </select>

                            <!-- Start Time -->
                            <input type="time" id="start-time" name="start-time" required>

                            <!-- End Time -->
                            <input type="time" id="end-time" name="end-time" required>

                            <!-- Location -->
                            <input type="text" id="location" name="location" placeholder="Location (e.g., Room 101)" required>

                            <!-- Multi-Date Picker -->
                            <input type="text" id="date-picker" class="flatpickr-input" name="dates" placeholder="Select Dates" readonly>

                            <button type="submit" class="btn" id="assign-btn">Add Timetable Entry</button>
                        </form>
                    </div>
                    <!-- Timetable Entries Table -->
                    <h3>List of Timetable Entries</h3>

                    <div class="search-filter">
                        <select id="filter-module">
                            <option value="">All Modules</option>
                            <?php
                            // Fetch module_ids for the filter dropdown
                            $stmt = $pdo->query("SELECT DISTINCT module_id FROM module_timetable ORDER BY module_id");
                            $moduleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($moduleIds as $module_id): ?>
                                <option value="<?php echo htmlspecialchars($module_id); ?>">
                                    <?php echo htmlspecialchars($module_id); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn" onclick="applyFilters()">Apply</button>
                    </div>
                    <table id="timetable-entries" class="timetable-entries">
                        <thead>
                            <tr>
                                <th>Module ID</th>
                                <th>Module Name</th>
                                <th>Lecturer</th>
                                <th>Type</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Location</th>
                                <th>Dates</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timetableEntries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['module_id']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['lecturer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['type']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['location']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['dates']); ?></td>
                                    <td>
                                        <button class="btn-edit" onclick="editTimetableEntry('<?php echo $entry['timetable_id']; ?>')"><i class="fas fa-edit"></i></button>
                                        <button class="btn-delete" onclick="deleteTimetableEntry('<?php echo $entry['timetable_id']; ?>')"><i class='fas fa-trash-alt'></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Flatpickr for multi-date selection
        const datePicker = flatpickr("#date-picker", {
            mode: "multiple", // Enable multi-date selection
            dateFormat: "Y-m-d", // Date format
            placeholder: "Select Dates", // Placeholder text
        });

        function applyFilters() {
            const moduleFilter = document.getElementById('filter-module').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const module_id = row.cells[0].textContent.toLowerCase();

                const matchesModule = moduleFilter === '' || module_id === moduleFilter;

                if (matchesModule) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        function editTimetableEntry(timetable_id) {
            // Fetch the existing data for the selected module (you can use AJAX or redirect to an edit page)
            window.location.href = `edit_timetable.php?timetable_id=${timetable_id}`;
        }

        function deleteTimetableEntry(timetable_id) {
            if (confirm("Are you sure you want to delete this timetable entry?")) {
                window.location.href = `delete_timetable.php?timetable_id=${timetable_id}`;
            }
        }
    </script>
</body>
</html>