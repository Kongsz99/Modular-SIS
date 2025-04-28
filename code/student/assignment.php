<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Array to store assignments for each department
$departmentAssignments = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details using the student_id stored in the session
        $studentId = $_SESSION['student_id'];

        // Fetch the student's assignments from the database
        $stmt = $pdo->prepare("
          SELECT DISTINCT
            a.assignment_id,
            a.title,
            a.due_date,
            m.assignment_weight,
            sm.module_id,
            m.module_name,
            s.status
        FROM assignment a
        JOIN modules m ON a.module_id = m.module_id
        JOIN student_modules sm ON sm.module_id = m.module_id
        LEFT JOIN submission s ON a.assignment_id = s.assignment_id AND s.student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Store assignments for this department
        $departmentAssignments[] = [
            'department_id' => $departmentId,
            'assignments' => $assignments,
        ];
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Portal</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add these new styles below existing ones */
        .assignment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .assignment-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            border-left: 4px solid #3d84d0;
        }

        .assignment-card:hover {
            transform: translateY(-2px);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .assignment-title {
            font-size: 1.2em;
            color: #2c3e50;
            margin: 0;
        }

        .assignment-meta {
            margin: 10px 0;
            color: #666;
        }

        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-not-started { background: #f0f0f0; color: #666; }
        .status-in-progress { background: #fff3cd; color: #856404; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d4edda; color: #155724; }

        .assignment-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .deadline-warning {
            color: #dc3545;
            font-weight: 500;
            margin: 10px 0;
        }

        .grade-percentage {
            font-size: 1.1em;
            color: #3d84d0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Student Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li class="active"><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li><a href="disability_request.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Assignment</h1>
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

            <!-- Sidebar Toggle -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <div class="content">
                <div class="person-details-container">
                    <?php foreach ($departmentAssignments as $department): ?>
                        <h2>Assignments (Department: <?php echo htmlspecialchars($department['department_id']); ?>)</h2>
                        <div class="assignment-grid">
                            <?php foreach ($department['assignments'] as $assignment): ?>
                                <?php
                                // Calculate the status and deadline warning
                                $dueDate = new DateTime($assignment['due_date']);
                                $currentDate = new DateTime();
                                $daysRemaining = $currentDate->diff($dueDate)->days;

                                if ($assignment['status'] === 'submitted') {
                                    $statusClass = 'status-completed';
                                    $statusText = 'Submitted';
                                } elseif ($dueDate < $currentDate) {
                                    $statusClass = 'status-overdue';
                                    $statusText = 'Overdue';
                                } elseif ($daysRemaining <= 80) {
                                    $statusClass = 'status-in-progress';
                                    $statusText = 'In Progress';
                                } else {
                                    $statusClass = 'status-not-started';
                                    $statusText = 'Not Started';
                                }
                                ?>
                                <div class="assignment-card" data-assignment-id="<?= $assignment['assignment_id'] ?>">
                                    <div class="assignment-header">
                                        <h3 class="assignment-title"><?= htmlspecialchars($assignment['title']) ?></h3>
                                        <span class="status-indicator <?= $statusClass ?>"><?= $statusText ?></span>
                                    </div>
                                    <div class="assignment-meta">
                                        <p><i class="fas fa-calendar-alt"></i> Due: <?= htmlspecialchars($dueDate->format('M j, Y')) ?></p>
                                        <p><i class="fas fa-book"></i> Module: <?= htmlspecialchars($assignment['module_name']) ?></p>
                                        <p class="grade-percentage">Weight: <?= htmlspecialchars($assignment['assignment_weight']) ?>%</p>
                                    </div>
                                    <?php if ($statusClass === 'status-overdue'): ?>
                                        <p class="deadline-warning"><i class="fas fa-exclamation-triangle"></i> <?= $daysRemaining ?> days overdue</p>
                                    <?php elseif ($statusClass === 'status-in-progress'): ?>
                                        <p class="deadline-warning"><i class="fas fa-exclamation-triangle"></i> <?= $daysRemaining ?> days remaining</p>
                                    <?php endif; ?>
                                    <div class="assignment-actions">
                                        <button class="btn view-details">Details</button>
                                        <?php if ($statusClass !== 'status-completed'): ?>
                                            <button class="btn submit-assignment"><?= $statusClass === 'status-overdue' ? 'Submit Late' : 'Submit' ?></button>
                                        <?php else: ?>
                                            <button class="btn" disabled><i class="fas fa-check"></i> Submitted</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Submission Modal -->
                    <div id="submissionModal" class="modal">
                        <div class="modal-content">
                            <h3>Submit Assignment: <span id="modalAssignmentTitle"></span></h3>
                            <form id="submissionForm">
                                <input type="hidden" id="assignmentId">
                                <div class="form-group">
                                    <label for="fileUpload">Select File:</label>
                                    <input type="file" id="fileUpload" required>
                                </div>
                                <div class="form-group">
                                    <label>Due Date: <span id="modalDueDate"></span></label>
                                    <p class="grade-percentage">Weight: <span id="modalWeight"></span></p>
                                </div>
                                <button type="submit" class="btn">Confirm Submission</button>
                                <button type="button" class="btn cancel-modal">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.submit-assignment').forEach(button => {
            button.addEventListener('click', () => {
                const card = button.closest('.assignment-card');
                document.getElementById('assignmentId').value = card.dataset.assignmentId;
                document.getElementById('modalAssignmentTitle').textContent = 
                    card.querySelector('.assignment-title').textContent;
                document.getElementById('modalDueDate').textContent = 
                    card.querySelector('.assignment-meta p:first-child').textContent.replace('Due: ', '');
                document.getElementById('modalWeight').textContent = 
                    card.querySelector('.grade-percentage').textContent.replace('Weight: ', '');
                document.getElementById('submissionModal').style.display = 'flex';
            });
        });

        document.querySelector('.cancel-modal').addEventListener('click', () => {
            document.getElementById('submissionModal').style.display = 'none';
        });

        document.getElementById('submissionForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const assignmentId = document.getElementById('assignmentId').value;
            alert(`Assignment ${assignmentId} submitted successfully!`);
            document.getElementById('submissionModal').style.display = 'none';
            // Update UI to show submitted status
            const card = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            card.querySelector('.status-indicator').className = 'status-indicator status-completed';
            card.querySelector('.status-indicator').textContent = 'Submitted';
            card.querySelector('.submit-assignment').disabled = true;
        });
    </script>
</body>
</html>