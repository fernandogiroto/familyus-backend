<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\GameController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

    // House
    Route::get('/house', [HouseController::class, 'show']);
    Route::post('/house', [HouseController::class, 'create']);
    Route::put('/house/ready', [HouseController::class, 'setReady']);

    // Invitations
    Route::post('/invitations', [InvitationController::class, 'send']);
    Route::get('/invitations/pending', [InvitationController::class, 'pending']);
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{token}/reject', [InvitationController::class, 'reject']);

    // Tasks (setup phase)
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Game
    Route::post('/game/start', [GameController::class, 'startGame']);
    Route::get('/game/today', [GameController::class, 'todayTasks']);
    Route::post('/game/tasks/{task}/complete', [GameController::class, 'completeTask']);
    Route::get('/game/score', [GameController::class, 'score']);
    Route::get('/game/history', [GameController::class, 'history']);
});
