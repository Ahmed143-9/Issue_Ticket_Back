<?php
// Test regex patterns for password validation
echo "Testing password validation regex patterns\n\n";

$testPasswords = [
    'Admin@123',
    'Admin@123.',
    'Ashraf.Haque@@123'
];

$regex = '/[@$!%*?&.]/';

foreach ($testPasswords as $password) {
    echo "Password: $password\n";
    echo "Matches special char regex: " . (preg_match($regex, $password) ? 'Yes' : 'No') . "\n";
    echo "All matches: ";
    preg_match_all($regex, $password, $matches);
    print_r($matches[0]);
    echo "\n---\n";
}
?>