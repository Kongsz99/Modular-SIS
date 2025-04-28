<?php 
require_once '../db_connect.php';
require_once '../auth.php';

check_role(required_role: GLOBAL_ADMIN);

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department']; // e.g., 'CS' or 'BM'
    $staff_id = $_POST['staff_id'];
    $student_id = $_POST['student_id'];

    if (empty($department) || empty($staff_id) || empty($student_id)) {
        die("Error: All fields are required.");
    }

    // Connect to the appropriate database
    $pdo = getDatabaseConnection(strtolower($department)); // e.g., 'cs' or 'bm'

    try {
        $sql = "INSERT INTO academic_tutor_assigned (staff_id, student_id) VALUES (:staff_id, :student_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'staff_id' => $staff_id,
            'student_id' => $student_id,
        ]);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

    try {
        if(isset($_GET['department'])){
            $department = strtolower($_GET['department']);
            $pdo = getDatabaseConnection($department); // Connect to the department's specific database
            
            $sql = "
                SELECT at.staff_id, at.student_id, 
                    s.first_name AS staff_first_name, s.last_name AS staff_last_name,
                    st.first_name AS student_first_name, st.last_name AS student_last_name
                FROM academic_tutor_assigned at
                JOIN staff s ON at.staff_id = s.staff_id
                JOIN students st ON at.student_id = st.student_id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $assigned_tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            // echo json_encode([]); // Return an empty array if department is not selected
        }
        $successMessage = "Assign Student successfully!";
    } catch (PDOException $e) {
        die("Error fetching assigned tutors: " . $e->getMessage());
    }

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
       /* Style for the search results dropdown */
        .search-results {
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            position: absolute;
            width: 25%;
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
        /* Ensure the dropdown is visible when typing */
        #student-search:focus + .search-results,
        #student-search:not(:placeholder-shown) + .search-results {
            display: block;
        }
        /* Ensure the dropdown is visible when typing */
        #student-search:focus + .search-results,
        #student-search:not(:placeholder-shown) + .search-results {
            display: block;
        }

    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
    // Fetch departments on page load
    fetch('get_departments.php')
        .then(response => response.json())
        .then(data => {
            const departmentSelect = document.getElementById('department');
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.department_id;
                option.textContent = dept.department_name;
                departmentSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching departments:', error));

    // Fetch staff and students when a department is selected
    document.getElementById('department').addEventListener('change', function () {
        const department = this.value;

        if (department) {
            // Fetch staff for the selected department
            fetch(`get_staff.php?department=${department}`)
                .then(response => response.json())
                .then(data => {
                    const staffSelect = document.getElementById('staff');
                    staffSelect.innerHTML = '<option value="">Select Tutor</option>';
                    data.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.staff_id;
                        option.textContent = `${staff.first_name} ${staff.last_name}`;
                        staffSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching staff:', error));

            // Fetch students for the selected department
            fetch(`get_students.php?department=${department}`)
                .then(response => response.json())
                .then(data => {
                    const studentSearch = document.getElementById('student-search');
                    const searchResults = document.getElementById('search-results');

                    studentSearch.addEventListener('input', function () {
                        const searchTerm = this.value.toLowerCase();
                        searchResults.innerHTML = ''; // Clear previous results

                        // Filter the students based on the search term
                        const filteredStudents = data.filter(student =>
                            student.first_name.toLowerCase().includes(searchTerm) ||
                            student.last_name.toLowerCase().includes(searchTerm) ||
                            String(student.student_id).includes(searchTerm) // Include student ID search
                        );

                        filteredStudents.forEach(student => {
                            const div = document.createElement('div');
                            div.classList.add('search-result-item');
                            div.textContent = `${student.first_name} ${student.last_name} (${student.student_id})`;
                            div.addEventListener('click', function () {
                                document.getElementById('student_id').value = student.student_id;
                                studentSearch.value = `${student.first_name} ${student.last_name} (${student.student_id})`;
                                searchResults.innerHTML = ''; // Clear results after selection
                            });
                            searchResults.appendChild(div);
                        });

                        if (filteredStudents.length === 0) {
                            searchResults.innerHTML = '<div class="search-result-item">No results found</div>';
                        }

                        // Dynamically position the search results dropdown below the search input
                        const rect = studentSearch.getBoundingClientRect();
                        searchResults.style.left = `${rect.left}px`; // Align the left side of the dropdown with the input field
                        searchResults.style.top = `${rect.bottom + window.scrollY}px`; // Position it just below the input field
                    });
                })
                .catch(error => console.error('Error fetching students:', error));
        }
    });
});

</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships Management</title>
    <link rel="stylesheet" href="template/styles.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style></style>
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
                <li><a href="scholarship.php"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
                <li><a href="exam.php"><i class="fas fa-calendar-alt"></i><span>Exams</span></a></li>
                <li><a href="requests.php"><i class="fas fa-wheelchair"></i> EC & DAS Requests</a></li>
                <li class="active"><a href="assign_tutor.php"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>     

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1>Assign Academic Tutor</h1>
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
               <a href="tutor.php" class="back-button">
                   <i class="fas fa-arrow-left"></i> Back to Academic Tutor List
               </a>
                <!-- Add New Scholarship Form -->
                <div class="form-container">
                    <form id="assign-tutor-form" name="assign-tutor-form" method="POST" action="assign_tutor.php">
                        <div class="form-group">
                            <label for="department-id">Department Name</label>
                            <select id="department" name="department" required>
                                <option value="">Choose Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                             <label for="staff-id">Academic Tutor</label>
                            <select id="staff" name="staff_id" required>
                                <option value="">Select Tutor</option>
                            </select>
                        </div>
                        <div class="form-group">
                             <label for="student-id">Student ID or Name</label>

                            <input type="text" id="student-search" name="student-search" placeholder="Search by name or ID" autocomplete="off">

                            <div id="search-results" class="search-results"></div>
                        </div>
                            <input type="hidden" id="student_id" name="student_id">

                            <div class="form-group submit-button">
                                <button type="submit" name="assign-tutor" class="btn" id="assign-btn">Assign Tutor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <?php if (!empty($successMessage)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo $successMessage; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect or do something else if needed
                        window.location.href = 'tutor.php';
                    }
                });
            });
        </script>
        <?php endif; ?>
</body>
</html>
<button type="submit" class="btn-pay-now">Make Payment</button>