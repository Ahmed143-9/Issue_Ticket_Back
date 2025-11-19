<?php
// app/Http\Controllers\ProblemController.php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\User;
use App\Models\PreAssignment;
use App\Models\FirstFaceAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ProblemController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    // ✅ CREATE new problem with AUTO ASSIGNMENT
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Problem store method called', $request->all());
            
            // Get authenticated user
            $user = auth()->user();
            Log::info('Authenticated user:', ['user' => $user]);

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

            Log::info('Validation passed', $validated);

            $problem = null;
            
            DB::transaction(function () use ($validated, &$problem) {
                // Create problem
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

                Log::info('Problem created successfully', ['id' => $problem->id]);

                // ✅ AUTO ASSIGNMENT LOGIC
                $this->autoAssignProblem($problem);
            });

            return response()->json([
                'success' => true,
                'message' => 'Problem created successfully',
                'problem' => $problem->load('assignee') // Load assigned user details
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Problem creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to create problem: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ AUTO ASSIGNMENT METHOD
    private function autoAssignProblem(Problem $problem)
{
    try {
        Log::info('=== AUTO ASSIGNMENT DEBUG START ===');
        Log::info('Problem Department: ' . $problem->department);

        // 1. Check ALL active First Face assignments
        $allActiveAssignments = FirstFaceAssignment::where('is_active', true)->get();
        Log::info('All Active Assignments:', $allActiveAssignments->toArray());

        // 2. First check for First Face Assignment (Global)
        $firstFaceAssignment = FirstFaceAssignment::where('is_active', true)
            ->with('user')
            ->first();

        Log::info('Selected First Face Assignment:', [
            'found' => !is_null($firstFaceAssignment),
            'user_id' => $firstFaceAssignment->user_id ?? null,
            'user_name' => $firstFaceAssignment->user->name ?? null,
            'department' => $firstFaceAssignment->department ?? null
        ]);

        if ($firstFaceAssignment && $firstFaceAssignment->user) {
            $problem->update([
                'assigned_to' => $firstFaceAssignment->user_id,
                'assignment_type' => 'first_face_auto',
                'status' => 'assigned'
            ]);
            
            Log::info('=== AUTO ASSIGNMENT COMPLETE ===');
            return;
        }

        Log::info('=== NO FIRST FACE FOUND ===');
        
    } catch (\Exception $e) {
        Log::error('Auto assignment failed: ' . $e->getMessage());
    }
}

    // ✅ GET all problems
    public function index(): JsonResponse
    {
        try {
            $problems = Problem::with(['creator', 'assignee'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problems: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET single problem
    public function show(string $id): JsonResponse
    {
        try {
            $problem = Problem::with(['creator', 'assignee'])->find($id);
            
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
            Log::error('Failed to fetch problem: ' . $e->getMessage());
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
                'status' => 'sometimes|in:pending,assigned,in_progress,resolved,pending_approval',
                'statement' => 'sometimes|string',
                'assigned_to' => 'sometimes|exists:users,id|nullable',
                'assignment_type' => 'sometimes|string|nullable',
                'service' => 'sometimes|string|max:255',
                'client' => 'sometimes|string|max:255|nullable',
            ]);

            $problem->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Problem updated successfully',
                'problem' => $problem->load(['creator', 'assignee'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update problem: ' . $e->getMessage());
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
            Log::error('Failed to delete problem: ' . $e->getMessage());
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
            $problems = Problem::with(['creator', 'assignee'])
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problems by status: ' . $e->getMessage());
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
            $problems = Problem::with(['creator', 'assignee'])
                ->where('department', $department)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problems by department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems by department: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET problems by assignment type
    public function getByAssignmentType($type): JsonResponse
    {
        try {
            $problems = Problem::with(['creator', 'assignee'])
                ->where('assignment_type', $type)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problems by assignment type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems by assignment type: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET problems assigned to specific user
    public function getAssignedToUser($userId): JsonResponse
    {
        try {
            $problems = Problem::with(['creator', 'assignee'])
                ->where('assigned_to', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problems assigned to user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problems assigned to user: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET unassigned problems
    public function getUnassigned(): JsonResponse
    {
        try {
            $problems = Problem::with(['creator', 'assignee'])
                ->whereNull('assigned_to')
                ->orWhere('assigned_to', '')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'problems' => $problems,
                'count' => $problems->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch unassigned problems: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch unassigned problems: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ MANUAL ASSIGN problem to user
    public function manualAssign(Request $request, $id): JsonResponse
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
                'assigned_to' => 'required|exists:users,id',
                'assignment_type' => 'required|in:manual,reassigned'
            ]);

            $problem->update([
                'assigned_to' => $validated['assigned_to'],
                'assignment_type' => $validated['assignment_type'],
                'status' => 'assigned'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Problem assigned successfully',
                'problem' => $problem->load(['creator', 'assignee'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to manually assign problem: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to assign problem: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE problem status
    public function updateStatus(Request $request, $id): JsonResponse
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
                'status' => 'required|in:pending,assigned,in_progress,resolved,pending_approval,rejected'
            ]);

            $updateData = ['status' => $validated['status']];

            // If status is resolved, set resolved_at timestamp
            if ($validated['status'] === 'resolved') {
                $updateData['resolved_at'] = now();
            }

            $problem->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Problem status updated successfully',
                'problem' => $problem->load(['creator', 'assignee'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update problem status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update problem status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET problem statistics
    public function getStatistics(): JsonResponse
    {
        try {
            $totalProblems = Problem::count();
            $assignedProblems = Problem::whereNotNull('assigned_to')->count();
            $unassignedProblems = Problem::whereNull('assigned_to')->count();
            $resolvedProblems = Problem::where('status', 'resolved')->count();
            $pendingProblems = Problem::where('status', 'pending')->count();

            $problemsByDepartment = Problem::select('department', DB::raw('count(*) as count'))
                ->groupBy('department')
                ->get();

            $problemsByPriority = Problem::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get();

            $problemsByStatus = Problem::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total' => $totalProblems,
                    'assigned' => $assignedProblems,
                    'unassigned' => $unassignedProblems,
                    'resolved' => $resolvedProblems,
                    'pending' => $pendingProblems,
                    'by_department' => $problemsByDepartment,
                    'by_priority' => $problemsByPriority,
                    'by_status' => $problemsByStatus,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problem statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch problem statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}