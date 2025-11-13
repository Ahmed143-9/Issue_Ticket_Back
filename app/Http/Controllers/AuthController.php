<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Simple validation
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Please fill in all fields'
            ], 422);
        }

        try {
            // Debug log
            \Log::info('Login attempt', ['username' => $request->username]);

            // Find user by username or email
            $user = User::where('username', $request->username)
                        ->orWhere('email', $request->username)
                        ->first();

            \Log::info('User found:', ['user' => $user ? $user->toArray() : 'Not found']);

            if (!$user) {
                \Log::warning('User not found', ['username' => $request->username]);
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 401);
            }

            // Check password
            $passwordMatch = Hash::check($request->password, $user->password);
            \Log::info('Password check:', [
                'input_password' => $request->password,
                'stored_hash' => $user->password,
                'match' => $passwordMatch
            ]);

            if (!$passwordMatch) {
                \Log::warning('Invalid password', ['username' => $request->username]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid password'
                ], 401);
            }

            // Check if user is active
            if ($user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'error' => 'Your account is inactive. Please contact admin.'
                ], 403);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            // User data to return
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'department' => $user->department,
                'status' => $user->status
            ];

            \Log::info('Login successful', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'user' => $userData,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ... other methods
}