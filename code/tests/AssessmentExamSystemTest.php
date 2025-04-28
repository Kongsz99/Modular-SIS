<?php
use PHPUnit\Framework\TestCase;

class AssessmentExamSystemTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ensure necessary module and student data exists
        $this->pdo->exec("INSERT INTO modules (module_id, module_name, department_id, semester, level, credits, exam_weight, assignment_weight, available_slots)
                          VALUES ('CS101', 'Test Module', 'CS', 1, 1, 15, 50, 50, 30)
                          ON CONFLICT (module_id) DO NOTHING");

        $this->pdo->exec("INSERT INTO students (student_id, user_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
                          VALUES (1001, 2001, 'John', 'Doe', 'male', '2000-01-01', 'UK', 'Bachelor', 'Test University')
                          ON CONFLICT (student_id) DO NOTHING");
    }

    public function testAssignmentManagement()
    {
        // Insert assignment
        $stmt = $this->pdo->prepare("INSERT INTO assignment (module_id, title, description, due_date, document)
                                     VALUES ('CS101', 'Midterm Project', 'Build a web app', '2025-05-10', 'project.pdf')");
        $stmt->execute();

        $assignmentId = $this->pdo->lastInsertId('assignment_id_seq');
        $this->assertNotEmpty($assignmentId);

        // Update assignment
        $stmt = $this->pdo->prepare("UPDATE assignment SET title = 'Updated Project' WHERE assignment_id = :id");
        $stmt->execute([':id' => $assignmentId]);

        // Delete assignment
        $stmt = $this->pdo->prepare("DELETE FROM assignment WHERE assignment_id = :id");
        $stmt->execute([':id' => $assignmentId]);

        $this->assertTrue(true); // If no exceptions, test passed
    }

    public function testAssignmentSubmissionWithAttachment()
    {
        // Insert a test assignment
        $this->pdo->exec("INSERT INTO assignment (assignment_id, module_id, title, due_date)
                          VALUES ('CSA999999', 'CS101', 'Test Assignment', '2025-05-10')");

        // Submit assignment
        $stmt = $this->pdo->prepare("INSERT INTO submission (assignment_id, student_id, file_path)
                                     VALUES ('CSA999999', 1001, 'uploads/test_assignment.pdf')");
        $stmt->execute();

        $this->assertTrue(true);
    }

    public function testExamManagement()
    {
        // Insert exam
        $stmt = $this->pdo->prepare("INSERT INTO exam (module_id, exam_date, start_time, end_time, location, academic_year)
                                     VALUES ('CS101', '2025-06-01', '10:00', '12:00', 'Exam Hall A', '2024/2025')");
        $stmt->execute();

        // Update exam
        $stmt = $this->pdo->prepare("UPDATE exam SET location = 'Updated Hall' WHERE module_id = 'CS101'");
        $stmt->execute();

        // Delete exam
        $stmt = $this->pdo->prepare("DELETE FROM exam WHERE module_id = 'CS101'");
        $stmt->execute();

        $this->assertTrue(true);
    }

    public function testStudentCanViewExamSchedule()
    {
        // Insert exam
        $this->pdo->exec("INSERT INTO exam (exam_id, module_id, exam_date, start_time, end_time, location, academic_year)
                          VALUES ('CSX999999', 'CS101', '2025-06-01', '10:00', '12:00', 'Hall A', '2024/2025')");

        // Fetch exam
        $stmt = $this->pdo->prepare("SELECT * FROM exam WHERE module_id = 'CS101'");
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Hall A', $exam['location']);
    }
}
