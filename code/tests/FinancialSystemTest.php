<?php

use PHPUnit\Framework\TestCase;

class FinancialSystemTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction
        $this->pdo->beginTransaction();

    }
    public function testTuitionFeesGeneratedByTrigger(): void
    {
        // Setup test data
        $stmt = $this->pdo->query("SELECT nextval('student_id_seq')");
        $studentId = $stmt->fetchColumn();
        $programmeId = 'UNCS12';
        $academicYear = '2024/5';
    
        // Insert student
        $this->pdo->exec("INSERT INTO students (student_id, user_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name) 
                          VALUES ($studentId, 1000017, 'Jane', 'Doe', 'female', '2000-01-01', 'Kenyan', 'Bachelor', 'Test Uni')");
    
        // Insert programme
        $this->pdo->exec("INSERT INTO programme (programme_id, programme_name, department_id, duration_years, local_fees, international_fees) 
                          VALUES ('$programmeId', 'Test Programme', 'CS', 3, 5000.00, 10000.00)");
    
        // Insert scholarship (optional if your function uses it)
        $this->pdo->exec("INSERT INTO scholarship (scholarship_id, scholarship_name, scholarship_type, amount) VALUES ('SC07', 'Europe Scholarship', 'merit based', 1000)");
        $this->pdo->exec("INSERT INTO scholarship_assignment (student_id, scholarship_id) VALUES ($studentId, 'SC07')");
    
        // Trigger the function by inserting into programme_enrolment
        $stmt = $this->pdo->prepare("INSERT INTO programme_enrolment (student_id, programme_id, programme_start_date, programme_end_date, academic_year, current_year, enrolment_date)
                                     VALUES (?, ?, '2024-09-01', '2027-06-30', ?, 1,'2024-08-01')");
        $stmt->execute([$studentId, $programmeId, $academicYear]);
    
        // Wait briefly (PostgreSQL trigger runs synchronously, but delay helps with debug)
        usleep(200000);
    
        // Fetch the generated finance record
        $stmt = $this->pdo->prepare("SELECT * FROM student_finance WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $finance = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Assert finance record exists
        $this->assertNotEmpty($finance, "student_finance record was not created");
    
        // Assert base fees and scholarship logic
        $this->assertEquals(5000.00, (float)$finance['base_fees'], "Base fee mismatch");
        $this->assertEquals(1000.00, (float)$finance['scholarship_amount'], "Scholarship amount mismatch");
        $this->assertEquals('not-paid', $finance['status'], "Initial payment status should be 'not-paid'");
    }
    
    
      protected function tearDown(): void
    {
        $this->pdo->rollBack(); // Undoes everything
        $this->pdo = null;
    }   
}