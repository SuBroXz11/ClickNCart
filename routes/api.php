<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\Api\CartController;

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


///Product endpoint ///
Route::get('/products/category/{category}', [ProductController::class, 'getByCategory']);
Route::get('/products/homepage', [ProductController::class, 'homepageProducts']);
Route::get('/products/filters', [ProductController::class, 'getAvailableFilters']);
Route::get('/products/normal-search', [ProductController::class, 'normalSearch']);

// Protected endpoints
Route::middleware([JwtMiddleware::class . ':retailer,admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/retailer/products', [ProductController::class, 'getRetailerProducts']);
    Route::get('/retailer/products/{retailerId}', [ProductController::class, 'getRetailerProducts']);
});

Route::get('/product/id/{productId}', [ProductController::class, 'getProductById']);

Route::middleware([JwtMiddleware::class . ':admin'])->group(function () {
    Route::get('/admin/products', [ProductController::class, 'getAllProducts']);
});

Route::middleware([JwtMiddleware::class . ':user,retailer,admin'])->group(function () {
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
});

Route::middleware([JwtMiddleware::class . ':user'])->group(function () {
    Route::post('/products/{id}/rate', [ProductController::class, 'updateRating']);
});



Route::middleware([JwtMiddleware::class . ':user,retailer'])->group(function () {
     Route::post('/cart/add', [CartController::class, 'addToCart']);
     Route::post('/cart/count', [CartController::class, 'cartCount']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::delete('/cart/remove', [CartController::class, 'deleteCart']);
});