<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('auth:api');
Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('auth:api');

Route::middleware('jwt.auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // Admin routes
    Route::middleware(['jwt.auth:admin'])->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/retailers/{id}/approve', [AdminController::class, 'approveRetailer']);
        Route::post('/users/{id}/block', [AdminController::class, 'blockUser']);
        Route::post('/users/{id}/unblock', [AdminController::class, 'unblockUser']);
    });
});