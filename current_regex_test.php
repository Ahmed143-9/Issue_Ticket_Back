<?php
// Test current regex pattern from the code

function validatePassword($password) {
    echo "Validating password: '$password'\n";
    
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/\d/', $password);
    $hasSpecial = preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password);
    
    echo "Has uppercase: " . ($hasUppercase ? 'YES' : 'NO') . "\n";
    echo "Has number: " . ($hasNumber ? 'YES' : 'NO') . "\n";
    echo "Has special ([!@#$%^&*()\-_=+{};:,<.>]): " . ($hasSpecial ? 'YES' : 'NO') . "\n";
    
    if ($hasSpecial) {
        preg_match_all('/[!@#$%^&*()\-_=+{};:,<.>]/', $password, $matches);
        echo "Special chars found: " . implode(', ', $matches[0]) . "\n";
    }
    
    $isValid = (strlen($password) >= 8) && $hasUppercase && $hasNumber && $hasSpecial;
    echo "Overall valid: " . ($isValid ? 'YES' : 'NO') . "\n\n";
    
    return $isValid;
}

$passwords = [
    'Admin@123',    // Should pass
    'Admin@123.',   // Should pass
    'Ashraf.Haque@@123' // Should pass
];

foreach ($passwords as $pwd) {
    validatePassword($pwd);
}
?>