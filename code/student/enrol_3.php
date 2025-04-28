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

// Arrays to store program and module details for each department
$programmes = [];
$compulsoryModules = [];
$optionalModules = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch the student's programme details
    $stmt = $pdo->prepare("SELECT p.programme_name, p.programme_id, p.duration_years, pe.current_year, pe.academic_year, 
        CASE WHEN s.student_type = 'local' THEN p.local_fees
        WHEN s.student_type = 'international' THEN p.international_fees
        ELSE NULL END AS tuition_fee
        FROM programme_enrolment pe
        JOIN programme p ON pe.programme_id = p.programme_id
        JOIN students s ON pe.student_id = s.student_id
        WHERE pe.student_id = :student_id");
    $stmt->execute(['student_id' => $studentId]);
    $programme = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$programme) {
        die("Programme details not found for department ID: $departmentId.");
    }

    // Add department ID to the program details
    $programme['department_id'] = $departmentId;
    $programmes[] = $programme;

    // Fetch compulsory modules for the programme
    $stmt = $pdo->prepare("SELECT m.module_id, m.module_name FROM programme_module pm 
        JOIN modules m ON pm.module_id = m.module_id
        JOIN programme_enrolment pe ON pm.programme_id = pe.programme_id
        WHERE pe.student_id = :student_id AND m.level = pe.current_year AND pm.module_type = 'Compulsory';");
    $stmt->execute(['student_id' => $studentId]);
    $compulsoryModules[$departmentId] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch optional modules for the programme
    $stmt = $pdo->prepare("SELECT m.module_id, m.module_name, m.credits, m.semester, m.prerequisite_module_id, m.available_slots FROM programme_module pm
        JOIN modules m ON pm.module_id = m.module_id
        JOIN programme_enrolment pe ON pm.programme_id = pe.programme_id
        WHERE pe.student_id = :student_id AND m.level = pe.current_year AND pm.module_type = 'Optional';");
    $stmt->execute(['student_id' => $studentId]);
    $optionalModules[$departmentId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Loop through each department ID
    foreach ($departmentIds as $departmentId) {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Validate that only one optional module is selected (if optional modules exist)
        $selectedOptionalModule = $_POST['optional_module'][$departmentId] ?? null;

        if (!empty($optionalModules[$departmentId]) && empty($selectedOptionalModule)) {
            die("❌ Please select one optional module for department ID: $departmentId.");
        }

        // Check if the programme details checkbox is ticked
        $programmeDetailsCorrect = isset($_POST['programme-details-correct'][$departmentId]) ? 1 : 0;

        // Save the programme details checkbox state in the session
        $_SESSION['programme_details_correct'][$departmentId] = $programmeDetailsCorrect;

        // Fetch the academic_year from programme_enrolment
        $stmt = $pdo->prepare("SELECT academic_year FROM programme_enrolment WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $studentId]);
        $academicYear = $stmt->fetchColumn();

        if (!$academicYear) {
            die("❌ Academic year not found for the student in department ID: $departmentId.");
        }

        try {
            // Insert compulsory modules
            foreach ($compulsoryModules[$departmentId] as $module) {
                // Check if the student is already enrolled in this module
                $checkStmt = $pdo->prepare("SELECT 1 FROM student_modules WHERE student_id = :student_id AND module_id = :module_id");
                $checkStmt->execute([ 
                    'student_id' => $studentId, 
                    'module_id' => $module['module_id'] 
                ]);
                
                // If the student is not already enrolled, insert the module
                if (!$checkStmt->fetchColumn()) {
                    $stmt = $pdo->prepare("INSERT INTO student_modules (student_id, module_id, academic_year, status)
                        VALUES (:student_id, :module_id, :academic_year, :status)");
                    $stmt->execute([
                        'student_id' => $studentId,
                        'module_id' => $module['module_id'],
                        'academic_year' => $academicYear,
                        'status' => 'Enroled'
                    ]);
                }
            }

            // Insert the selected optional module (if optional modules exist)
            if (!empty($optionalModules[$departmentId]) && $selectedOptionalModule) {
                // Check if the student is already enrolled in the optional module
                $checkStmt = $pdo->prepare("SELECT 1 FROM student_modules WHERE student_id = :student_id AND module_id = :module_id");
                $checkStmt->execute([ 
                    'student_id' => $studentId, 
                    'module_id' => $selectedOptionalModule 
                ]);
                
                // If the student is not already enrolled, insert the module
                if (!$checkStmt->fetchColumn()) {
                    // Check if module has available slots
                    $checkAvailabilityStmt = $pdo->prepare("SELECT available_slots FROM modules WHERE module_id = :module_id");
                    $checkAvailabilityStmt->execute(['module_id' => $selectedOptionalModule]);
                    $availableSlots = $checkAvailabilityStmt->fetchColumn();

                    // Step 2: Check if there are available slots
                    if ($availableSlots > 0) {
                        // Step 3: Enroll the student
                        $stmt = $pdo->prepare("INSERT INTO student_modules (student_id, module_id, academic_year, status) 
                                            VALUES (:student_id, :module_id, :academic_year, :status)");
                        $stmt->execute([
                            'student_id' => $studentId,
                            'module_id' => $selectedOptionalModule,
                            'academic_year' => $academicYear,
                            'status' => 'Enroled'
                        ]);

                        // Step 4: Deduct one available slot
                        $stmt = $pdo->prepare("UPDATE modules SET available_slots = (available_slots - 1) WHERE module_id = :module_id");
                        $stmt->execute(['module_id' => $selectedOptionalModule]);

                        // Success message
                        echo "You have successfully enrolled in the module!";
                    } else {
                        // No available slots
                        echo "Sorry, this module is full.";
                    }

                    // Check prerequisites for the selected module
                    $checkPrerequisiteStmt = $pdo->prepare("
                        SELECT prerequisite_module_id FROM modules WHERE module_id = :module_id
                    ");
                    $checkPrerequisiteStmt->execute(['module_id' => $selectedOptionalModule]);
                    $prerequisiteModuleId = $checkPrerequisiteStmt->fetchColumn();

                    if ($prerequisiteModuleId) {
                        // Check if the student has completed the prerequisite
                        $checkPrerequisiteCompletionStmt = $pdo->prepare("
                            SELECT 1 FROM student_modules WHERE student_id = :student_id AND module_id = :module_id AND status = 'Completed'
                        ");
                        $checkPrerequisiteCompletionStmt->execute(['student_id' => $studentId, 'module_id' => $prerequisiteModuleId]);

                        if (!$checkPrerequisiteCompletionStmt->fetchColumn()) {
                            die("❌ You must complete the prerequisite module before enrolling in this module.");
                        }
                    }
                }
            }

            // Update the progress_step to 3
            $stmt = $pdo->prepare("UPDATE programme_enrolment SET progress_step = 3 WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $studentId]);

            // Save the selected optional module in the session
            $_SESSION['selected_optional_module'][$departmentId] = $selectedOptionalModule;
        } catch (PDOException $e) {
            die("❌ Error: " . $e->getMessage());
        }
    }

    // Redirect to the next page
    header('Location: enrol_4.php');
    exit();
}

// Retrieve the selected optional module from the session
$selectedOptionalModule = $_SESSION['selected_optional_module'] ?? [];

// Retrieve the programme details checkbox state from the session
$programmeDetailsCorrect = $_SESSION['programme_details_correct'] ?? [];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment - Module Selection</title>
    <link rel="stylesheet" href="template/styles.css">
    <script>
        // JavaScript to ensure only one optional module is selected per department
        function toggleOptionalModule(departmentId, moduleId) {
            const checkboxes = document.querySelectorAll(`input[name="optional_module[${departmentId}]"]`);
            checkboxes.forEach(checkbox => {
                if (checkbox.value !== moduleId) {
                    checkbox.checked = false;
                }
            });
        }
    </script>
</head>
<body class="enrol-page">
    <div class="container">
        <h1>Enrollment - Module Selection</h1>
        <form method="POST" action="">
            <!-- Loop through each department -->
            <?php foreach ($programmes as $programme): ?>
                <div class="department-section">
                    <h2>Department: <?php echo htmlspecialchars($programme['department_id']); ?></h2>
                    <div class="form-group">
                        <label for="program-name">Program Name:</label>
                        <input type="text" id="program-name" name="program-name" value="<?php echo htmlspecialchars($programme['programme_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="program-id">Program ID:</label>
                        <input type="text" id="program-id" name="program-id" value="<?php echo htmlspecialchars($programme['programme_id']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (Years):</label>
                        <input type="text" id="duration" name="duration_years" value="<?php echo htmlspecialchars($programme['duration_years']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="current_year">Current Year:</label>
                        <input type="text" id="current_year" name="current_year" value="<?php echo htmlspecialchars($programme['current_year']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="tuition-fee">Tuition Fee Per Year(£):</label>
                        <input type="text" id="tuition-fee" name="tuition-fee" value="<?php echo htmlspecialchars($programme['tuition_fee']); ?>" readonly>
                    </div>

                    <!-- Display compulsory modules -->
                    <div class="module-selection">
                        <h4>Compulsory Modules</h4>
                        <?php foreach ($compulsoryModules[$programme['department_id']] as $module): ?>
                            <div class="module-card">
                                <h3><?php echo htmlspecialchars($module['module_id'] . ' - ' . $module['module_name']); ?></h3>
                                <div class="checkbox-container">
                                    <input type="checkbox" id="module-<?php echo htmlspecialchars($module['module_id']); ?>" name="module-<?php echo htmlspecialchars($module['module_id']); ?>" checked disabled>
                                    <label for="module-<?php echo htmlspecialchars($module['module_id']); ?>">Compulsory Module (Selected)</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Display optional modules (if any) -->
                    <?php if (!empty($optionalModules[$programme['department_id']])): ?>
                        <div class="module-selection">
                            <h4>Optional Modules</h4>
                            <?php foreach ($optionalModules[$programme['department_id']] as $module): ?>
                                <div class="module-card">
                                    <h3><?php echo htmlspecialchars($module['module_id'] . ' - ' . $module['module_name']); ?></h3>
                                    <p><strong>Prerequisites:</strong> <?php echo htmlspecialchars($module['prerequisite_module_id'] ?? 'None'); ?></p>
                                    <p><strong>Credits:</strong> <?php echo htmlspecialchars($module['credits']); ?></p>
                                    <p><strong>Availability:</strong> <?php echo htmlspecialchars($module['available_slots']); ?></p>
                                    <p><strong>Semester:</strong> <?php echo htmlspecialchars($module['semester'] ?? 'TBA'); ?></p>
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="module-<?php echo htmlspecialchars($module['module_id']); ?>" 
                                               name="optional_module[<?php echo htmlspecialchars($programme['department_id']); ?>]" 
                                               value="<?php echo htmlspecialchars($module['module_id']); ?>" 
                                               <?php echo ($selectedOptionalModule[$programme['department_id']] ?? null) === $module['module_id'] ? 'checked' : ''; ?> 
                                               onclick="toggleOptionalModule('<?php echo htmlspecialchars($programme['department_id']); ?>', '<?php echo htmlspecialchars($module['module_id']); ?>')">
                                        <label for="module-<?php echo htmlspecialchars($module['module_id']); ?>">Select this module</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Programme details confirmation -->
                    <div class="checkbox-container">
                        <label>
                            <input type="checkbox" name="programme-details-correct[<?php echo htmlspecialchars($programme['department_id']); ?>]" required>
                            I confirm that the programme details are correct.
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="footer">
                <a href="enrol_2.php"><button type="button" class="btn">Back</button></a>
                <button type="submit" class="btn">Next</button>
            </div>
        </form>
    </div>
</body>
</html>

