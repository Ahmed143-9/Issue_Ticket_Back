<?php
// app/Http/Controllers/ProblemController.php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\FirstFaceAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProblemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all problems
     */
    public function index()
    {
        try {
            $user = auth()->user();
            
            // Admin and Team Leaders can see all problems
            if ($user->isAdmin() || $user->isTeamLeader()) {
                $problems = Problem::with(['reporter', 'assignee'])
                                ->orderBy('created_at', 'desc')
                                ->get();
            } else {
                // Regular users can only see their reported problems and assigned problems
                $problems = Problem::with(['reporter', 'assignee'])
                                ->where('reported_by', $user->id)
                                ->orWhere('assigned_to', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get();
            }

            return response()->json([
                'success' => true,
                'problems' => $problems
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load problems: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load problems'
            ], 500);
        }
    }

    /**
     * Create new problem
     */
   public function store(Request $request)
{
    try {
        \Log::info('Problem creation request received', $request->all()); // Debug log

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'department' => 'required|in:IT & Innovation,Business,Accounts',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Get authenticated user
        $user = auth()->user();
        if (!$user) {
            \Log::error('No authenticated user found');
            return response()->json([
                'success' => false,
                'error' => 'User not authenticated'
            ], 401);
        }

        \Log::info('Creating problem for user: ' . $user->id); // Debug

        // Create problem
        $problem = Problem::create([
            'title' => $request->title,
            'description' => $request->description,
            'department' => $request->department,
            'priority' => $request->priority,
            'status' => 'pending',
            'reported_by' => $user->id,
        ]);

        \Log::info('Problem created successfully. ID: ' . $problem->id); // Debug

        // ✅ AUTO-ASSIGN TO FIRST FACE BASED ON DEPARTMENT
        $this->assignToFirstFace($problem);

        return response()->json([
            'success' => true,
            'message' => 'Problem created successfully!',
            'problem' => $problem->load('reporter')
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Problem creation error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Failed to create problem: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Get specific problem
     */
    public function show($id)
    {
        try {
            $problem = Problem::with(['reporter', 'assignee'])->find($id);

            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            // Check if user has permission to view this problem
            $user = auth()->user();
            if (!$user->isAdmin() && !$user->isTeamLeader() && 
                $problem->reported_by !== $user->id && $problem->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'problem' => $problem
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load problem: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load problem'
            ], 500);
        }
    }

    /**
     * Update problem
     */
    public function update(Request $request, $id)
    {
        try {
            $problem = Problem::find($id);

            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            $user = auth()->user();
            
            // Only admin, team leader, reporter or assignee can update
            if (!$user->isAdmin() && !$user->isTeamLeader() && 
                $problem->reported_by !== $user->id && $problem->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'department' => 'sometimes|required|in:IT & Innovation,Business,Accounts',
                'priority' => 'sometimes|required|in:low,medium,high,critical',
                'status' => 'sometimes|required|in:pending,assigned,in_progress,resolved,closed',
                'assigned_to' => 'sometimes|nullable|exists:users,id',
                'resolution_notes' => 'sometimes|nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $updateData = $request->only([
                'title', 'description', 'department', 'priority', 
                'status', 'assigned_to', 'resolution_notes'
            ]);

            // If status is being updated to resolved, set resolved_at
            if ($request->has('status') && $request->status === 'resolved' && $problem->status !== 'resolved') {
                $updateData['resolved_at'] = now();
            }

            // If assigned_to is being set, update assigned_at
            if ($request->has('assigned_to') && $request->assigned_to && !$problem->assigned_to) {
                $updateData['assigned_at'] = now();
            }

            $problem->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Problem updated successfully!',
                'problem' => $problem->load(['reporter', 'assignee'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Problem update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update problem'
            ], 500);
        }
    }

    /**
     * Delete problem
     */
    public function destroy($id)
    {
        try {
            $problem = Problem::find($id);

            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            // Only admin can delete problems
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only admin can delete problems'
                ], 403);
            }

            $problem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Problem deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Problem deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete problem'
            ], 500);
        }
    }

    /**
     * Get problems by status
     */
    public function getByStatus($status)
    {
        try {
            $user = auth()->user();
            $query = Problem::with(['reporter', 'assignee'])->where('status', $status);

            if (!$user->isAdmin() && !$user->isTeamLeader()) {
                $query->where(function($q) use ($user) {
                    $q->where('reported_by', $user->id)
                      ->orWhere('assigned_to', $user->id);
                });
            }

            $problems = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'problems' => $problems
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load problems by status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load problems'
            ], 500);
        }
    }

    /**
     * Get problems by department
     */
    public function getByDepartment($department)
    {
        try {
            $user = auth()->user();
            $query = Problem::with(['reporter', 'assignee'])->where('department', $department);

            if (!$user->isAdmin() && !$user->isTeamLeader()) {
                $query->where(function($q) use ($user) {
                    $q->where('reported_by', $user->id)
                      ->orWhere('assigned_to', $user->id);
                });
            }

            $problems = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'problems' => $problems
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load problems by department: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load problems'
            ], 500);
        }
    }

    /**
     * Get unassigned problems count by department
     */
    public function getUnassignedProblemsStats()
    {
        try {
            $user = auth()->user();
            $query = Problem::whereNull('assigned_to')->where('status', 'pending');

            if (!$user->isAdmin() && !$user->isTeamLeader()) {
                $query->where('reported_by', $user->id);
            }

            $unassigned = $query->get();

            $byDepartment = [
                'all' => $unassigned->count(),
                'IT & Innovation' => $unassigned->where('department', 'IT & Innovation')->count(),
                'Business' => $unassigned->where('department', 'Business')->count(),
                'Accounts' => $unassigned->where('department', 'Accounts')->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $byDepartment
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load problem statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load problem statistics'
            ], 500);
        }
    }

    /**
     * Assign problem to user
     */
    public function assignProblem(Request $request, $id)
    {
        try {
            $problem = Problem::find($id);

            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'assigned_to' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            // Only admin, team leader or reporter can assign
            $user = auth()->user();
            if (!$user->isAdmin() && !$user->isTeamLeader() && $problem->reported_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }

            $problem->update([
                'assigned_to' => $request->assigned_to,
                'assigned_at' => now(),
                'status' => 'assigned'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Problem assigned successfully!',
                'problem' => $problem->load(['reporter', 'assignee'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Problem assignment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to assign problem'
            ], 500);
        }
    }

    /**
     * Update problem status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $problem = Problem::find($id);

            if (!$problem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Problem not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,assigned,in_progress,resolved,closed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            // Check permissions
            $user = auth()->user();
            $canUpdate = $user->isAdmin() || 
                        $user->isTeamLeader() || 
                        $problem->reported_by === $user->id || 
                        $problem->assigned_to === $user->id;

            if (!$canUpdate) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }

            $updateData = ['status' => $request->status];

            // If status is resolved, set resolved_at
            if ($request->status === 'resolved' && $problem->status !== 'resolved') {
                $updateData['resolved_at'] = now();
            }

            $problem->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Problem status updated successfully!',
                'problem' => $problem->load(['reporter', 'assignee'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Problem status update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update problem status'
            ], 500);
        }
    }

    /**
     * Auto-assign problem to First Face based on department
     */
    private function assignToFirstFace(Problem $problem)
    {
        try {
            // 1. First check for specific department First Face
            $firstFace = FirstFaceAssignment::with('user')
                ->where('is_active', true)
                ->where(function($query) use ($problem) {
                    $query->where('department', $problem->department)
                          ->orWhere('department', 'all');
                })
                ->orderByRaw("CASE WHEN department = 'all' THEN 1 ELSE 0 END") // Specific first, then all
                ->first();

            if ($firstFace) {
                $problem->update([
                    'assigned_to' => $firstFace->user_id,
                    'first_face_assigned_to' => $firstFace->user->name,
                    'assigned_at' => now(),
                    'status' => 'assigned'
                ]);

                \Log::info("Problem {$problem->id} auto-assigned to First Face: {$firstFace->user->name} for department: {$problem->department}");
            } else {
                \Log::info("No First Face found for department: {$problem->department}. Problem remains unassigned.");
            }

        } catch (\Exception $e) {
            \Log::error('First Face assignment error: ' . $e->getMessage());
        }
    }
}