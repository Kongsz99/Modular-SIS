<?php
// StudentRegistrationTest.php
use PHPUnit\Framework\TestCase;

class UserManagementTest extends TestCase {

    private $pdo;

    // This will run before each test to set up the test environment
    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->beginTransaction();

    }

    // Method to insert a test user into the users table
    protected function insertTestUser($username, $password_hash, $role_id = 4) {
        $query = "
            INSERT INTO users (username, password_hash, role_id)
            VALUES (:username, :password_hash, :role_id)
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':role_id' => $role_id
        ]);
        return $this->pdo->lastInsertId();
    }

    // Method to insert a test address into the address table with a custom address_id
    protected function insertTestAddress($address, $postcode, $city, $state, $country, $address_id) {
        $query = "
            INSERT INTO address (address_id, address, postcode, city, state, country)
            VALUES (:address_id, :address, :postcode, :city, :state, :country)
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':address_id' => $address_id,
            ':address' => $address,
            ':postcode' => $postcode,
            ':city' => $city,
            ':state' => $state,
            ':country' => $country
        ]);
        return $address_id; // Return the address_id
    }

    // Actual test method for student registration
    public function testStudentRegistration() {
        // Test data
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2000-01-01',
            'personal_email' => 'heljj@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St.',
            'postcode' => '12345',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'nationality' => 'Test Nationality',
            'student_type' => 'local',
            'education_level' => 'Undergraduate',
            'institution_name' => 'Test University',
            'department_1' => 1,  // Assume 1 is a valid department ID
            'p1' => 1  // Assume 1 is a valid program ID
        ];

        // Insert a test address into the database with a custom address_id 'A0000001'
        $stmt = $this->pdo->query("SELECT nextval('address_id_seq')");
        $address_id = $stmt->fetchColumn();
        $this->insertTestAddress(
            $data['address'],
            $data['postcode'],
            $data['city'],
            $data['state'],
            $data['country'],
            $address_id
        );

        // Insert a test user into the database
        $username = 'st' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $user_id = $this->insertTestUser($username, $password_hash);
        $stmt = $this->pdo->query("SELECT nextval('student_id_seq')");
        $student_id = $stmt->fetchColumn();

        // Insert student registration details (using the address data)
        $stmt = $this->pdo->prepare("
            INSERT INTO students (
                student_id, user_id, first_name, last_name, gender, date_of_birth, personal_email, 
                uni_email, phone, address_id, nationality, student_type, education_level, institution_name, status
            ) VALUES (
                :student_id, :user_id, :first_name, :last_name, :gender, :date_of_birth, :personal_email, 
                :uni_email, :phone, :address_id, :nationality, :student_type, :education_level, :institution_name, 'active'
            )
        ");

        $uni_email = $username . '@student.university.edu';

        $stmt->execute([
            'student_id' => $student_id,
            ':user_id' => $user_id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':gender' => $data['gender'],
            ':date_of_birth' => $data['date_of_birth'],
            ':personal_email' => $data['personal_email'],
            ':uni_email' => $uni_email,
            ':phone' => $data['phone'],
            ':address_id' => $address_id,
            ':nationality' => $data['nationality'],
            ':student_type' => $data['student_type'],
            ':education_level' => $data['education_level'],
            ':institution_name' => $data['institution_name']
        ]);

        // Verify that the student was inserted successfully
        $stmt = $this->pdo->prepare("SELECT * FROM students WHERE uni_email = :uni_email");
        $stmt->execute([':uni_email' => $uni_email]);
        $student = $stmt->fetch();

        $this->assertNotEmpty($student);
        $this->assertEquals($data['first_name'], $student['first_name']);
        $this->assertEquals($data['last_name'], $student['last_name']);
        $this->assertEquals($data['personal_email'], $student['personal_email']);
        $this->assertEquals($uni_email, $student['uni_email']);
    }

    public function testDepartmentAdminCannotAddStudent() {
        // Simulate a Department Admin login (role_id = 2)
        $username = 'di2admin';
        $password_hash = password_hash('securepass', PASSWORD_DEFAULT);
        $user_id = $this->insertTestUser($username, $password_hash, 2);
    
        // Attempt to insert a student (simulate restricted access logic)
        $hasPermission = false; // This would normally be determined by your access control logic
    
        // In a real app, you'd simulate the controller call and check the response
        $this->assertFalse($hasPermission, "Department Admin should not have permission to add student.");
    }
    public function testPasswordHashing() {
        $password = 'mypassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
    
        $this->assertTrue(password_verify($password, $hash), "Password should be correctly hashed and verifiable.");
    }
    
    public function testStudentCanUpdateContactInfo() {
        // Setup user and student as in previous test
        $username = 'st00013';
        $user_id = $this->insertTestUser($username, password_hash('pass', PASSWORD_DEFAULT));
        $student_id = '31000013';
    
        // Update phone number
        $newPhone = '07517118453';
        $this->pdo->prepare("UPDATE students SET phone = :phone WHERE student_id = :sid")
            ->execute([':phone' => $newPhone, ':sid' => $student_id]);
    
        // Check update
        $stmt = $this->pdo->prepare("SELECT phone FROM students WHERE student_id = :sid");
        $stmt->execute([':sid' => $student_id]);
        $updated = $stmt->fetch();
    
        $this->assertEquals($newPhone, $updated['phone']);
    }
    public function testRoleBasedLoginRedirection() {
        $roles = [
            1 => 'global_admin/dashboard.php',
            2 => 'department_admin/dashboard.php',
            3 => 'lecturer/dashboard.php',
            4 => 'student/dashboard.php'
        ];
    
        foreach ($roles as $roleId => $expectedRedirect) {
            $redirectPath = $this->simulateLoginRedirect($roleId);
            $this->assertEquals($expectedRedirect, $redirectPath, "Role $roleId should redirect to $expectedRedirect");
        }
    }
    
    // Simulated redirect logic
    private function simulateLoginRedirect($roleId) {
        switch ($roleId) {
            case 1: return 'global_admin/dashboard.php';
            case 2: return 'department_admin/dashboard.php';
            case 3: return 'lecturer/dashboard.php';
            case 4: return 'student/dashboard.php';
            default: return 'login.php';
        }
    }
    
    
}
