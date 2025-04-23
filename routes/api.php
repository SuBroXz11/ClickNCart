<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/{id}/block', [UserController::class, 'blockUser']);
    Route::post('/users/{id}/unblock', [UserController::class, 'unblockUser']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::post('/deactivate-account', [UserController::class, 'deactivateAccount']);
});