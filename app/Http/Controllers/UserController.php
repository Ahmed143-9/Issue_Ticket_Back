<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ✅ TEMPORARILY REMOVE MIDDLEWARE FOR TESTING
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    // Get all users (except admin)
    public function index()
    {
        try {
            \Log::info('Fetching all users from database');
            
            $users = User::where('username', '!=', 'Admin')
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

            \Log::info('Users fetched successfully', ['count' => $users->count()]);

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('User loading error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load users: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create new user - ✅ FIXED VERSION
    public function store(Request $request)
    {
        try {
            \Log::info('User creation request received', $request->all());

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'username' => 'required|string|unique:users',
                'password' => ['required', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
                'role' => 'required|in:user,team_leader,admin',
                'department' => 'required|string|in:IT & Innovation,Business,Accounts',
                'status' => 'required|in:active,inactive'
            ], [
                'password.regex' => 'Password must contain at least 1 uppercase letter, 1 number, and 1 special character.',
                'email.unique' => 'This email is already registered.',
                'username.unique' => 'This username is already taken.'
            ]);

            if ($validator->fails()) {
                \Log::warning('User creation validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'department' => $request->department,
                'status' => $request->status
            ]);

            \Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'message' => 'User added successfully!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'status' => $user->status
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('User creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update user - ✅ FIXED VERSION
    public function update(Request $request, $id)
    {
        try {
            \Log::info('User update request', ['user_id' => $id, 'data' => $request->all()]);

            $user = User::find($id);
            
            if (!$user) {
                \Log::warning('User not found for update', ['user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'username' => 'required|string|unique:users,username,' . $id,
                'password' => ['nullable', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
                'role' => 'required|in:user,team_leader,admin',
                'department' => 'required|string|in:IT & Innovation,Business,Accounts',
                'status' => 'required|in:active,inactive'
            ], [
                'password.regex' => 'Password must contain at least 1 uppercase letter, 1 number, and 1 special character.',
                'email.unique' => 'This email is already registered.',
                'username.unique' => 'This username is already taken.'
            ]);

            if ($validator->fails()) {
                \Log::warning('User update validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'role' => $request->role,
                'department' => $request->department,
                'status' => $request->status
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                \Log::info('Password updated for user', ['user_id' => $id]);
            }

            $user->update($updateData);

            \Log::info('User updated successfully', ['user_id' => $id]);

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
            \Log::error('User update error: ' . $e->getMessage());
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
            \Log::info('User delete request', ['user_id' => $id]);

            $user = User::find($id);
            
            if (!$user) {
                \Log::warning('User not found for deletion', ['user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $user->delete();

            \Log::info('User deleted successfully', ['user_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('User deletion error: ' . $e->getMessage());
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
            \Log::info('User status toggle request', ['user_id' => $id]);

            $user = User::find($id);
            
            if (!$user) {
                \Log::warning('User not found for status toggle', ['user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);

            \Log::info('User status updated', ['user_id' => $id, 'new_status' => $newStatus]);

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
            \Log::error('User status toggle error: ' . $e->getMessage());
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
            \Log::info('Fetching active users');

            $activeUsers = User::where('status', 'active')
                            ->where('username', '!=', 'Admin')
                            ->get(['id', 'name', 'username', 'email', 'role', 'department']);

            \Log::info('Active users fetched', ['count' => $activeUsers->count()]);

            return response()->json([
                'success' => true,
                'activeUsers' => $activeUsers,
                'count' => $activeUsers->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Active users loading error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load active users: ' . $e->getMessage()
            ], 500);
        }
    }
}