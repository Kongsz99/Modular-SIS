<?php
// Database connection details
$databases = [
    'central' => ['host' => '127.0.0.1', 'dbname' => 'central_database', 'user' => 'postgres', 'password' => 'kong9983'],
    'cs' => ['host' => '127.0.0.1', 'dbname' => 'cs_database', 'user' => 'postgres', 'password' => 'kong9983'],
    'bm' => ['host' => '127.0.0.1', 'dbname' => 'bm_database', 'user' => 'postgres', 'password' => 'kong9983']
    // 'archi' => ['host' => 'localhost', 'dbname' => 'archi_database', 'user' => 'your_user', 'password' => 'your_password'],
];

// Function to get a connection to a specified database
function getDatabaseConnection($dbKey) {
    global $databases;
    if (!isset($databases[$dbKey])) {
        die("Database not found: " . $dbKey);
    }
    
    $db = $databases[$dbKey];
    $dsn = "pgsql:host={$db['host']};dbname={$db['dbname']}";
    
    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Example usage
$centralDb = getDatabaseConnection('central');
$csDb = getDatabaseConnection('cs');
$bmDb = getDatabaseConnection('bm');
?>
