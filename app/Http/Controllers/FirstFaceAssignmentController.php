<?php
// app/Http\Controllers\FirstFaceAssignmentController.php

namespace App\Http\Controllers;

use App\Models\FirstFaceAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FirstFaceAssignmentController extends Controller
{
    // ✅ NO MIDDLEWARE - Authentication ছাড়াই কাজ করবে
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    // ✅ GET all first face assignments
    public function index(): JsonResponse
    {
        try {
            Log::info('Fetching first face assignments...');
            
            $assignments = FirstFaceAssignment::with(['user'])
                ->where('is_active', true)
                ->get();

            Log::info('First face assignments fetched successfully', [
                'count' => $assignments->count()
            ]);

            return response()->json([
                'success' => true,
                'firstFaceAssignments' => $assignments,
                'count' => $assignments->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch first face assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch first face assignments: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ CREATE new first face assignment
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Creating first face assignment...', $request->all());

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'department' => 'required|string|in:all,Enterprise Business Solutions,Board Management,Support Stuff,Administration and Human Resources,Finance and Accounts,Business Dev and Operations,Implementation and Support,Technical and Networking Department',
                'type' => 'required|in:all,specific'
            ]);

            Log::info('Validation passed', $validated);

            // Check if user exists
            $user = User::find($validated['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Deactivate existing assignments
            if ($validated['type'] === 'specific') {
                FirstFaceAssignment::where('department', $validated['department'])
                    ->update(['is_active' => false]);
            } else {
                FirstFaceAssignment::where('is_active', true)
                    ->update(['is_active' => false]);
            }

            // Create new assignment
            $assignment = FirstFaceAssignment::create([
                'user_id' => $validated['user_id'],
                'department' => $validated['department'],
                'type' => $validated['type'],
                'assigned_by' => 1, // Default admin ID
                'is_active' => true
            ]);

            $assignment->load(['user']);

            Log::info('First Face assignment created successfully', [
                'assignment_id' => $assignment->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment created successfully',
                'assignment' => $assignment
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create first face assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create first face assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE first face assignment
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $assignment = FirstFaceAssignment::find($id);
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'First Face assignment not found'
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'department' => 'sometimes|string|in:all,Enterprise Business Solutions,Board Management,Support Stuff,Administration and Human Resources,Finance and Accounts,Business Dev and Operations,Implementation and Support,Technical and Networking Department',
                'type' => 'sometimes|in:all,specific',
                'is_active' => 'sometimes|boolean'
            ]);

            $assignment->update($validated);
            $assignment->load(['user']);

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment updated successfully',
                'assignment' => $assignment
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update first face assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update first face assignment'
            ], 500);
        }
    }

    // ✅ DELETE first face assignment
    public function destroy($id): JsonResponse
    {
        try {
            $assignment = FirstFaceAssignment::find($id);
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'First Face assignment not found'
                ], 404);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete first face assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete first face assignment'
            ], 500);
        }
    }

    // ✅ TOGGLE active status
    public function toggleActive($id): JsonResponse
    {
        try {
            $assignment = FirstFaceAssignment::find($id);
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'First Face assignment not found'
                ], 404);
            }

            $assignment->update([
                'is_active' => !$assignment->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'First Face assignment status updated',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle first face assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update assignment status'
            ], 500);
        }
    }
}