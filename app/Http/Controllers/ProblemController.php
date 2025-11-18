<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProblemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // ✅ এই লাইনটা রাখবেন
    }

    // ✅ GET all problems
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

    // ✅ CREATE new problem (আপনার store method)
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('Problem store method called', $request->all());
            
            // Get authenticated user
            $user = auth()->user();
            \Log::info('Authenticated user:', ['user' => $user]);

            $validated = $request->validate([
                'department' => 'required|string|max:255',
                'service' => 'required|string|max:255',
                'priority' => 'required|in:High,Medium,Low',
                'statement' => 'required|string',
                'client' => 'nullable|string|max:255',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                'created_by' => 'required|string|max:255',
            ]);

            \Log::info('Validation passed', $validated);

            $problem = Problem::create([
                'department' => $validated['department'],
                'service' => $validated['service'],
                'priority' => $validated['priority'],
                'statement' => $validated['statement'],
                'client' => $validated['client'] ?? null,
                'images' => $validated['images'] ?? [],
                'created_by' => $validated['created_by'],
                'status' => 'pending',
            ]);

            \Log::info('Problem created successfully', ['id' => $problem->id]);

            return response()->json([
                'success' => true,
                'message' => 'Problem created successfully',
                'problem' => $problem
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Problem creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create problem: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET single problem
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

    // ✅ UPDATE problem
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
                'error' => 'Failed to update problem: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE problem
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

    // ✅ GET problems by status
    public function getByStatus($status): JsonResponse
    {
        try {
            $problems = Problem::where('status', $status)->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems by status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET problems by department
    public function getByDepartment($department): JsonResponse
    {
        try {
            $problems = Problem::where('department', $department)->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems by department: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ ADD any other methods you need here...
}