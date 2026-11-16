<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /api (see bootstrap/app.php).

Route::get('/health', HealthController::class);

// The homepage's blocks and their content (see HomeController).
Route::get('/home', HomeController::class);

// Public catalog. {product:slug} looks the product up by its slug column.
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/filters', [ProductController::class, 'filters']); // before {product:slug}, or "filters" is read as a slug
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index']);
Route::get('/products/{product:slug}/similar', [ProductController::class, 'similar']);

// Prices a guest's localStorage cart.
Route::post('/cart/preview', [CartController::class, 'preview']);

// Discount code preview. Throttled so codes can't be guessed by brute force.
Route::post('/coupons/check', [CouponController::class, 'check'])->middleware('throttle:20,1');

// Accounts. "throttle:login" = at most 5 attempts a minute (see AppServiceProvider).
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:login');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:login');

// Everything below needs "Authorization: Bearer <token>"; without it the API answers 401.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AccountController::class, 'update']);
    Route::put('/user/password', [AccountController::class, 'updatePassword']);
    Route::delete('/user', [AccountController::class, 'destroy']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/merge', [CartController::class, 'merge']);
    Route::put('/cart/items/{offer}', [CartController::class, 'update']); // {offer}: a seller's offer id
    Route::delete('/cart/items/{offer}', [CartController::class, 'destroy']);

    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::put('/wishlist/{product}', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);

    Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store']);

    // Back office: admins only (403 for everyone else).
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/offers', [AdminController::class, 'offers']);
        Route::put('/offers/{offer}', [AdminController::class, 'updateOffer']);
        Route::post('/offers/{offer}/keys', [AdminController::class, 'addKeys']);
        Route::get('/home-blocks', [AdminController::class, 'homeBlocks']);
        Route::put('/home-blocks/{homeBlock}', [AdminController::class, 'updateHomeBlock']);
        Route::get('/coupons', [AdminController::class, 'coupons']);
        Route::post('/coupons', [AdminController::class, 'storeCoupon']);
        Route::delete('/coupons/{coupon}', [AdminController::class, 'destroyCoupon']);
        Route::get('/orders', [AdminController::class, 'orders']);
    });
});
