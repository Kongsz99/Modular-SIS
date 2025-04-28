<?php

use PHPUnit\Framework\TestCase;

class AcademicManagementTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Clean up
        $this->pdo->exec("DELETE FROM programme_module");
        // $this->pdo->exec(statement: "DELETE FROM programme");
        $this->pdo->exec("DELETE FROM modules");

        // Insert test data
        // $this->pdo->exec("INSERT INTO programme (programme_id, programme_name, department_id, duration_years, local_fees, international_fees, description)
        //                   VALUES ('UNCS01', 'Computer Science', 'CS', 3, 12000.00, 15000.00, 'BSc in Computer Science')");
    }

    public function testProgrammeCRUD()
    {
        // Add
        $this->pdo->exec("INSERT INTO programme (programme_id, programme_name, department_id, duration_years)
                          VALUES ('UNCS02', 'Software Engineering', 'CS', 3)");

        $stmt = $this->pdo->query("SELECT * FROM programme WHERE programme_id = 'UNCS02'");
        $result = $stmt->fetch();
        $this->assertEquals('Software Engineering', $result['programme_name']);

        // Edit
        $this->pdo->exec("UPDATE programme SET programme_name = 'Updated SE' WHERE programme_id = 'UNCS02'");
        $stmt = $this->pdo->query("SELECT programme_name FROM programme WHERE programme_id = 'UNCS02'");
        $this->assertEquals('Updated SE', $stmt->fetchColumn());

        // Delete
        $this->pdo->exec("DELETE FROM programme WHERE programme_id = 'UNCS02'");
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM programme WHERE programme_id = 'UNCS02'");
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testAssignUnassignModuleToProgramme()
    {
        $this->pdo->exec("INSERT INTO modules (module_id, module_name, department_id, semester, level, credits, exam_weight, assignment_weight, available_slots)
                          VALUES ('CS101', 'Intro to CS', 'CS', 1, 1, 15, 60, 40, 100)");

        $this->pdo->exec("INSERT INTO programme_module (programme_id, module_id, module_type)
                          VALUES ('UNCS01', 'CS101', 'Optional')");

        // Verify assign
        $stmt = $this->pdo->query("SELECT module_type FROM programme_module WHERE programme_id = 'UNCS01' AND module_id = 'CS101'");
        $this->assertEquals('Optional', $stmt->fetchColumn());

        // Unassign
        $this->pdo->exec("DELETE FROM programme_module WHERE programme_id = 'UNCS01' AND module_id = 'CS101'");
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM programme_module WHERE module_id = 'CS101'");
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    // public function testModuleDeletionBlockedIfActiveEnrollment()
    // {
    //     $this->pdo->exec("INSERT INTO modules (module_id, module_name, department_id, semester, level, credits, exam_weight, assignment_weight, available_slots)
    //                       VALUES ('CS102', 'Data Structures', 'CS', 2, 2, 15, 50, 50, 60)");

    //     // Simulate active enrollment
    //     $this->pdo->exec("INSERT INTO programme_module (programme_id, module_id)
    //                       VALUES ('UNCS01', 'CS102')");

    //     try {
    //         $this->pdo->exec("DELETE FROM modules WHERE module_id = 'CS102'");
    //         $this->fail("Expected foreign key constraint violation.");
    //     } catch (PDOException $e) {
    //         $this->assertStringContainsString('violates foreign key constraint', $e->getMessage());
    //     }
    // }

    public function testGradeCalculation()
    {
        $examScore = 70;
        $assignmentScore = 80;

        $examWeight = 0.6;
        $assignmentWeight = 0.4;

        $finalGrade = ($examScore * $examWeight) + ($assignmentScore * $assignmentWeight);
        $this->assertEquals(74, $finalGrade);
    }
  
    
    // protected function tearDown(): void
    // {
    //     $this->pdo->rollBack(); // Undoes everything
    //     $this->pdo = null;
    // }   
}
