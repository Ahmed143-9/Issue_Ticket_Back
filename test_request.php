<?php
// Test script to simulate frontend requests

// Test data 1: Password without dot
$data1 = [
    'name' => 'Test User',
    'username' => 'test@example.com',
    'email' => 'test@example.com',
    'password' => 'Admin@123',
    'role' => 'user',
    'department' => 'Board Management',
    'status' => 'active'
];

// Test data 2: Password with dot
$data2 = [
    'name' => 'Test User',
    'username' => 'test@example.com',
    'email' => 'test@example.com',
    'password' => 'Admin@123.',
    'role' => 'user',
    'department' => 'Board Management',
    'status' => 'active'
];

echo "Test 1 - Password without dot:\n";
echo json_encode($data1, JSON_PRETTY_PRINT);
echo "\n\n";

echo "Test 2 - Password with dot:\n";
echo json_encode($data2, JSON_PRETTY_PRINT);
echo "\n\n";

// Test JSON parsing
$json1 = json_encode($data1);
$json2 = json_encode($data2);

echo "Parsing Test 1:\n";
$parsed1 = json_decode($json1, true);
print_r($parsed1);
echo "\n";

echo "Parsing Test 2:\n";
$parsed2 = json_decode($json2, true);
print_r($parsed2);
echo "\n";

// Check if department is preserved correctly
echo "Department in Test 1: " . ($parsed1['department'] ?? 'MISSING') . "\n";
echo "Department in Test 2: " . ($parsed2['department'] ?? 'MISSING') . "\n";
?>