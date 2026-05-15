<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\FlashSaleController;
use App\Http\Controllers\Api\V1\HomeSectionController;
use App\Http\Controllers\Api\V1\InitController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Version 1
|--------------------------------------------------------------------------
|
| All routes prefixed with /api/v1/
| Locale middleware resolves language from ?lang=, Accept-Language, or user pref.
|
*/

Route::prefix('v1')->middleware([SetApiLocale::class])->group(function () {

    // ═══ PUBLIC (Guest) ═══

    // Storefront initialization
    Route::get('init', InitController::class);

    // Homepage sections
    Route::get('homepage-sections', [HomeSectionController::class, 'index']);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    // Brands
    Route::get('brands', [BrandController::class, 'index']);

    // Banners
    Route::get('banners', [BannerController::class, 'index']);

    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/new-arrivals', [ProductController::class, 'newArrivals']);
    Route::get('products/best-sellers', [ProductController::class, 'bestSellers']);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // Product Reviews (public — read)
    Route::get('products/{slug}/reviews', [ReviewController::class, 'index']);

    // Flash Sales
    Route::get('flash-sales/active', [FlashSaleController::class, 'active']);

    // Blog
    Route::get('blog/posts', [BlogController::class, 'posts']);
    Route::get('blog/posts/{slug}', [BlogController::class, 'showPost'])->name('api.blog.show');
    Route::get('blog/categories', [BlogController::class, 'categories']);

    // CMS Pages
    Route::get('pages/{slug}', [PageController::class, 'show']);

    // Cart (works for guests via session and authenticated via user_id)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items/{id}', [CartController::class, 'updateItem']);
        Route::delete('items/{id}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('coupon', [CartController::class, 'applyCoupon']);
        Route::delete('coupon', [CartController::class, 'removeCoupon']);
    });

    // Checkout (partially public — guest checkout allowed if enabled)
    Route::prefix('checkout')->group(function () {
        Route::post('shipping-rates', [CheckoutController::class, 'shippingRates']);
        Route::post('place-order', [CheckoutController::class, 'placeOrder']);
        Route::get('order-success/{orderNumber}', [CheckoutController::class, 'orderSuccess']);
    });

    // ═══ AUTHENTICATED (Customer) ═══

    Route::middleware('auth:sanctum')->group(function () {

        // Auth (authenticated)
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'updatePassword']);

        // Addresses
        Route::get('addresses', [ProfileController::class, 'addresses']);
        Route::post('addresses', [ProfileController::class, 'addAddress']);
        Route::put('addresses/{id}', [ProfileController::class, 'updateAddress']);
        Route::delete('addresses/{id}', [ProfileController::class, 'deleteAddress']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{orderNumber}', [OrderController::class, 'show']);

        // Wishlist
        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist', [WishlistController::class, 'store']);
        Route::delete('wishlist/{productId}', [WishlistController::class, 'destroy']);

        // Reviews (authenticated — write)
        Route::post('products/{slug}/reviews', [ReviewController::class, 'store']);
    });
});
