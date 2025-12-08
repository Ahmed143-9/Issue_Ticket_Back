<?php
// Detailed test of password validation

function validatePassword($password) {
    echo "Validating password: '$password'\n";
    echo "Length: " . strlen($password) . " (>= 8: " . (strlen($password) >= 8 ? 'YES' : 'NO') . ")\n";
    
    // Check for uppercase
    $hasUpper = preg_match('/[A-Z]/', $password);
    echo "Has uppercase: " . ($hasUpper ? 'YES' : 'NO') . "\n";
    if ($hasUpper) {
        preg_match_all('/[A-Z]/', $password, $upperMatches);
        echo "  Matches: " . implode(', ', $upperMatches[0]) . "\n";
    }
    
    // Check for numbers
    $hasNumber = preg_match('/\d/', $password);
    echo "Has number: " . ($hasNumber ? 'YES' : 'NO') . "\n";
    if ($hasNumber) {
        preg_match_all('/\d/', $password, $numMatches);
        echo "  Matches: " . implode(', ', $numMatches[0]) . "\n";
    }
    
    // Check for special characters
    $hasSpecial = preg_match('/[@$!%*?&.]/', $password);
    echo "Has special char: " . ($hasSpecial ? 'YES' : 'NO') . "\n";
    if ($hasSpecial) {
        preg_match_all('/[@$!%*?&.]/', $password, $specMatches);
        echo "  Matches: " . implode(', ', $specMatches[0]) . "\n";
    }
    
    $isValid = (strlen($password) >= 8) && $hasUpper && $hasNumber && $hasSpecial;
    echo "Overall valid: " . ($isValid ? 'YES' : 'NO') . "\n\n";
    
    return $isValid;
}

$passwords = [
    'Admin@123',
    'Admin@123.'
];

foreach ($passwords as $pwd) {
    validatePassword($pwd);
}
?>