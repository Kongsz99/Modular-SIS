<?php
use PHPUnit\Framework\TestCase;

class AcademicTutorAssignmentTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->beginTransaction();

        // Insert dummy staff for testing
        $this->pdo->exec("INSERT INTO staff (staff_id, first_name, last_name, gender, date_of_birth, personal_email, phone )
                          VALUES (3001, 'John', 'Doe', 'male', '1990-01-01', 'john.doe@example.com', '1234567890')");
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function testAssignAcademicTutor()
    {
        // Create a unique numeric student ID for the test
        $user_id = $this->pdo->query("SELECT nextval('users_user_id_seq')");
        $stmt = $this->pdo->query("SELECT nextval('student_id_seq')");
        $student_id = $stmt->fetchColumn(); // Get the next value in the sequence; 
        $this->pdo->exec("INSERT INTO students (student_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
                          VALUES ($student_id, 'Test', 'Student', 'male', '2000-01-01', 'Local', 'Undergraduate', 'Test Uni')");

        // Assign student to tutor
        $stmt = $this->pdo->prepare("INSERT INTO academic_tutor_assigned (student_id, staff_id) VALUES (?, ?)");
        $stmt->execute([$student_id, 3001]);

        // Verify the assignment exists
        $check = $this->pdo->prepare("SELECT * FROM academic_tutor_assigned WHERE student_id = ? AND staff_id = ?");
        $check->execute([$student_id, 3001]);
        $result = $check->fetch();

        $this->assertNotEmpty($result, "Student was not assigned to tutor correctly.");
    }

    public function testUnassignAcademicTutor()
    {
        // Create a unique numeric student ID for the test
        $stmt = $this->pdo->query("SELECT nextval('student_id_seq')");
        $student_id = $stmt->fetchColumn(); // Random numeric ID within range
        $this->pdo->exec("INSERT INTO students (student_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
                          VALUES ($student_id, 'Test', 'Student', 'male', '2000-01-01', 'Local', 'Undergraduate', 'Test Uni')");

        // First, assign
        $this->pdo->exec("INSERT INTO academic_tutor_assigned (student_id, staff_id) VALUES ($student_id, 3001)");

        // Then, unassign
        $stmt = $this->pdo->prepare("DELETE FROM academic_tutor_assigned WHERE student_id = ? AND staff_id = ?");
        $stmt->execute([$student_id, 3001]);

        // Verify unassignment
        $check = $this->pdo->prepare("SELECT * FROM academic_tutor_assigned WHERE student_id = ? AND staff_id = ?");
        $check->execute([$student_id, 3001]);
        $result = $check->fetch();

        $this->assertFalse($result, "Student was not unassigned from tutor correctly.");
    }
}

