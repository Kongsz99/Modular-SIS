<?php
use PHPUnit\Framework\TestCase;

class DatabaseQueryTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Create a PDO connection
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        
        // Start a new transaction
        $this->pdo->beginTransaction();
    }
    
    protected function tearDown(): void
    {
        // Rollback any changes made during the test
        $this->pdo->rollBack();
    }
    
    public function testQueryPerformanceWithLargeEnrolmentDataset()
    {
        // Insert 1000 students into the students table
        $students = [];
        for ($i = 0; $i < 1000; $i++) {
            // Fetch the next user_id from the sequence for the users table
            $stmt = $this->pdo->query("SELECT nextval('users_user_id_seq')");
            $user_id = $stmt->fetchColumn();  // Get the next user_id
            
            // Insert into the users table
            $stmt = $this->pdo->prepare("INSERT INTO users (user_id, username, password_hash, role_id) 
                                        VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, 'user' . $user_id, password_hash('password123', PASSWORD_DEFAULT), 4]);

            // Fetch the next student_id from the sequence
            $stmt = $this->pdo->query("SELECT nextval('student_id_seq')");
            $student_id = $stmt->fetchColumn();  // Get the next student_id
            
            // Store student IDs for later use
            $students[] = $student_id;

            // Insert into the students table with the generated student_id and user_id
            $stmt = $this->pdo->prepare("INSERT INTO students (student_id, user_id, first_name, last_name, gender, date_of_birth, nationality, education_level, institution_name)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $user_id, 'John', 'Doe', 'male', '1999-01-01', 'Local', 'Undergraduate', 'Test University']);
        }

        // After inserting students, insert into programme_enrolment for each student
        $programme_id = 'UNCS01';  // Ensure this is a valid programme_id
        $programme_start_date = '2025-01-01';
        $programme_end_date = '2026-01-01';
        $academic_year = '2025/6';
        $enrolment_date = '2025-01-01';
        $current_year = 1;
        $status = 'enroled';

        foreach ($students as $student_id) {
            // Fetch the next program enrolment id from the sequence
            $stmt = $this->pdo->query("SELECT nextval('enroled_prog_seq')");
            $enrolment_id = $stmt->fetchColumn();  // Get the next enrolment_id

            // Insert the enrolment record with the generated enrolment_id
            $stmt = $this->pdo->prepare("INSERT INTO programme_enrolment (enrolment_id, student_id, programme_id, programme_start_date, programme_end_date, academic_year, enrolment_date, current_year, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$enrolment_id, $student_id, $programme_id, $programme_start_date, $programme_end_date, $academic_year, $enrolment_date, $current_year, $status]);
        }

        // Optionally: Verify some of the enrolments to ensure they were added
        $check = $this->pdo->prepare("SELECT * FROM programme_enrolment WHERE student_id IN (" . implode(",", $students) . ")");
        $check->execute();
        $result = $check->fetchAll();

        // Assert that the data was inserted
        $this->assertCount(count($students), $result, "Not all students were enrolled successfully.");

        // Commit the transaction to persist the data (you could also use rollBack() to discard changes)
        // $this->pdo->commit(); // Commented out because you are rolling back in tearDown

        // Continue with your test
        $this->assertTrue(true);
    }
}
