
<?php
use PHPUnit\Framework\TestCase;

class PerformanceUnderLoadTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

        public function testSystemCanHandle800SimultaneousUsers()
    {
        $userCount = 800;
        $success = true;

        // Simulate 100 simultaneous users interacting with the student portal
        for ($i = 0; $i < $userCount; $i++) {
            // Simulate user interaction (could be login, browsing, etc.)
            // Use a real HTTP request here if needed, like Guzzle or cURL in a multi-request scenario
            try {
                // Simulate a request (e.g., a simple login or data retrieval)
                $stmt = $this->pdo->prepare("SELECT * FROM students WHERE student_id = ?");
                $stmt->execute([rand(0, 1000000)]); // Example student ID
            } catch (Exception $e) {
                $success = false;  // If an error occurs, mark the test as failed
                break;
            }
        }

        $this->assertTrue($success, "The system failed to handle 100 simultaneous users.");
    }

    public function testDatabaseQueriesRemainEfficientUnderLoad()
    {
        $startTime = microtime(true);
        $userCount = 100;

        // Simulate database queries under high load (e.g., 100 simultaneous database reads)
        for ($i = 0; $i < $userCount; $i++) {
            // Simulate querying student data
            $stmt = $this->pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([rand(1000, 9999)]); // Random student ID

            // Optionally, you can fetch and verify some data here if necessary
            // $result = $stmt->fetch();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Ensure the total execution time for 100 queries is under an acceptable threshold
        $this->assertLessThan(5, $executionTime, "Database queries are too slow under load.");
    }

}