<?php
use PHPUnit\Framework\TestCase;

class TimetableSchedulingTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
{
    $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clean up any existing test data
    $this->pdo->exec("DELETE FROM module_timetable WHERE module_id IN ('CS101', 'MODTEST1', 'MODTEST2', 'MODTEST3')");
    $this->pdo->exec("DELETE FROM modules WHERE module_id IN ('CS101', 'MODTEST1', 'MODTEST2', 'MODTEST3')");

    // Insert required module test data
    $this->pdo->exec("
        INSERT INTO modules (module_id, module_name, department_id, semester, level, credits, exam_weight, assignment_weight, available_slots)
        VALUES 
        ('CS101', 'Test Module 1', 'CS', 1, 1, 15, 50, 50, 30),
        ('MODTEST1', 'Test Module 2', 'CS', 1, 1, 15, 50, 50, 30),
        ('MODTEST2', 'Test Module 3', 'CS', 1, 1, 15, 50, 50, 30),
        ('MODTEST3', 'Test Module 4', 'CS', 1, 1, 15, 50, 50, 30)
    ");
}

    public function testDepartmentAdminCanManageTimetables(): void
    {
        // Insert test timetable
        $stmt = $this->pdo->prepare("INSERT INTO module_timetable (module_id, staff_id, type, start_time, end_time, date, location)
                                     VALUES ('CS101', 2001, 'Lecture', '09:00', '11:00', '2025-04-20', 'Room A')");
        $stmt->execute();

        // Update timetable
        $stmt = $this->pdo->prepare("UPDATE module_timetable SET location = 'Room B' WHERE module_id = 'CS101'");
        $stmt->execute();

        // Verify update
        $stmt = $this->pdo->query("SELECT location FROM module_timetable WHERE module_id = 'CS101'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Room B', $result['location'], 'Timetable update failed');

        // Delete timetable
        $stmt = $this->pdo->prepare("DELETE FROM module_timetable WHERE module_id = 'CS101'");
        $stmt->execute();

        // Verify deletion
        $stmt = $this->pdo->query("SELECT * FROM module_timetable WHERE module_id = 'CS101'");
        $this->assertFalse($stmt->fetch(), 'Timetable deletion failed');
    }

    public function testConflictDetectionOnScheduling(): void
    {
        // Insert a non-conflicting timetable entry
        $this->pdo->exec("INSERT INTO module_timetable (module_id, staff_id, type, start_time, end_time, date, location)
                          VALUES ('CS101', 2002, 'Lecture', '10:00', '12:00', '2025-04-21', 'Room X')");

        // Simulate conflict detection logic manually (or check if constraint is enforced)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM module_timetable
            WHERE staff_id = 2002
              AND date = '2025-04-21'
              AND (
                (start_time, end_time) OVERLAPS (TIME '11:00', TIME '13:00')
              )
        ");
        $stmt->execute();
        $conflictCount = $stmt->fetchColumn();

        $this->assertGreaterThan(0, $conflictCount, 'Conflict was not detected for overlapping schedule');
    }

    public function testTimetableVisibleToStudentAndLecturer(): void
    {
        // Insert timetable for testing
        $this->pdo->exec("INSERT INTO module_timetable (module_id, staff_id, type, start_time, end_time, date, location)
                          VALUES ('CS101', 2003, 'Seminar', '14:00', '16:00', '2025-04-22', 'Room C')");

        // Simulate student view
        $stmt = $this->pdo->prepare("SELECT * FROM module_timetable WHERE module_id = 'CS101'");
        $stmt->execute();
        $studentView = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($studentView, 'Student cannot view the timetable');
        $this->assertEquals('Seminar', $studentView['type'], 'Incorrect session type for student');

        // Simulate lecturer view
        $stmt = $this->pdo->prepare("SELECT * FROM module_timetable WHERE staff_id = 2003");
        $stmt->execute();
        $lecturerView = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($lecturerView, 'Lecturer cannot view their timetable');
    }
}
