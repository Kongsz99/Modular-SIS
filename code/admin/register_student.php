<?php
require_once '../db_connect.php';
require_once '../auth.php';

header("Content-Type: application/json");

// Verify admin role
check_role(GLOBAL_ADMIN);

// Get central database connection
$share_db_conn = getDatabaseConnection('central');

// Initialize response
$response = ['status' => 'error', 'message' => ''];

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Validate required fields
    $required = [
        'first_name', 'last_name', 'gender', 'date_of_birth',
        'personal_email', 'phone', 'address', 'postcode', 'city',
        'state', 'country', 'nationality', 'student_type',
        'education_level', 'institution_name', 'department_1', 'p1'
    ];
    
    $data = [];
    foreach ($required as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
        if (empty($data[$field])) {
            throw new Exception("Missing required field: " . str_replace('_', ' ', $field));
        }
    }

    // Additional validations
    if (!filter_var($data['personal_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Verify department exists
    $stmt = $share_db_conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
    $stmt->execute([$data['department_1']]);
    if (!$stmt->fetch()) {
        throw new Exception("Invalid department ID");
    }

    // Begin transaction
    $share_db_conn->beginTransaction();

    // Generate credentials
    $student_id = get_nextval($share_db_conn, 'student_id_seq');
    $username = 'st' . str_pad(substr($student_id, -6), 6, "0", STR_PAD_LEFT);
    $password = strtolower($data['last_name']) . date('Ymd', strtotime($data['date_of_birth']));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $uni_email = $username . '@student.university.edu';
    $address_id = get_formatted_id($share_db_conn, 'address_id_seq', 'A', 8);

    // Insert user
    $stmt = $share_db_conn->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 4)");
    $stmt->execute([$username, $password_hash]);
    $user_id = $share_db_conn->lastInsertId();

    // Insert user departments
    $departments = array_filter([$data['department_1'], $_POST['department_2'] ?? null]);
    foreach ($departments as $dept_id) {
        $stmt = $share_db_conn->prepare("INSERT INTO user_department (user_id, department_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $dept_id]);
    }

    // Process each department
    $programs = [
        [
            'dept' => $data['department_1'],
            'prog' => $data['p1'],
            'start' => $_POST['p1_start_date'] ?? date('Y-m-d'),
            'year' => $_POST['p1_current_year'] ?? 1,
            'academic_year' => $_POST['p1_academic_year'] ?? date('Y') . '/' . (date('Y')+1)
        ],
        [
            'dept' => $_POST['department_2'] ?? null,
            'prog' => $_POST['p2'] ?? null,
            'start' => $_POST['p2_start_date'] ?? null,
            'year' => $_POST['p2_current_year'] ?? null,
            'academic_year' => $_POST['p2_academic_year'] ?? null
        ]
    ];

    foreach ($programs as $program) {
        if (empty($program['dept'])) continue;

        $target_db = getDatabaseConnection(strtolower($program['dept']));

        // Insert address
        $stmt = $target_db->prepare("INSERT INTO address (address_id, address, postcode, city, state, country) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $address_id, $data['address'], $data['postcode'], 
            $data['city'], $data['state'], $data['country']
        ]);

        // Insert student
        $stmt = $target_db->prepare("INSERT INTO students (
            student_id, user_id, first_name, last_name, gender, date_of_birth, 
            personal_email, uni_email, phone, address_id, nationality, 
            student_type, education_level, institution_name, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute([
            $student_id, $user_id, $data['first_name'], $data['last_name'], 
            $data['gender'], $data['date_of_birth'], $data['personal_email'], 
            $uni_email, $data['phone'], $address_id, $data['nationality'], 
            $data['student_type'], $data['education_level'], $data['institution_name']
        ]);

        $year = substr($program['academic_year'], 0, 4);  // Extract the year (e.g., '2024' from '2024/5')

        // Set the enrolment date to September 15th of that year
        $enrolment_date = $year . '-09-15';

        // Insert enrollment
        $stmt = $target_db->prepare("INSERT INTO programme_enrolment (
            student_id, programme_id, programme_start_date, academic_year, enrolment_date, current_year
        ) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $student_id, 
            $program['prog'], 
            $program['start'], 
            $program['academic_year'], 
            $enrolment_date,  // Use the calculated enrolment_date here
            $program['year']
        ]);

    }

    // Commit transaction
    $share_db_conn->commit();

    $response = [
        'status' => 'success',
        'message' => 'Student registered successfully',
        'data' => [
            'student_id' => $student_id,
            'username' => $username
        ]
    ];

} catch (PDOException $e) {
    if (isset($share_db_conn) && $share_db_conn->inTransaction()) {
        $share_db_conn->rollBack();
    }
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    if (isset($share_db_conn) && $share_db_conn->inTransaction()) {
        $share_db_conn->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

function get_nextval($db, $sequence) {
    $stmt = $db->prepare("SELECT nextval(?) AS val");
    $stmt->execute([$sequence]);
    return $stmt->fetch()['val'];
}

function get_formatted_id($db, $sequence, $prefix, $length) {
    return $prefix . str_pad(get_nextval($db, $sequence), $length, '0', STR_PAD_LEFT);
}
?>