<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login_user');

// Registration Routes
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register_user');


Route::get('/verify-email', [VerificationController::class, 'showVerificationForm'])->name('verification.verify');


Route::get('/dashboard', function () {
    return response()->file(resource_path('views/dashboard/dashboard.html'));
});

Route::get('/profile', function () {
    return response()->file(resource_path('views/dashboard/profile.html'));
});