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

Route::get('/profile', function () {
    return response()->file(resource_path('views/dashboard/profile.html'));
});

Route::get('/', function () {
    return response()->file(resource_path('views/homepage/home.html'));
});

Route::get('/contact', function () {
    return response()->file(resource_path('views/homepage/contact.html'));
});

Route::get('/products', function () {
    return response()->file(resource_path('views/products/product.html'));
});

Route::get('/checkout', function () {
    return response()->file(resource_path('views/products/checkout.html'));
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
