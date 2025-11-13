<?php
// app/Http/Controllers/FirstFaceAssignmentController.php

namespace App\Http\Controllers;

use App\Models\FirstFaceAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FirstFaceAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Get all first face assignments
    public function index()
    {
        try {
            $authUser = auth()->user();
            
            if (!$authUser->isAdmin() && !$authUser->isTeamLeader()) {
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
                                                    'userName' => $assignment->user->name,
                                                    'department' => $assignment->department,
                                                    'type' => $assignment->type,
                                                    'assigned_by' => $assignment->assigner->name,
                                                    'assignedAt' => $assignment->assigned_at->toISOString(),
                                                    'is_active' => $assignment->is_active
                                                ];
                                            });

            return response()->json([
                'success' => true,
                'firstFaceAssignments' => $assignments
            ]);
        } catch (\Exception $e) {
            \Log::error('First Face Assignment Load Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load first face assignments'
            ], 500);
        }
    }

    // Create new first face assignment
   public function store(Request $request)
    {
        try {
            $authUser = auth()->user();
            
            if (!$authUser->isAdmin()) {
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
                    'error' => $validator->errors()->first()
                ], 422);
            }

            // ✅ IMPORTANT: Check if already assigned for this department
            if ($request->department !== 'all') {
                $existingAssignment = FirstFaceAssignment::where('department', $request->department)
                                                        ->where('is_active', true)
                                                        ->first();

                if ($existingAssignment) {
                    // Deactivate the existing assignment
                    $existingAssignment->update(['is_active' => false]);
                }
            } else {
                // If assigning for "all" departments, deactivate other "all" assignments
                $existingAllAssignment = FirstFaceAssignment::where('department', 'all')
                                                           ->where('is_active', true)
                                                           ->first();
                if ($existingAllAssignment) {
                    $existingAllAssignment->update(['is_active' => false]);
                }
            }

            $assignment = FirstFaceAssignment::create([
                'user_id' => $request->user_id,
                'department' => $request->department,
                'type' => $request->type,
                'assigned_by' => $authUser->id,
                'assigned_at' => now(),
                'is_active' => true
            ]);

            $assignment->load(['user', 'assigner']);

            return response()->json([
                'success' => true,
                'message' => 'First Face assigned successfully!',
                'assignment' => [
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
        }
    