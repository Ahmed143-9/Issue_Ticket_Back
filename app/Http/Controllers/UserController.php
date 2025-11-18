<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // Get all users (except admin)
    public function index()
    {
        try {
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

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load users: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create new user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'username' => 'required|string|unique:users',
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'role' => 'required|in:user,team_leader,admin',
            'department' => 'required|string|in:IT & Innovation,Business,Accounts',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'department' => $request->department,
                'status' => $request->status
            ]);

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
            return response()->json([
                'success' => false,
                'error' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'username' => 'required|string|unique:users,username,' . $id,
            'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'role' => 'required|in:user,team_leader,admin',
            'department' => 'required|string|in:IT & Innovation,Business,Accounts',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
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
            }

            $user->update($updateData);

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
            return response()->json([
                'success' => false,
                'error' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Toggle user status
    public function toggleStatus($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        try {
            $user->update([
                'status' => $user->status === 'active' ? 'inactive' : 'active'
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
                            ->where('username', '!=', 'Admin')
                            ->get(['id', 'name', 'username', 'email', 'role', 'department']);

            return response()->json([
                'success' => true,
                'activeUsers' => $activeUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load active users: ' . $e->getMessage()
            ], 500);
        }
    }
}