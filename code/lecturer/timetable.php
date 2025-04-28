<?php
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has staff role
check_role(STAFF);

// Get the staff ID from the session
$staffId = $_SESSION['staff_id'];

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Array to store all modules taught by this staff and timetable events
$taughtModules = [];
$events = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch modules taught by this staff member
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.module_id, m.module_name
        FROM assigned_lecturers ms
        JOIN modules m ON ms.module_id = m.module_id
        WHERE ms.staff_id = :staff_id
    ");
    $stmt->execute(['staff_id' => $staffId]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge the modules into the $taughtModules array
    $taughtModules = array_merge($taughtModules, $modules);

    // Fetch timetable events for the taught modules
    foreach ($modules as $module) {
        $stmt = $pdo->prepare("
            SELECT 
                timetable_id, 
                module_id, 
                date, 
                start_time, 
                end_time, 
                location, 
                type
            FROM module_timetable
            WHERE module_id = :module_id
            AND staff_id = :staff_id
            ORDER BY date, start_time
        ");
        $stmt->execute([
            'module_id' => $module['module_id'],
            'staff_id' => $staffId
        ]);
        $events = array_merge($events, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// Fetch the staff member's name from the first department
$pdo = getDatabaseConnection(strtolower($departmentIds[0]));
$stmt = $pdo->prepare("
    SELECT first_name, last_name 
    FROM staff 
    WHERE staff_id = :staff_id
");
$stmt->execute(['staff_id' => $staffId]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$staffName = $staff ? $staff['first_name'] . ' ' . $staff['last_name'] : 'Staff Member';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li class="active"><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Weekly Timetable</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($staffName); ?></span>
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
                
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="month-navigation">
                        <button class="nav-button" id="prevWeek"><i class="fas fa-chevron-left"></i></button>
                        <h2 id="currentWeek"></h2>
                        <button class="nav-button" id="nextWeek"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>

            <script>
                function getMondayOfCurrentWeek(d) {
                    d = new Date(d);
                    const day = d.getDay();
                    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                    return new Date(d.setDate(diff));
                }

                let currentStartDate = getMondayOfCurrentWeek(new Date());

                // Define events from PHP
                const events = <?php echo json_encode($events); ?>;

                function updateCalendar() {
                    const weekDays = [];
                    const date = new Date(currentStartDate);
                    for (let i = 0; i < 5; i++) {
                        weekDays.push(new Date(date));
                        date.setDate(date.getDate() + 1);
                    }

                    const calendarGrid = document.getElementById('calendarGrid');
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    // Update header
                    document.getElementById('currentWeek').textContent = 
                        `Week of ${currentStartDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}`;

                    // Generate grid
                    calendarGrid.innerHTML = '';
                    
                    // Create headers
                    calendarGrid.innerHTML += `<div class="time-column"></div>`;
                    weekDays.forEach(day => {
                        const dayHeader = day.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: '2-digit' });
                        calendarGrid.innerHTML += `
                            <div class="day-header">${dayHeader}</div>`;
                    });

                    // Create time slots (9:00-18:00 in 24h format)
                    for (let hour = 9; hour <= 18; hour++) {
                        calendarGrid.innerHTML += `<div class="time-column">${hour.toString().padStart(2, '0')}:00</div>`;
                        
                        weekDays.forEach((day, index) => {
                            const currentDay = new Date(day);
                            currentDay.setHours(0, 0, 0, 0);
                            const isToday = currentDay.getTime() === today.getTime();
                            const cellClass = isToday ? 'calendar-cell today' : 'calendar-cell';
                            calendarGrid.innerHTML += `
                                <div class="${cellClass}" 
                                     data-date="${day.toISOString().split('T')[0]}" 
                                     data-hour="${hour}">
                                </div>`;
                        });
                    }

                    // Render events
                    events.forEach(event => {
                        const eventDate = new Date(event.date);
                        const dayIndex = weekDays.findIndex(day => day.toDateString() === eventDate.toDateString());
                        if (dayIndex !== -1) {
                            const startHour = parseInt(event.start_time.split(':')[0]);
                            const endHour = parseInt(event.end_time.split(':')[0]);
                            const duration = endHour - startHour;

                            // Create event div
                            const eventDiv = document.createElement('div');
                            eventDiv.className = `event ${event.type === 'lecture' ? 'lecture' : 'tutorial'}`;
                            eventDiv.style.gridRow = `${startHour - 9 + 1} / span ${duration}`;
                            eventDiv.innerHTML = `
                                <div class="event-code">${event.module_id}</div>
                                <div class="event-location">${event.location}</div>
                            `;

                            // Append to the correct cell
                            const cell = calendarGrid.children[(startHour - 9 + 1) * 6 + dayIndex + 1]; // Calculate cell index
                            cell.appendChild(eventDiv);
                            
                            // If the event spans multiple hours, create additional divs for the remaining hours
                            for (let hour = startHour + 1; hour < endHour; hour++) {
                                const nextCell = calendarGrid.children[(hour - 9 + 1) * 6 + dayIndex + 1];
                                const additionalDiv = document.createElement('div');
                                additionalDiv.className = `event ${event.type === 'lecture' ? 'lecture' : 'tutorial'}`;
                                additionalDiv.style.gridRow = `${hour - 9 + 1}`;
                                additionalDiv.style.height = '100%'; // Make it fill the cell
                                additionalDiv.innerHTML = `
                                    <div class="event-code">${event.module_id}</div>
                                    <div class="event-location">${event.location}</div>
                                `;
                                nextCell.appendChild(additionalDiv);
                            }
                        }
                    });
                }

                // Navigation controls
                document.getElementById('prevWeek').addEventListener('click', () => {
                    currentStartDate.setDate(currentStartDate.getDate() - 7);
                    updateCalendar();
                });

                document.getElementById('nextWeek').addEventListener('click', () => {
                    currentStartDate.setDate(currentStartDate.getDate() + 7);
                    updateCalendar();
                });

                // Initialize currentStartDate to the Monday of the current week
                currentStartDate = getMondayOfCurrentWeek(new Date());
                updateCalendar();
            </script>
        </div>
    </div>
</body>
</html>