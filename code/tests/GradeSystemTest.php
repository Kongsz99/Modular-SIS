<?php
use PHPUnit\Framework\TestCase;

class GradeSystemTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert required module and student if not exists
        $this->pdo->exec("INSERT INTO modules (module_id, module_name, department_id, semester, level, credits, exam_weight, assignment_weight, available_slots)
                          VALUES ('CS101', 'Test Module', 'CS', 1, 1, 15, 50, 50, 30)
                          ON CONFLICT (module_id) DO NOTHING");

        $this->pdo->exec("INSERT INTO students (student_id, user_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
                          VALUES (1001, 2001, 'Alice', 'Smith', 'female', '2000-01-01', 'UK', 'Bachelor', 'Test University')
                          ON CONFLICT (student_id) DO NOTHING");
    }

    public function testGradeManagement()
    {
        // Add grade
        $stmt = $this->pdo->prepare("INSERT INTO grade (module_id, student_id, assignment_marks, exam_marks, total_marks, grade, academic_year)
                                     VALUES ('CS101', 1001, 45.5, 40.0, 85.5, 'A', '2024/2025')");
        $stmt->execute();

        // Update grade
        $stmt = $this->pdo->prepare("UPDATE grade SET grade = 'A+' WHERE module_id = 'CS101' AND student_id = 1001");
        $stmt->execute();

        // Verify update
        $stmt = $this->pdo->prepare("SELECT grade FROM grade WHERE module_id = 'CS101' AND student_id = 1001");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('A+', $result['grade']);
    }

    public function testStudentCanViewOwnGrade()
    {
        // Make sure a grade exists for the test
        $this->pdo->exec("INSERT INTO grade (grade_id, module_id, student_id, assignment_marks, exam_marks, total_marks, grade, academic_year)
                          VALUES ('CSG499999', 'CS101', 1001, 50, 45, 95, 'A+', '2024/2025')");

        // Simulate student viewing their grade
        $stmt = $this->pdo->prepare("SELECT grade, total_marks FROM grade WHERE student_id = 1001 AND module_id = 'CS101'");
        $stmt->execute();
        $grade = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('A+', $grade['grade']);
        $this->assertEquals(85.5, (float) $grade['total_marks']);

    }
}
