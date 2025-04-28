<?php
require_once '../db_connect.php';
require_once '../auth.php';

check_role(GLOBAL_ADMIN);

// Function to fetch students from a specific database
function fetchStudentsinCS($dbName) {
    $pdo = getDatabaseConnection($dbName);
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            s.student_id,
            s.first_name,
            s.last_name,
            d.department_name
        FROM 
            students s
        JOIN 
            user_department ud ON s.user_id = ud.user_id
        JOIN 
            departments d ON ud.department_id = d.department_id
        WHERE 
            d.department_name = 'Computer Science'
        ORDER BY s.student_id;
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchStudentsinBM($dbName) {
    $pdo = getDatabaseConnection($dbName);
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            s.student_id,
            s.first_name,
            s.last_name,
            d.department_name
        FROM 
            students s
        JOIN 
            user_department ud ON s.user_id = ud.user_id
        JOIN 
            departments d ON ud.department_id = d.department_id
        WHERE 
            d.department_name = 'Business Management'
        ORDER BY s.student_id;
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch students from the 'cs' database
$csStudents = fetchStudentsinCS('cs');

// Fetch students from the 'bm' database
$bmStudents = fetchStudentsinBM('bm');

// Combine the results into a single array
$students = array_merge($csStudents, $bmStudents);

// Fetch scholarships from the central database
$pdo_central = getDatabaseConnection('central');
$stmt = $pdo_central->prepare("SELECT scholarship_id, scholarship_name, amount FROM scholarship");
$stmt->execute();
$scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch assigned scholarships from a specific database
function fetchAssignedScholarships($dbName) {
    $pdo = getDatabaseConnection($dbName);
    $stmt = $pdo->prepare("
        SELECT sa.student_id, sa.scholarship_id, s.first_name, s.last_name, sc.scholarship_name, sc.amount
        FROM scholarship_assignment sa
        JOIN students s ON sa.student_id = s.student_id
        JOIN scholarship sc ON sa.scholarship_id = sc.scholarship_id
        ORDER BY s.student_id;
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch assigned scholarships from the 'cs' database
$csAssignedScholarships = fetchAssignedScholarships('cs');

// Fetch assigned scholarships from the 'bm' database
$bmAssignedScholarships = fetchAssignedScholarships('bm');

// Combine the results into a single array
$assigned_scholarships = array_merge($csAssignedScholarships, $bmAssignedScholarships);

// Handle scholarship assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $scholarship_id = $_POST['scholarship_id'];

    // Get scholarship amount from central database
    $pdo_central = getDatabaseConnection('central');
    $stmt = $pdo_central->prepare("SELECT amount FROM scholarship WHERE scholarship_id = :scholarship_id");
    $stmt->execute([':scholarship_id' => $scholarship_id]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);
    $scholarship_amount = $scholarship['amount'];

    // Determine which databases the student belongs to
    $student_in_cs = in_array($student_id, array_column($csStudents, 'student_id'));
    $student_in_bm = in_array($student_id, array_column($bmStudents, 'student_id'));

    // Assign scholarship in the respective databases
    if ($student_in_cs) {
        $pdo_cs = getDatabaseConnection('cs');
        
        // Assign scholarship
        $stmt = $pdo_cs->prepare("
            INSERT INTO scholarship_assignment (scholarship_id, student_id)
            VALUES (:scholarship_id, :student_id)
        ");
        $stmt->execute([
            ':scholarship_id' => $scholarship_id,
            ':student_id' => $student_id
        ]);
        
        // Update student finance record
        $stmt = $pdo_cs->prepare("
            UPDATE student_finance 
            SET scholarship_amount = scholarship_amount + :amount
            WHERE student_id = :student_id
        ");
        $stmt->execute([
            ':amount' => $scholarship_amount,
            ':student_id' => $student_id
        ]);
    }

    if ($student_in_bm) {
        $pdo_bm = getDatabaseConnection('bm');
        
        // Assign scholarship
        $stmt = $pdo_bm->prepare("
            INSERT INTO scholarship_assignment (scholarship_id, student_id)
            VALUES (:scholarship_id, :student_id)
        ");
        $stmt->execute([
            ':scholarship_id' => $scholarship_id,
            ':student_id' => $student_id
        ]);
        
        // Update student finance record
        $stmt = $pdo_bm->prepare("
            UPDATE student_finance 
            SET scholarship_amount = scholarship_amount + :amount
            WHERE student_id = :student_id
        ");
        $stmt->execute([
            ':amount' => $scholarship_amount,
            ':student_id' => $student_id
        ]);
    }

    // Set a success message
    $_SESSION['message'] = "Scholarship assigned successfully!";
    header("Location: scholarship.php");
    exit();
}

// Handle deletion of assigned scholarship
// Handle deletion of assigned scholarship
if (isset($_GET['delete_assignment'])) {
    $student_id = $_GET['student_id'];
    $scholarship_id = $_GET['scholarship_id'];

    // Get scholarship amount from central database
    $pdo_central = getDatabaseConnection('central');
    $stmt = $pdo_central->prepare("SELECT amount FROM scholarship WHERE scholarship_id = :scholarship_id");
    $stmt->execute([':scholarship_id' => $scholarship_id]);
    $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);
    $scholarship_amount = $scholarship['amount'];

    // Determine which databases the student belongs to
    $student_in_cs = in_array($student_id, array_column($csStudents, 'student_id'));
    $student_in_bm = in_array($student_id, array_column($bmStudents, 'student_id'));

    // Delete scholarship assignment from the respective databases
    if ($student_in_cs) {
        $pdo_cs = getDatabaseConnection('cs');
        $stmt = $pdo_cs->prepare("DELETE FROM scholarship_assignment WHERE student_id = :student_id AND scholarship_id = :scholarship_id");
        $stmt->execute([':student_id' => $student_id, ':scholarship_id' => $scholarship_id]);
        
        // Update student finance record
        $stmt = $pdo_cs->prepare("
            UPDATE student_finance 
            SET scholarship_amount = GREATEST(0, scholarship_amount - :amount)
            WHERE student_id = :student_id
        ");
        $stmt->execute([
            ':amount' => $scholarship_amount,
            ':student_id' => $student_id
        ]);
    }

    if ($student_in_bm) {
        $pdo_bm = getDatabaseConnection('bm');
        $stmt = $pdo_bm->prepare("DELETE FROM scholarship_assignment WHERE student_id = :student_id AND scholarship_id = :scholarship_id");
        $stmt->execute([':student_id' => $student_id, ':scholarship_id' => $scholarship_id]);
        
        // Update student finance record
        $stmt = $pdo_bm->prepare("
            UPDATE student_finance 
            SET scholarship_amount = GREATEST(0, scholarship_amount - :amount)
            WHERE student_id = :student_id
        ");
        $stmt->execute([
            ':amount' => $scholarship_amount,
            ':student_id' => $student_id
        ]);
    }

    // Set a success message
    $_SESSION['message'] = "Scholarship assignment removed successfully!";
    header("Location: scholarship.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
       .search-results {
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            position: absolute;
            width: 20%;
            background-color: white;
            z-index: 100;
            display: none;
        }

        .search-result-item {
            padding: 8px;
            cursor: pointer;
        }

        .search-result-item:hover {
            background-color: #f1f1f1;
        }


        /* Popup Styles */
        .popup {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.6);
            color: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 200;
        }

        .popup.show {
            display: block;
        }

        .popup button {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .popup button:hover {
            background-color: #218838;
        }

    </style>
</head>
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
                <li><a href="student.php"><i class="fas fa-users"></i><span>Students</span></a></li>
                <li><a href="staff.php"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>
                <li><a href="enrolment.php"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
                <li><a href="module.php"><i class="fas fa-book"></i><span>Modules</span></a></li>
                <li><a href="finance.php"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
                <li class="active"><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li><a href="tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>     

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Scholarships Management</h1>
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
                <!-- New Button -->
                <div class="new-button-container">
                    <a href="add_scholarship.php" class="btn-new">
                        <i class="fas fa-plus"></i>
                        New
                    </a>
                </div>

                <!-- All Scholarships Table -->
                <h2>List of Scholarships</h2>
                <div class="table-container">
                    <table id="scholarship-list">
                        <thead>
                            <tr>
                                <th>Scholarship ID</th>
                                <th>Scholarship Name</th>
                                <th>Amount (£)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scholarships as $scholarship): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($scholarship['scholarship_id']); ?></td>
                                    <td><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></td>
                                    <td><?php echo htmlspecialchars($scholarship['amount']); ?></td>
                                    <td>
                                    <a href="edit_scholarship.php?id=<?php echo $scholarship['scholarship_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i></a>  
                                                                          
                                    <a href="#" class="btn-delete" onclick="confirmDeleteScholarship('<?php echo $scholarship['scholarship_id']; ?>'); return false;">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Scholarship Assignment Form -->
                <h2>Scholarship Assignment</h2>
                <div class="content">
                <div class="search-filter">
                    <form id="assign-scholarship" method="POST" action="scholarship.php">
                        <input type="text" id="student-search" name="student-search" placeholder="Search by name or ID" autocomplete="off" oninput="searchStudents(this.value)">
                        <div id="search-results" class="search-results"></div>

                        <select id="scholarship" name="scholarship_id" required>
                            <option value="">Select a Scholarship</option>
                            <?php foreach ($scholarships as $scholarship): ?>
                                <option value="<?php echo $scholarship['scholarship_id']; ?>"><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Hidden input to store the selected student ID -->
                        <input type="hidden" id="student_id" name="student_id">

                        <button type="submit" name="assign-scholarship" class="btn" id="assign-btn">Assign Scholarship</button>
                    </form>
                </div>

                    <!-- Assigned Scholarships Table -->
                    <h3>Assigned Scholarships</h3>
                    <table id="assigned-scholarships">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Scholarship</th>
                                <th>Amount (£)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assigned_scholarships as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['scholarship_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['amount']); ?></td>
                                    <td>
                                    <a href="#" class="btn-delete" onclick="confirmRemoveScholarship('<?php echo $assignment['student_id']; ?>', '<?php echo $assignment['scholarship_id']; ?>'); return false;">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup for confirmation -->
    <!-- <div class="popup" id="confirmation-popup">
        <p>Scholarship successfully assigned to the student!</p>
        <button onclick="closePopup()">OK</button>
    </div> -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Function to search for students
function searchStudents(query) {
    const searchResultsDiv = document.getElementById('search-results');
    searchResultsDiv.innerHTML = '';

    if (query.trim() === '') {
        searchResultsDiv.style.display = 'none';
        return;
    }

    fetch(`search_students.php?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                searchResultsDiv.style.display = 'block';
                data.forEach(student => {
                    const resultItem = document.createElement('div');
                    resultItem.classList.add('search-result-item');
                    resultItem.innerHTML = `${student.first_name} ${student.last_name} (ID: ${student.student_id})`;
                    resultItem.onclick = () => selectStudent(student);
                    searchResultsDiv.appendChild(resultItem);
                });
            } else {
                searchResultsDiv.style.display = 'none';
            }
        })
        .catch(error => console.error('Error fetching students:', error));
}

function selectStudent(student) {
    document.getElementById('student-search').value = `${student.first_name} ${student.last_name} (ID: ${student.student_id})`;
    document.getElementById('student_id').value = student.student_id;
    document.getElementById('search-results').style.display = 'none';
}

document.addEventListener('click', function(event) {
    const searchResultsDiv = document.getElementById('search-results');
    const studentSearchInput = document.getElementById('student-search');

    if (event.target !== studentSearchInput && !searchResultsDiv.contains(event.target)) {
        searchResultsDiv.style.display = 'none';
    }
});

// 🎯 SWEETALERT CONFIRMATIONS

// Delete a scholarship
function confirmDeleteScholarship(scholarshipId) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: "This scholarship will be permanently deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete_scholarship.php?id=${scholarshipId}`;
        }
    });
}

// Remove assigned scholarship
function confirmRemoveScholarship(studentId, scholarshipId) {
    event.preventDefault();
    Swal.fire({
        title: 'Remove Scholarship?',
        text: "This will unassign the scholarship from the student.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `scholarship.php?delete_assignment=1&student_id=${studentId}&scholarship_id=${scholarshipId}`;
        }
    });
}

// 🟢 Show success messages from PHP sessions
<?php if (isset($_SESSION['message'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '<?php echo $_SESSION['message']; ?>',
        confirmButtonColor: '#3085d6'
    });
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $_SESSION['error']; ?>',
        confirmButtonColor: '#d33'
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

</body>
</html>