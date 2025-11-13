<?php
// routes/api.php - সম্পূর্ণ code check করুন

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\FirstFaceAssignmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);
    
    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/active', [UserController::class, 'getActiveUsers']);
    });

    // ✅ Problem routes - POST method যোগ করুন
    Route::prefix('problems')->group(function () {
        Route::get('/', [ProblemController::class, 'index']);
        Route::post('/', [ProblemController::class, 'store']); // ✅ এই line টি非常重要
        Route::get('/{id}', [ProblemController::class, 'show']);
        Route::put('/{id}', [ProblemController::class, 'update']);
        Route::delete('/{id}', [ProblemController::class, 'destroy']);
        Route::get('/status/{status}', [ProblemController::class, 'getByStatus']);
        Route::get('/department/{department}', [ProblemController::class, 'getByDepartment']);
        Route::get('/stats/unassigned', [ProblemController::class, 'getUnassignedProblemsStats']);
        Route::patch('/{id}/assign', [ProblemController::class, 'assignProblem']);
        Route::patch('/{id}/status', [ProblemController::class, 'updateStatus']);
    });

    // First Face Assignment routes
    Route::prefix('first-face')->group(function () {
        Route::get('/', [FirstFaceAssignmentController::class, 'index']);
        Route::post('/', [FirstFaceAssignmentController::class, 'store']);
        Route::delete('/{id}', [FirstFaceAssignmentController::class, 'destroy']);
    });
});