<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\FirstFaceAssignmentController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// ✅ TEMPORARY DEBUG ROUTES - Authentication ছাড়া
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
        $assignments = \App\Models\FirstFaceAssignment::all();
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

// ✅ TEMPORARILY REMOVE AUTH MIDDLEWARE FOR ALL ROUTES
// User Management Routes
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::get('/active', [UserController::class, 'getActiveUsers']);
});

// Problem Routes
Route::apiResource('problems', ProblemController::class);

// First Face Assignment Routes - URL FIXED
Route::prefix('first-face')->group(function () {
    Route::get('/', [FirstFaceAssignmentController::class, 'index']);
    Route::post('/', [FirstFaceAssignmentController::class, 'store']);
    Route::put('/{id}', [FirstFaceAssignmentController::class, 'update']);
    Route::delete('/{id}', [FirstFaceAssignmentController::class, 'destroy']);
});

// Active Users route (duplicate remove)
// Route::get('/active-users', [UserController::class, 'getActiveUsers']); // Remove this line

// Auth routes (without middleware temporarily)
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'getUser']);

// Protected routes (COMMENT OUT TEMPORARILY FOR TESTING)
/*
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);

    // User Management Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/active', [UserController::class, 'getActiveUsers']);
    });

    // Problem Routes
    Route::apiResource('problems', ProblemController::class);

    // First Face Assignment Routes
    Route::prefix('first-face')->group(function () {
        Route::get('/', [FirstFaceAssignmentController::class, 'index']);
        Route::post('/', [FirstFaceAssignmentController::class, 'store']);
        Route::put('/{id}', [FirstFaceAssignmentController::class, 'update']);
        Route::delete('/{id}', [FirstFaceAssignmentController::class, 'destroy']);
    });
});
*/