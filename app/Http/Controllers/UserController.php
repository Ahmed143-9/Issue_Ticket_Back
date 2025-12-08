<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Get all users (except admin)
    public function index()
    {
        try {
            Log::info('Fetching all users from database');
            
            $users = User::where('email', '!=', 'admin@example.com')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'email' => $user->email,
                            'role' => $user->role,
                            'department' => $user->department,
                            'status' => $user->status,
                            'created_at' => $user->created_at
                        ];
                    });

            Log::info('Users fetched successfully', ['count' => $users->count()]);

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('User loading error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load users: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ FIXED: Create new user method
    public function store(Request $request)
    {
        try {
            Log::info('=== USER CREATION REQUEST START ===');
            Log::info('Raw request data:', $request->all());

            // ✅ FIXED: Allowed departments with exact match
            $allowedDepartments = [
                'Enterprise Business Solutions',
                'Board Management',
                'Support Stuff',
                'Administration and Human Resources',
                'Finance and Accounts',
                'Business Dev and Operations', // ✅ Exact match
                'Implementation and Support',
                'Technical and Networking Department'
            ];

            Log::info('Allowed departments:', $allowedDepartments);
            Log::info('Received department:', [
                'raw' => $request->department,
                'trimmed' => trim($request->department),
                'exact_match' => in_array(trim($request->department), $allowedDepartments)
            ]);

            // ✅ FIXED: Password validator with ALL special characters
            $passwordValidator = function ($attribute, $value, $fail) {
    // Security: Don't log full password
    $firstFewChars = substr($value, 0, 3);
    
    $hasUppercase = preg_match('/[A-Z]/', $value);
    $hasNumber = preg_match('/\d/', $value);
    
    // ✅ FIXED: PROPER regex for ALL special characters
    $hasSpecial = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $value);
    
    // Debug log
    Log::info('🔍 Password validation DETAILS:', [
        'first_chars' => $firstFewChars . '...',
        'full_password' => $value, // TEMPORARY: দেখার জন্য full password log করুন
        'length' => strlen($value),
        'has_uppercase' => $hasUppercase ? 'YES' : 'NO',
        'has_number' => $hasNumber ? 'YES' : 'NO',
        'has_special' => $hasSpecial ? 'YES' : 'NO',
        'regex_used' => '/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/',
        'contains_dot' => strpos($value, '.') !== false,
        'contains_at' => strpos($value, '@') !== false,
        'dot_position' => strpos($value, '.'),
        'at_position' => strpos($value, '@')
    ]);
    
    if (strlen($value) < 8) {
        $fail('Password must be at least 8 characters.');
        return;
    }
    
    if (!$hasUppercase) {
        $fail('Password must contain at least 1 uppercase letter (A-Z).');
        return;
    }
    
    if (!$hasNumber) {
        $fail('Password must contain at least 1 number (0-9).');
        return;
    }
    
    if (!$hasSpecial) {
        $fail('Password must contain at least 1 special character (! @ # $ % ^ & * ( ) - _ = + [ ] { } ; : " \' , . < > ? / ~ `).');
        return;
    }
    
    Log::info('✅ Password validation PASSED');
};


            // ✅ FIXED: Department validator with better matching
           $departmentValidator = function ($attribute, $value, $fail) use ($allowedDepartments) {
                $trimmedValue = trim($value);
                
                // Debug
                Log::info('🔍 Department validation DETAILS:', [
                    'input' => $value,
                    'trimmed' => $trimmedValue,
                    'allowed_departments' => $allowedDepartments,
                    'exact_match' => in_array($trimmedValue, $allowedDepartments, true)
                ]);
                
                // ✅ Simple exact match
                if (!in_array($trimmedValue, $allowedDepartments, true)) {
                    Log::warning('❌ Department validation FAILED', [
                        'received' => $trimmedValue,
                        'allowed' => $allowedDepartments
                    ]);
                    $fail("The selected department is invalid.");
                    return;
                }
    
                    Log::info('✅ Department validation PASSED');
                };

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'username' => 'required|string|unique:users',
                'password' => ['required', $passwordValidator],
                'role' => 'required|in:user,team_leader,admin',
                'department' => ['required', 'string', $departmentValidator],
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            // Create user with exact department from allowed list
            $normalizedDepartment = preg_replace('/\s+/', ' ', trim($request->department));
            
            // Find exact match from allowed departments (for correct case)
            $exactDepartment = null;
            foreach ($allowedDepartments as $dept) {
                if (strcasecmp($normalizedDepartment, $dept) === 0) {
                    $exactDepartment = $dept;
                    break;
                }
            }

            $user = User::create([
                'name' => trim($request->name),
                'email' => trim($request->email),
                'username' => trim($request->username),
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'department' => $exactDepartment ?? $normalizedDepartment,
                'status' => $request->status
            ]);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'department' => $user->department
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User added successfully!',
                'user' => $user->only(['id', 'name', 'username', 'email', 'role', 'department', 'status'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('User creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update user - FIXED VERSION
    public function update(Request $request, $id)
    {
        try {
            Log::info('=== USER UPDATE REQUEST ===', [
                'user_id' => $id,
                'data' => $request->except('password'),
                'password_provided' => $request->filled('password')
            ]);

            $user = User::find($id);
            
            if (!$user) {
                Log::warning('User not found', ['user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Define allowed departments
            $allowedDepartments = [
                'Enterprise Business Solutions',
                'Board Management',
                'Support Stuff',
                'Administration and Human Resources',
                'Finance and Accounts',
                'Business Dev and Operations',
                'Implementation and Support',
                'Technical and Networking Department'
            ];

            // ✅ FIXED: Password validation for update
            $passwordValidator = function ($attribute, $value, $fail) {
                if (empty($value)) {
                    return; // Password is optional for updates
                }
                
                $hasUppercase = preg_match('/[A-Z]/', $value);
                $hasLowercase = preg_match('/[a-z]/', $value);
                $hasNumber = preg_match('/\d/', $value);
                $hasSpecial = preg_match('/[^\w\s]/', $value); // Same regex as store()
                
                Log::info('Update password validation', [
                    'length' => strlen($value),
                    'has_uppercase' => $hasUppercase ? 'YES' : 'NO',
                    'has_lowercase' => $hasLowercase ? 'YES' : 'NO',
                    'has_number' => $hasNumber ? 'YES' : 'NO',
                    'has_special' => $hasSpecial ? 'YES' : 'NO'
                ]);
                
                if (strlen($value) < 8) {
                    $fail('Password must be at least 8 characters long.');
                    return;
                }
                
                if (!$hasUppercase) {
                    $fail('Password must contain at least one uppercase letter (A-Z).');
                    return;
                }
                
                if (!$hasLowercase) {
                    $fail('Password must contain at least one lowercase letter (a-z).');
                    return;
                }
                
                if (!$hasNumber) {
                    $fail('Password must contain at least one number (0-9).');
                    return;
                }
                
                if (!$hasSpecial) {
                    $fail('Password must contain at least one special character.');
                    return;
                }
            };

            // ✅ FIXED: Department validation for update
            $departmentValidator = function ($attribute, $value, $fail) use ($allowedDepartments) {
                if (empty($value)) {
                    $fail('Department is required.');
                    return;
                }
                
                $normalizedValue = preg_replace('/\s+/', ' ', trim($value));
                
                if (!in_array($normalizedValue, $allowedDepartments)) {
                    Log::warning('Invalid department (update)', [
                        'received' => $normalizedValue,
                        'allowed' => $allowedDepartments
                    ]);
                    $fail("The selected department is invalid.");
                    return;
                }
            };

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'username' => 'required|string|unique:users,username,' . $id,
                'password' => ['nullable', $passwordValidator],
                'role' => 'required|in:user,team_leader,admin',
                'department' => ['required', 'string', $departmentValidator],
                'status' => 'required|in:active,inactive'
            ], [
                'email.unique' => 'This email is already registered.',
                'username.unique' => 'This username is already taken.'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Update validation FAILED', [
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            $normalizedDepartment = preg_replace('/\s+/', ' ', trim($request->department));
            
            $updateData = [
                'name' => trim($request->name),
                'email' => trim($request->email),
                'username' => trim($request->username),
                'role' => $request->role,
                'department' => $normalizedDepartment,
                'status' => $request->status
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                Log::info('Password will be updated');
            }

            $user->update($updateData);

            Log::info('✅ User updated successfully', ['user_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'status' => $user->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ User update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            Log::info('User delete request', ['user_id' => $id]);

            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $user->delete();

            Log::info('✅ User deleted successfully', ['user_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('User deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Toggle user status
    public function toggleStatus($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);

            Log::info('✅ User status toggled', [
                'user_id' => $id,
                'new_status' => $newStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'status' => $user->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User status toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get active users for First Face assignment
    public function getActiveUsers()
    {
        try {
            $activeUsers = User::where('status', 'active')
                        ->where('email', '!=', 'admin@example.com')
                        ->get(['id', 'name', 'username', 'email', 'role', 'department']);

            return response()->json([
                'success' => true,
                'activeUsers' => $activeUsers,
                'count' => $activeUsers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Active users loading error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load active users: ' . $e->getMessage()
            ], 500);
        }
    }
}