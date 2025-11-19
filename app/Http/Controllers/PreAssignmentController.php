<?php
// app/Http/Controllers/PreAssignmentController.php

namespace App\Http\Controllers;

use App\Models\PreAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PreAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // ✅ GET all pre-assignments
    public function index(): JsonResponse
    {
        try {
            $preAssignments = PreAssignment::with(['user'])
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'preAssignments' => $preAssignments,
                'count' => $preAssignments->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch pre-assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch pre-assignments'
            ], 500);
        }
    }

    // ✅ CREATE new pre-assignment
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'department' => 'required|string'
            ]);

            // Check if user exists and is active
            $user = User::where('id', $validated['user_id'])
                ->where('status', 'active')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found or inactive'
                ], 404);
            }

            // Deactivate any existing assignments for the same department
            PreAssignment::where('department', $validated['department'])
                ->update(['is_active' => false]);

            // Create new assignment
            $preAssignment = PreAssignment::create([
                'user_id' => $validated['user_id'],
                'department' => $validated['department'],
                'is_active' => true
            ]);

            $preAssignment->load(['user']);

            return response()->json([
                'success' => true,
                'message' => 'Pre-assignment created successfully',
                'preAssignment' => $preAssignment
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create pre-assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create pre-assignment'
            ], 500);
        }
    }

    // ✅ UPDATE pre-assignment
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $preAssignment = PreAssignment::find($id);
            
            if (!$preAssignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pre-assignment not found'
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'department' => 'sometimes|string',
                'is_active' => 'sometimes|boolean'
            ]);

            $preAssignment->update($validated);
            $preAssignment->load(['user']);

            return response()->json([
                'success' => true,
                'message' => 'Pre-assignment updated successfully',
                'preAssignment' => $preAssignment
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update pre-assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update pre-assignment'
            ], 500);
        }
    }

    // ✅ DELETE pre-assignment
    public function destroy($id): JsonResponse
    {
        try {
            $preAssignment = PreAssignment::find($id);
            
            if (!$preAssignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pre-assignment not found'
                ], 404);
            }

            $preAssignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pre-assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete pre-assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete pre-assignment'
            ], 500);
        }
    }

    // ✅ TOGGLE active status
    public function toggleActive($id): JsonResponse
    {
        try {
            $preAssignment = PreAssignment::find($id);
            
            if (!$preAssignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pre-assignment not found'
                ], 404);
            }

            $preAssignment->update([
                'is_active' => !$preAssignment->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pre-assignment status updated',
                'preAssignment' => $preAssignment
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle pre-assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update pre-assignment status'
            ], 500);
        }
    }
}