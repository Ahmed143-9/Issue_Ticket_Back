<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Problem;
use App\Models\User;
use App\Models\FirstFaceAssignment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDepartments()
    {
        $departments = [
            'Enterprise Business Solutions',
            'Board Management',
            'Support Stuff',
            'Administration and Human Resources',
            'Finance and Accounts',
            'Business Dev and Operations',
            'Implementation and Support',
            'Technical and Networking Department'
        ];
        
        return response()->json([
            'success' => true,
            'departments' => $departments
        ]);
    }

    public function getStats()
    {
        try {
            $totalUsers = User::where('email', '!=', 'admin@example.com')->count();
            $activeUsers = User::where('status', 'active')
                             ->where('email', '!=', 'admin@example.com')
                             ->count();
            $totalProblems = Problem::count();
            $assignedProblems = Problem::whereNotNull('assigned_to')->count();
            $unassignedProblems = Problem::whereNull('assigned_to')->count();
            $resolvedProblems = Problem::where('status', 'resolved')->count();
            $pendingProblems = Problem::where('status', 'pending')->count();
            
            $problemsByDepartment = Problem::select('department', DB::raw('count(*) as count'))
                ->groupBy('department')
                ->get();
                
            $activeAssignments = FirstFaceAssignment::where('is_active', true)->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'users' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers
                    ],
                    'problems' => [
                        'total' => $totalProblems,
                        'assigned' => $assignedProblems,
                        'unassigned' => $unassignedProblems,
                        'resolved' => $resolvedProblems,
                        'pending' => $pendingProblems
                    ],
                    'assignments' => [
                        'active' => $activeAssignments
                    ],
                    'problems_by_department' => $problemsByDepartment
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}