<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\FirstFaceAssignmentController;
use App\Http\Controllers\PreAssignmentController;
use App\Http\Controllers\DashboardController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// ✅ ADD LOGIN ROUTE WITH NAME - এইটা add করুন
// Route::get('/login', function () {
//     return response()->json([
//         'success' => false,
//         'error' => 'Please use POST /api/login for authentication'
//     ], 401);
// })->name('login');

// ✅ TEMPORARY DEBUG ROUTES - Authentication ছাড়া
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API test endpoint is working!',
        'timestamp' => now()
    ]);
});

Route::get('/test-users', function () {
    try {
        $users = \App\Models\User::all();
        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'users' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/test-first-face', function () {
    try {
        $assignments = \App\Models\FirstFaceAssignment::with('user')->get();
        return response()->json([
            'success' => true,
            'count' => $assignments->count(),
            'assignments' => $assignments
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-pre-assignments', function () {
    try {
        $preAssignments = \App\Models\PreAssignment::with('user')->get();
        return response()->json([
            'success' => true,
            'count' => $preAssignments->count(),
            'pre_assignments' => $preAssignments
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-problems', function () {
    try {
        $problems = \App\Models\Problem::with(['creator', 'assignee'])->get();
        return response()->json([
            'success' => true,
            'count' => $problems->count(),
            'problems' => $problems
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// ✅ TEMPORARILY REMOVE AUTH MIDDLEWARE FOR ALL ROUTES FOR TESTING

// Department Routes
Route::get('/departments', [DashboardController::class, 'getDepartments']);

// User Management Routes
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/active', [UserController::class, 'getActiveUsers']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
});

// Problem Routes - COMPLETE API RESOURCE
Route::prefix('problems')->group(function () {
    Route::get('/', [ProblemController::class, 'index']);
    Route::post('/', [ProblemController::class, 'store']);
    Route::get('/{id}', [ProblemController::class, 'show']);
    Route::put('/{id}', [ProblemController::class, 'update']);
    Route::delete('/{id}', [ProblemController::class, 'destroy']);
    Route::get('/status/{status}', [ProblemController::class, 'getByStatus']);
    Route::get('/department/{department}', [ProblemController::class, 'getByDepartment']);
    Route::get('/assignment-type/{type}', [ProblemController::class, 'getByAssignmentType']);
    Route::get('/assigned-to/{userId}', [ProblemController::class, 'getAssignedToUser']);
    Route::get('/unassigned/all', [ProblemController::class, 'getUnassigned']);
    Route::post('/{id}/assign', [ProblemController::class, 'manualAssign']);
    Route::patch('/{id}/status', [ProblemController::class, 'updateStatus']);
    Route::get('/statistics/summary', [ProblemController::class, 'getStatistics']);
});

// First Face Assignment Routes
Route::prefix('first-face-assignments')->group(function () {
    Route::get('/', [FirstFaceAssignmentController::class, 'index']);
    Route::post('/', [FirstFaceAssignmentController::class, 'store']);
    Route::put('/{id}', [FirstFaceAssignmentController::class, 'update']);
    Route::delete('/{id}', [FirstFaceAssignmentController::class, 'destroy']);
    Route::patch('/{id}/toggle', [FirstFaceAssignmentController::class, 'toggleActive']);
});

// Pre Assignment Routes
Route::prefix('pre-assignments')->group(function () {
    Route::get('/', [PreAssignmentController::class, 'index']);
    Route::post('/', [PreAssignmentController::class, 'store']);
    Route::put('/{id}', [PreAssignmentController::class, 'update']);
    Route::delete('/{id}', [PreAssignmentController::class, 'destroy']);
    Route::patch('/{id}/toggle', [PreAssignmentController::class, 'toggleActive']);
});

// Auth routes
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'getUser']);

// Additional test route for problem creation
Route::post('/test-problem-create', function (Illuminate\Http\Request $request) {
    try {
        $problem = \App\Models\Problem::create([
            'department' => 'IT & Innovation',
            'service' => 'Bulk SMS',
            'priority' => 'High',
            'statement' => 'Test problem statement for auto assignment',
            'client' => 'Test Client',
            'created_by' => 'Test User',
            'status' => 'pending'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Test problem created',
            'problem' => $problem
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test auto assignment route
Route::post('/test-auto-assign/{problemId}', function ($problemId) {
    try {
        $problem = \App\Models\Problem::find($problemId);
        
        if (!$problem) {
            return response()->json([
                'success' => false,
                'error' => 'Problem not found'
            ], 404);
        }

        // Call auto assignment logic
        app()->make(\App\Http\Controllers\ProblemController::class)->autoAssignProblem($problem);
        
        $problem->refresh(); // Refresh to get updated data
        
        return response()->json([
            'success' => true,
            'message' => 'Auto assignment completed',
            'problem' => $problem->load('assignee')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}); 