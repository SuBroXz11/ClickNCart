<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\OrderManagement;
use App\Http\Controllers\CollectionSlotController;
use App\Http\Controllers\Api\ContactController;

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
    Route::post('/products/{id}/update', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/retailer/products', [ProductController::class, 'getRetailerProducts']);
    Route::get('/retailer/products/{retailerId}', [ProductController::class, 'getRetailerProducts']);
});

Route::get('/shop/{shopId}', [ProductController::class, 'getByShop']);

Route::get('/product/id/{productId}', [ProductController::class, 'getProductById']);

Route::middleware([JwtMiddleware::class . ':admin'])->group(function () {
    Route::get('/admin/products', [ProductController::class, 'getAllProducts']);
});

Route::middleware([JwtMiddleware::class . ':user,retailer,admin'])->group(function () {
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
});


 Route::middleware([JwtMiddleware::class . ':user'])->group(function () {
        Route::post('/{productId}/reviews', [ProductController::class, 'addReview']);
        Route::delete('/{productId}/reviews', [ProductController::class, 'deleteReview']);
    });
    
    Route::get('/{productId}/reviews', [ProductController::class, 'getReviews']);
    
    // Discount routes
    Route::middleware([JwtMiddleware::class . ':retailer,admin'])->group(function () {
        Route::post('/products/{productId}/discount', [ProductController::class, 'setDiscount']);
        Route::delete('/products/{productId}/discount', [ProductController::class, 'removeDiscount']);
    });



Route::middleware([JwtMiddleware::class . ':user'])->group(function () {
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
    
    Route::post('/shops/{id}/update', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);
    Route::get('/shops/{id}', [ShopController::class, 'show']);
});

Route::get('/shops/all', [ShopController::class, 'getAllShops']);

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
    Route::get('/', [OrderManagement::class, 'getUserOrders']);
    Route::get('/shop', [OrderManagement::class, 'getShopOrders']);
    Route::get('/{orderId}', [OrderManagement::class, 'getOrderDetails']);
    Route::post('/{orderItemId}/cancel', [OrderManagement::class, 'requestCancellation']);
    Route::put('/{orderItemId}/process-cancellation', [OrderManagement::class, 'processCancellation']);
    Route::put('/{orderItemId}/status', [OrderManagement::class, 'updateOrderStatus']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('user', [ProfileController::class, 'show']);
    Route::put('user', [ProfileController::class, 'update']);
});

Route::prefix('wishlist')->middleware('auth:api')->group(function(){
    Route::get('/',      [WishlistController::class,'index']);
    Route::post('add',   [WishlistController::class,'add']);
    Route::delete('remove/{id}', [WishlistController::class,'remove']);
});

Route::middleware('jwt.auth')->group(function () {
    Route::post('contact', [ContactController::class, 'store']);
});

Route::middleware([JwtMiddleware::class . ':admin'])->group(function () {
    Route::get('admin/contacts', [ContactController::class, 'index']);
});