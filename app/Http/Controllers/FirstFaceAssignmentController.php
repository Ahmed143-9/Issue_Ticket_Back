<?php
// app/Http/Controllers/FirstFaceAssignmentController.php

namespace App\Http\Controllers;

use App\Models\FirstFaceAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FirstFaceAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Get all first face assignments - UPDATED VERSION
    public function index(): JsonResponse
    {
        try {
            $authUser = auth()->user();
            
            // Check if user is admin or team leader
            if (!in_array($authUser->role, ['admin', 'team_leader'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only Admin or Team Leader can access first face assignments'
                ], 403);
            }

            // Get only active assignments with user data
            $assignments = FirstFaceAssignment::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($assignment) {
                    $user = User::find($assignment->user_id);
                    $assigner = User::find($assignment->assigned_by);
                    
                    return [
                        'id' => $assignment->id,
                        'user_id' => $assignment->user_id,
                        'user' => $user ? [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'department' => $user->department
                        ] : null,
                        'department' => $assignment->department,
                        'type' => $assignment->type,
                        'assigned_by' => $assigner ? $assigner->name : 'Unknown',
                        'is_active' => $assignment->is_active,
                        'created_at' => $assignment->created_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'firstFaceAssignments' => $assignments, // ✅ Changed from 'data' to 'firstFaceAssignments'
                'count' => $assignments->count(),
                'message' => 'First face assignments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('First Face Assignment Load Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load first face assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create new first face assignment - UPDATED VERSION
    public function store(Request $request): JsonResponse
    {
        try {
            $authUser = auth()->user();
            
            if ($authUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only Admin can assign First Face'
                ], 403);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'department' => 'required|string|in:all,IT & Innovation,Business,Accounts',
                'type' => 'required|in:all,specific'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user exists and is active
            $user = User::where('id', $request->user_id)
                       ->where('status', 'active')
                       ->first();
                       
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Selected user not found or inactive'
                ], 404);
            }

            // Check for existing active assignment for this department
            $existingAssignment = FirstFaceAssignment::where('department', $request->department)
                ->where('is_active', true)
                ->first();

            if ($existingAssignment) {
                $existingUser = User::find($existingAssignment->user_id);
                return response()->json([
                    'success' => false,
                    'error' => "First Face already assigned to {$existingUser->name} for {$request->department}"
                ], 400);
            }

            // If assigning for "all" departments, deactivate all other assignments
            if ($request->department === 'all') {
                FirstFaceAssignment::where('is_active', true)->update(['is_active' => false]);
            }

            // Create new assignment
            $assignment = FirstFaceAssignment::create([
                'user_id' => $request->user_id,
                'department' => $request->department,
                'type' => $request->type,
                'assigned_by' => $authUser->id,
                'is_active' => true
            ]);

            Log::info('First Face assigned successfully', [
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'department' => $assignment->department,
                'assigned_by' => $authUser->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'First Face assigned successfully!',
                'assignment' => [ // ✅ Changed from 'data' to 'assignment'
                    'id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'userName' => $user->name,
                    'department' => $assignment->department,
                    'type' => $assignment->type,
                    'assigned_by' => $authUser->name
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('First Face Assignment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to assign First Face: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update first face assignment - UPDATED VERSION
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $authUser = auth()->user();
            
            if ($authUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only Admin can update First Face assignments'
                ], 403);
            }

            $assignment = FirstFaceAssignment::find($id);
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Assignment not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'department' => 'sometimes|string|in:all,IT & Innovation,Business,Accounts',
                'type' => 'sometimes|in:all,specific',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $assignment->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment updated successfully!',
                'assignment' => $assignment // ✅ Changed from 'data' to 'assignment'
            ]);

        } catch (\Exception $e) {
            Log::error('First Face Assignment Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete first face assignment - UPDATED VERSION
    public function destroy($id): JsonResponse
    {
        try {
            $authUser = auth()->user();
            
            if ($authUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only Admin can delete First Face assignments'
                ], 403);
            }

            $assignment = FirstFaceAssignment::find($id);
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Assignment not found'
                ], 404);
            }

            $assignment->delete();

            Log::info('First Face assignment deleted', [
                'assignment_id' => $id,
                'deleted_by' => $authUser->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('First Face Assignment Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}