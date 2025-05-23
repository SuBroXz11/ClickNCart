<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;


// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login_user');

// Registration Routes
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register_user');


Route::get('/verify-email', [VerificationController::class, 'showVerificationForm'])->name('verification.verify');


Route::get('/dashboard', function () {
    return response()->file(resource_path('views/dashboard/dashboard.html'));
});
Route::get('/shops', function () {
    return response()->file(resource_path('views/user/shops.html'));
});

Route::get('/manage-shop', function () {
    return response()->file(resource_path('views/dashboard/manage-shop.html'));
});

Route::get('/approve-traders', function () {
    return response()->file(resource_path('views/auth/approve-traders.html'));
});

Route::get('/manage-users', function () {
    return response()->file(resource_path('views/dashboard/manage-users.html'));
});

Route::get('/manage-products', function () {
    return response()->file(resource_path('views/dashboard/manage-products.html'));
});

Route::get('/profile', function () {
    return response()->file(resource_path('views/dashboard/profile.html'));
});

Route::get('/trader-orders', function () {
    return response()->file(resource_path('views/orders/trader-orders.html'));
});

Route::get('/', function () {
    return response()->file(resource_path('views/homepage/home.html'));
});

Route::get('/contact', function () {
    return response()->file(resource_path('views/homepage/contact.html'));
});

Route::get('/wishlist', function () {
    return response()->file(resource_path('views/profile/wishlist.html'));
});


Route::get('/products', function () {
    return response()->file(resource_path('views/products/product.html'));
});

Route::get('/user-orders', function () {
    return response()->file(resource_path('views/orders/user-orders.html'));
});

Route::get('/checkout', function () {
    return response()->file(resource_path('views/products/checkout.html'));
});

Route::get('/payment/success', function () {
    return response()->file(resource_path('views/payment/payment-success.html'));
});
Route::get('/orders', function () {
    return response()->file(resource_path('views/products/orders.html'));
});
Route::get('/payment/cancel', function () {
    return response()->file(resource_path('views/payment/cancel.html'));
});
Route::get('/product/{id}', function ($id) {
    $html = file_get_contents(resource_path('views/products/individualProduct.html'));
    $html = str_replace('<!--PRODUCT_ID-->', $id, $html);
    return response($html)->header('Content-Type', 'text/html');
});

Route::get('/users', function () {
    return response()->file(resource_path('views/dashboard/manage-users.html'));
});

Route::get('/traders', fn() => response()->file(resource_path('views/dashboard/manage-traders.html')));

Route::get('/user-profile', fn() => response()->file(resource_path('views/profile/profile.html')));

Route::get('/wishlist', fn() => response()->file(resource_path('views/profile/wishlist.html')));



