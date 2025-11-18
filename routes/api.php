<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\FirstFaceAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (No authentication required)
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);

    // User Management Routes (Admin only)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']); // Admin & Team Leader
        Route::post('/', [UserController::class, 'store']); // Admin only
        Route::put('/{id}', [UserController::class, 'update']); // Admin only
        Route::delete('/{id}', [UserController::class, 'destroy']); // Admin only
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']); // Admin only
        Route::get('/active', [UserController::class, 'getActiveUsers']); // All authenticated users
    });

    // Problem Routes (All authenticated users with permissions)
    Route::prefix('problems')->group(function () {
        Route::get('/', [ProblemController::class, 'index']); // All authenticated users
        Route::post('/', [ProblemController::class, 'store']); // All authenticated users
        Route::get('/{id}', [ProblemController::class, 'show']); // All authenticated users
        Route::put('/{id}', [ProblemController::class, 'update']); // Admin, Team Leader, or assigned user
        Route::delete('/{id}', [ProblemController::class, 'destroy']); // Admin only
        Route::get('/status/{status}', [ProblemController::class, 'getByStatus']); // All authenticated users
        Route::get('/department/{department}', [ProblemController::class, 'getByDepartment']); // All authenticated users
        Route::get('/stats/unassigned', [ProblemController::class, 'getUnassignedProblemsStats']); // Admin & Team Leader
        Route::patch('/{id}/assign', [ProblemController::class, 'assignProblem']); // Admin & Team Leader
        Route::patch('/{id}/status', [ProblemController::class, 'updateStatus']); // Admin, Team Leader, or assigned user
    });

    // First Face Assignment Routes (Admin only)
    Route::prefix('first-face-assignments')->group(function () {
        Route::get('/', [FirstFaceAssignmentController::class, 'index']); // Admin & Team Leader
        Route::post('/', [FirstFaceAssignmentController::class, 'store']); // Admin only
        Route::put('/{id}', [FirstFaceAssignmentController::class, 'update']); // Admin only
        Route::delete('/{id}', [FirstFaceAssignmentController::class, 'destroy']); // Admin only
    });

    // Active Users for dropdowns (All authenticated users)
    Route::get('/active-users', [UserController::class, 'getActiveUsers']);
});