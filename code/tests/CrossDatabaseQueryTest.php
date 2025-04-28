<?php

use PHPUnit\Framework\TestCase;
// use PDO;

class CrossDatabaseQueryTest extends TestCase
{
    private $pdo;

    // Setup the database connection
    protected function setUp(): void
    {
        $this->pdo = new PDO('pgsql:host=localhost;dbname=testing', 'postgres', 'kong9983');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Test for single cross-database query performance (Load Test)
    public function testSingleQueryPerformance()
    {
        // Define the query: Join users and students tables
        $query = "
            SELECT u.user_id, u.username, u.role_id, s.student_id, s.first_name, s.last_name
            FROM users u
            INNER JOIN students s ON u.user_id = s.user_id
            WHERE u.user_id = :user_id
        ";

        // Start measuring time
        $startTime = microtime(true);

        // Execute query for user_id 1001 (example)
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => 5032]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // End measuring time
        $endTime = microtime(true);

        // Calculate execution time
        $executionTime = $endTime - $startTime;

        // Assert the query returns within 2 seconds
        $this->assertNotEmpty($result, "Query returned no data.");
        $this->assertLessThanOrEqual(2, $executionTime, "Query took longer than expected: {$executionTime}s");
    }

    // Stress test: Simulate concurrent queries (Concurrency Test)
        public function testConcurrentQueriesPerformance()
    {
        // Known existing user IDs for test data
        $existingUserIds = [5032, 11, 14, 20, 22,24,26,29,32,5031,5032,2002];

        $queries = [];
        foreach ($existingUserIds as $id) {
            $stmt = $this->pdo->prepare("
                SELECT u.user_id, u.username, u.role_id, s.student_id, s.first_name, s.last_name
                FROM users u
                INNER JOIN students s ON u.user_id = s.user_id
                WHERE u.user_id = :user_id
            ");
            $queries[] = [$stmt, $id];
        }

        // Start timing
        $startTime = microtime(true);

        // Execute
        foreach ($queries as [$stmt, $id]) {
            $stmt->execute(['user_id' => $id]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert speed
        $this->assertLessThanOrEqual(5, $executionTime, "Queries took too long: {$executionTime}s");

        // Assert each returned something
        foreach ($queries as [$stmt, $id]) {
            $this->assertGreaterThan(0, $stmt->rowCount(), "Query for user_id {$id} returned no rows.");
        }
    }

    // Test for query result consistency (Data Consistency Test)
    public function testDataConsistency()
    {
        // Example query: Check if user and student data are consistent
        $query = "
            SELECT u.user_id, u.username, u.role_id, s.student_id, s.first_name, s.last_name
            FROM users u
            INNER JOIN students s ON u.user_id = s.user_id
            WHERE u.user_id = :user_id
        ";

        // Execute query for user_id 1001
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => 5032]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Assert the data is consistent
        $this->assertNotEmpty($result, "No data found for user_id 5032.");
        $this->assertEquals(5032, $result['user_id'], "User ID mismatch.");
        $this->assertEquals('ji7k555', $result['username'], "Username mismatch.");
        $this->assertNotEmpty($result['first_name'], "First name is empty.");
        $this->assertNotEmpty($result['last_name'], "Last name is empty.");
    }
}
