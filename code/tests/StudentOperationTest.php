<?php

use PHPUnit\Framework\TestCase;

class StudentOperationTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Clean all test data
        $this->pdo->exec("DELETE FROM student_modules WHERE student_id IN ('31000001', '31000002')");
        $this->pdo->exec("DELETE FROM programme_enrolment WHERE student_id IN ('31000001', '31000002')");
        $this->pdo->exec("DELETE FROM modules WHERE module_id IN ('CSSE00', 'CSSE01', 'CSSE02', 'CSSE03')");

        // Insert enrolment record
        $this->pdo->exec("INSERT INTO programme_enrolment (student_id, programme_id, programme_start_date, academic_year, current_year, progress_step)
                          VALUES ('31000001', 'UNCS01', '2024-01-01', '2024/5', 1, 0)");
    }

    public function testAcknowledgePoliciesStep1()
    {
        $stmt = $this->pdo->prepare("UPDATE programme_enrolment SET progress_step = 1 WHERE student_id = ?");
        $stmt->execute(['31000001']);

        $stmt = $this->pdo->prepare("SELECT progress_step FROM programme_enrolment WHERE student_id = ?");
        $stmt->execute(['31000001']);
        $result = $stmt->fetch();

        $this->assertEquals(1, $result['progress_step']);
    }

    public function testConfirmPersonalDetailsStep2()
    {
        $stmt = $this->pdo->prepare("UPDATE programme_enrolment SET progress_step = 2 WHERE student_id = ?");
        $stmt->execute(['31000001']);

        $stmt = $this->pdo->prepare("SELECT progress_step FROM programme_enrolment WHERE student_id = ?");
        $stmt->execute(['31000001']);
        $result = $stmt->fetch();

        $this->assertEquals(2, $result['progress_step']);
    }

    public function testOptionalModuleEnrollmentStep3()
    {
        $this->pdo->exec("INSERT INTO modules VALUES (
            'CSSE02', 'Human-Centered Computing', 'CS', 1, 1, 15, 70, 30, 20, NULL)");

        $this->pdo->exec("INSERT INTO student_modules (student_id, module_id, academic_year)
                          VALUES ('31000001', 'CSSE02', '2024/5')");

        $stmt = $this->pdo->prepare("SELECT * FROM student_modules WHERE student_id = ? AND module_id = ?");
        $stmt->execute(['31000001', 'CSSE02']);
        $result = $stmt->fetch();

        $this->assertNotEmpty($result);
        $this->assertEquals('31000001', $result['student_id']);
    }

    public function testFinalStep4Completion()
    {
        $stmt = $this->pdo->prepare("UPDATE programme_enrolment SET progress_step = 4 WHERE student_id = ?");
        $stmt->execute(['31000001']);

        $stmt = $this->pdo->prepare("SELECT progress_step FROM programme_enrolment WHERE student_id = ?");
        $stmt->execute(['31000001']);
        $result = $stmt->fetch();

        $this->assertEquals(4, $result['progress_step']);
    }

    private function canEnrollInModule($studentId, $moduleId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM modules WHERE module_id = ?");
        $stmt->execute([$moduleId]);
        $module = $stmt->fetch();

        if (!$module) return false;

        if ((int)$module['available_slots'] <= 0) return false;

        if ($module['prerequisite_module_id']) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM student_modules
                                         WHERE student_id = ? AND module_id = ?");
            $stmt->execute([$studentId, $module['prerequisite_module_id']]);
            $completed = $stmt->fetchColumn();
            if ($completed == 0) return false;
        }

        return true;
    }

    // public function testEnrollmentSucceedsIfPrerequisiteIsMet()
    // {
    //     $this->pdo->exec("INSERT INTO modules VALUES (
    //         'CSSE00', 'Intro to Computing', 'CS', 1, 1, 15, 70, 30, 30, NULL)");

    //     $this->pdo->exec("INSERT INTO modules VALUES (
    //         'CSSE01', 'Software Engineering', 'CS', 1, 1, 15, 70, 30, 25, 'CSSE00')");

    //     $this->pdo->exec("INSERT INTO student_modules (student_id, module_id, academic_year)
    //                       VALUES ('31000001', 'CSSE00', '2023/2024')");

    //     $canEnroll = $this->canEnrollInModule('31000002', 'CSSE01');
    //     $this->assertTrue($canEnroll, "Enrollment should succeed if prerequisite is met.");
    // }
    // public function testEnrollmentFailsIfPrerequisiteNotMet()
    // {
    //     $this->pdo->exec("INSERT INTO modules VALUES (
    //         'CSSE02', 'Advanced Networks', 'CS', 2, 2, 15, 70, 30, 10, 'CSSE01')");

    //     $canEnroll = $this->canEnrollInModule('31000001', 'CSSE02');
    //     $this->assertFalse($canEnroll, "Enrollment should fail if prerequisite is not met.");
    // }


    public function testEnrollmentFailsIfSlotsAreFull()
    {
        $this->pdo->exec("INSERT INTO modules VALUES (
            'CSSE02', 'Advanced Networks', 'CS', 2, 2, 15, 70, 30, 0, NULL)");

        $canEnroll = $this->canEnrollInModule('31000001', 'CSSE02');
        $this->assertFalse($canEnroll, "Enrollment should fail if no slots are available.");
    }

    public function testSuccessfulEnrollmentWhenAllConditionsMet()
    {
        $this->pdo->exec("INSERT INTO modules VALUES (
            'CSSE03', 'Cloud Architecture', 'CS', 2, 2, 15, 60, 40, 5, NULL)");

        $canEnroll = $this->canEnrollInModule('31000002', 'CSSE03');
        $this->assertTrue($canEnroll, "Enrollment should succeed when slots are available and no prerequisites.");
    }

    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM student_modules WHERE student_id IN ('31000001', '31000002')");
        $this->pdo->exec("DELETE FROM programme_enrolment WHERE student_id IN ('31000001', '31000002')");
        $this->pdo->exec("DELETE FROM modules WHERE module_id IN ('CSSE00', 'CSSE01', 'CSSE02', 'CSSE03')");
    }
}
