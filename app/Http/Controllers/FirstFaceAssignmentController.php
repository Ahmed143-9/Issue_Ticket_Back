<?php
// app/Http/Controllers/FirstFaceAssignmentController.php

namespace App\Http\Controllers;

use App\Models\FirstFaceAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FirstFaceAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Get all first face assignments
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

            $assignments = FirstFaceAssignment::with(['user', 'assigner'])
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'user_id' => $assignment->user_id,
                        'userName' => $assignment->user ? $assignment->user->name : 'Unknown User',
                        'department' => $assignment->department,
                        'type' => $assignment->type,
                        'assigned_by' => $assignment->assigner ? $assignment->assigner->name : 'Unknown',
                        'assignedAt' => $assignment->assigned_at ? $assignment->assigned_at->toISOString() : null,
                        'is_active' => $assignment->is_active
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $assignments,
                'message' => 'First face assignments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('First Face Assignment Load Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load first face assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    // Create new first face assignment
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

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
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

            // Deactivate existing assignments based on type
            if ($request->department === 'all') {
                // Deactivate all "all" type assignments
                FirstFaceAssignment::where('department', 'all')
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            } else {
                // Deactivate specific department assignments
                FirstFaceAssignment::where('department', $request->department)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $assignment = FirstFaceAssignment::create([
                'user_id' => $request->user_id,
                'department' => $request->department,
                'type' => $request->type,
                'assigned_by' => $authUser->id,
                'assigned_at' => now(),
                'is_active' => true
            ]);

            // Load relationships
            $assignment->load(['user', 'assigner']);

            return response()->json([
                'success' => true,
                'message' => 'First Face assigned successfully!',
                'data' => [
                    'id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'userName' => $assignment->user->name,
                    'department' => $assignment->department,
                    'type' => $assignment->type,
                    'assigned_by' => $assignment->assigner->name,
                    'assignedAt' => $assignment->assigned_at->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('First Face Assignment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to assign First Face: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update first face assignment
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
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            \Log::error('First Face Assignment Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete first face assignment
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

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('First Face Assignment Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}