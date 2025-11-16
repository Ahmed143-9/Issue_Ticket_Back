<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProblemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $problems = Problem::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'department' => 'required|string|max:255',
                'priority' => 'required|in:High,Medium,Low',
                'statement' => 'required|string',
                'created_by' => 'required|string|max:255',
            ]);

            $problem = Problem::create([
                'department' => $validated['department'],
                'priority' => $validated['priority'],
                'statement' => $validated['statement'],
                'created_by' => $validated['created_by'],
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Problem created successfully',
                'problem' => $problem
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create problem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $problem = Problem::find($id);
            
            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'problem' => $problem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $problem = Problem::find($id);
            
            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            $validated = $request->validate([
                'department' => 'sometimes|string|max:255',
                'priority' => 'sometimes|in:High,Medium,Low',
                'status' => 'sometimes|in:pending,in_progress,done,pending_approval',
                'statement' => 'sometimes|string',
                'assigned_to' => 'sometimes|string|max:255|nullable',
            ]);

            $problem->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Problem updated successfully',
                'problem' => $problem
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update problem: ' . e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $problem = Problem::find($id);
            
            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            $problem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Problem deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete problem: ' . $e->getMessage()
            ], 500);
        }
    }
}