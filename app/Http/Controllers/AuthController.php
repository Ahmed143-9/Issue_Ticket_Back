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
        try {
            \Log::info('Login attempt:', $request->all());

            // ✅ SIMPLE VALIDATION - কোন strict validation নেই
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                \Log::warning('Login validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                \Log::warning('User not found', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                \Log::warning('Password mismatch', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

            // Check if user is active
            if ($user->status !== 'active') {
                \Log::warning('User inactive', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'error' => 'Your account is inactive. Please contact administrator.'
                ], 401);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            \Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                    'department' => $user->department,
                    'status' => $user->status
                ],
                'message' => 'Login successful!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Logout successful!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Logout failed'
            ], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                    'department' => $user->department,
                    'status' => $user->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get user data'
            ], 500);
        }
    }
}