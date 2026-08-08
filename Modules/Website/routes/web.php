<?php

use Illuminate\Support\Facades\Route;

// =====================
// Controllers Frontend
// =====================
use Modules\Website\Http\Controllers\AuthController;
use Modules\Website\Http\Controllers\AccountController;
use Modules\Website\Http\Controllers\WebsiteController;
use Modules\Website\Http\Controllers\PostController;
use Modules\Website\Http\Controllers\ProductController;
use Modules\Website\Http\Controllers\CartController;
use Modules\Website\Http\Controllers\CheckoutController;

// =====================
// Controllers Admin
// =====================
use Modules\Website\Http\Controllers\Admin\AffiliateController;
use Modules\Website\Http\Controllers\Admin\HomeSettingsController;
use Modules\Website\Http\Controllers\Admin\HeaderController;
use Modules\Website\Http\Controllers\Admin\FooterController;
use Modules\Website\Http\Controllers\Admin\BannerController;
use Modules\Website\Http\Controllers\Admin\FlashSaleController;
use Modules\Website\Http\Controllers\Admin\CouponController;
use Modules\Website\Http\Controllers\Admin\CustomerController;

// Config
$websitePrefix = config('website.route_prefix', 'website');

Route::middleware('web')->group(function () use ($websitePrefix) {

    /*
    |--------------------------------------------------------------------------
    | 1. AUTH (Guest)
    |--------------------------------------------------------------------------
    */
    Route::controller(AuthController::class)->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::get('/register', 'register')->name('register');
    });

    /*
    |--------------------------------------------------------------------------
    | 2. AUTH (User)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')->prefix($websitePrefix)->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    /*
    |--------------------------------------------------------------------------
    | 3. PUBLIC
    |--------------------------------------------------------------------------
    */
    Route::controller(WebsiteController::class)->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/help', 'help')->name('help');
    });

    // Product
    Route::controller(ProductController::class)->prefix('product')->name('product.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('/{slug}', 'show')->name('detail');
    });

    // Blog
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/{slug}', [PostController::class, 'detail'])->name('detail');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. SHOPPING
    |--------------------------------------------------------------------------
    */
    Route::controller(CartController::class)->group(function () {
        Route::get('/cart', 'index')->name('cart.index');
    });

    Route::prefix('checkout')->name('checkout.')->controller(CheckoutController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/success', 'success')->name('success');
        Route::get('/momo-callback', 'momoCallback')->name('momo.callback');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. ACCOUNT (Auth)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth')->prefix('account')->name('account.')->controller(AccountController::class)->group(function () {

        Route::get('/', 'index')->name('dashboard');

        Route::get('/profile', 'profile')->name('profile');
        Route::get('/affiliate', 'affiliate')->name('affiliate');

        Route::get('/orders', 'orders')->name('orders');
        Route::get('/orders/{code}', 'orderDetail')->name('orders.detail');

        Route::get('/wishlist', 'wishlist')->name('wishlist');
    });

});

/*
|--------------------------------------------------------------------------
| 6. ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/affiliate', [AffiliateController::class, 'index'])->name('affiliate.index');

        Route::get('/homepage-settings', [HomeSettingsController::class, 'index'])->name('home.settings');
        Route::get('/header-settings', [HeaderController::class, 'index'])->name('header.settings');
        Route::get('/footer-settings', [FooterController::class, 'index'])->name('footer.settings');

        Route::get('/banners', [BannerController::class, 'index'])->name('banners');
        Route::get('/flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales');

        Route::prefix('coupons')->name('coupons.')->controller(CouponController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{id}/edit', 'edit')->name('edit');
        });

        Route::prefix('customers')->name('customers.')->controller(CustomerController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/{id}', 'show')->name('show');
        });
    });
