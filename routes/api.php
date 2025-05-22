<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\OrderManagement;
use App\Http\Controllers\CollectionSlotController;

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
        Route::get('/unapproved-retailers', [AdminController::class, 'getUnapprovedRetailers']);
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
    Route::get('/shop/{shopId}', [ProductController::class, 'getByShop']);
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

Route::middleware([JwtMiddleware::class . ':retailer'])->group(function () {
     Route::post('/cart/add', [CartController::class, 'addToCart']);
     Route::post('/cart/count', [CartController::class, 'cartCount']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::delete('/cart/remove', [CartController::class, 'deleteCart']);
    Route::put('/cart/update-quantity', [CartController::class, 'updateCartQuantity']);
});

// Shop routes
Route::middleware([JwtMiddleware::class .':retailer'])->group(function () {
    Route::post('/shops', [ShopController::class, 'store']);
    Route::get('/shops/user/{userId?}', [ShopController::class, 'getUserShops']);
    Route::get('/shops/all', [ShopController::class, 'getAllShops'])->middleware('admin');
    Route::get('/shops/{id}', [ShopController::class, 'show']);
    Route::put('/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);
});

Route::prefix('payment')->group(function () {
    Route::post('/create', [PaymentController::class, 'createPayment']);
    Route::get('/success', [PaymentController::class, 'paymentSuccess']);
});

// Add these routes inside your existing routes file, within the appropriate middleware groups

Route::prefix('collection-slots')->group(function () {
    Route::post('/check-availability', [CollectionSlotController::class, 'checkAvailability']);
    
    // Admin routes
    Route::middleware([JwtMiddleware::class . ':admin'])->group(function () {
        Route::get('/', [CollectionSlotController::class, 'getAllSlots']);
        Route::put('/{slotId}/status', [CollectionSlotController::class, 'updateSlotStatus']);
    });
    
    // User routes
    Route::middleware([JwtMiddleware::class . ':user'])->group(function () {
        Route::get('/order/{orderId}', [CollectionSlotController::class, 'getSlotByOrder']);
    });
});

// Order routes
Route::prefix('orders')->group(function () {
    // User routes
    Route::middleware([JwtMiddleware::class . ':user'])->group(function () {
        Route::get('/', [OrderManagement::class, 'getUserOrders']);
        Route::get('/{orderId}', [OrderManagement::class, 'getOrderDetails']);
        Route::post('/{orderItemId}/cancel', [OrderManagement::class, 'requestCancellation']);
    });

    // Retailer routes
    Route::middleware([JwtMiddleware::class . ':retailer'])->group(function () {
        Route::get('/shop/info', [OrderManagement::class, 'getShopOrders']);
        Route::get('/shop/{shopId}', [OrderManagement::class, 'getOrdersByShop']); // New endpoint
        Route::put('/{orderItemId}/process-cancellation', [OrderManagement::class, 'processCancellation']);
        Route::put('/{orderItemId}/status', [OrderManagement::class, 'updateOrderStatus']);
    });

    // Admin routes
    Route::middleware([JwtMiddleware::class . ':admin'])->group(function () {
        Route::get('/admin/all', [OrderManagement::class, 'getAllOrders']); // New endpoint
    });
});