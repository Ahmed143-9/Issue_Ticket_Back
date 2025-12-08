<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Validator;

// Test the password validation
$data = [
    'password' => 'Ashraf.Haque@@123',
    'department' => 'IT & Innovation'  // This should fail with the current validation
];

$validator = Validator::make($data, [
    'password' => 'required|string|min:8|regex:/[A-Z]/|regex:/\d/|regex:/[@$!%*?&.]/',
    'department' => 'required|string|in:Enterprise Business Solutions,Board Management,Support Stuff,Administration and Human Resources,Finance and Accounts,Business Dev and Operations,Implementation and Support,Technical and Networking Department'
]);

if ($validator->fails()) {
    print_r($validator->errors()->toArray());
} else {
    echo "Validation passed!\n";
}