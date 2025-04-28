<?php
// Example: Fetch departments from a predefined list
$departments = [
    ['department_id' => 'CS', 'department_name' => 'Computer Science'],
    ['department_id' => 'BM', 'department_name' => 'Business Management'],
    // Add more departments as needed
];

header('Content-Type: application/json');
echo json_encode($departments);