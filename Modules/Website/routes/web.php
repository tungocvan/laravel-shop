<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\AccountController;
use Modules\Website\Http\Controllers\Admin\AffiliateController;
use Modules\Website\Http\Controllers\Admin\BannerController;
use Modules\Website\Http\Controllers\Admin\CouponController;
use Modules\Website\Http\Controllers\Admin\CustomerController;
use Modules\Website\Http\Controllers\Admin\FlashSaleController;
use Modules\Website\Http\Controllers\Admin\FooterController;
use Modules\Website\Http\Controllers\Admin\HeaderController;
use Modules\Website\Http\Controllers\Admin\HomeSettingsController;
use Modules\Website\Http\Controllers\Admin\WebsiteDashboardController;
use Modules\Website\Http\Controllers\Admin\WebsiteSettingsController;
use Modules\Website\Http\Controllers\AuthController;
use Modules\Website\Http\Controllers\CartController;
use Modules\Website\Http\Controllers\CheckoutController;
use Modules\Website\Http\Controllers\PostController;
use Modules\Website\Http\Controllers\ProductController;
use Modules\Website\Http\Controllers\SitemapController;
use Modules\Website\Http\Controllers\WebsiteController;

$websitePrefix = config('website.route_prefix', 'website');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/website-manifest.webmanifest', [WebsiteController::class, 'manifest'])->name('website.manifest');
Route::get('/website-pwa-version.json', [WebsiteController::class, 'pwaVersion'])->name('website.pwa.version');

Route::middleware('web')->group(function () use ($websitePrefix) {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::get('/register', 'register')->name('register');
    });

    Route::controller(WebsiteController::class)->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/help', 'help')->name('help');
    });

    Route::controller(ProductController::class)->prefix('product')->name('product.')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('/{slug}', 'show')->name('detail');
    });

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/{slug}', [PostController::class, 'detail'])->name('detail');
    });

    Route::controller(CartController::class)->group(function () {
        Route::get('/cart', 'index')->name('cart.index');
    });

    Route::prefix('checkout')->name('checkout.')->controller(CheckoutController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/success', 'success')->name('success');
        Route::get('/momo-callback', 'momoCallback')->name('momo.callback');
        Route::post('/momo-ipn', 'momoIpn')->withoutMiddleware([ValidateCsrfToken::class])->name('momo.ipn');
    });

    Route::middleware('auth')->prefix('account')->name('account.')->controller(AccountController::class)->group(function () {
        Route::get('/', 'index')->name('dashboard');
        Route::get('/profile', 'profile')->name('profile');
        Route::get('/affiliate', 'affiliate')->name('affiliate');
        Route::get('/orders', 'orders')->name('orders');
        Route::get('/orders/{code}', 'orderDetail')->name('orders.detail');
        Route::get('/wishlist', 'wishlist')->name('wishlist');
    });
});

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/website', [WebsiteDashboardController::class, 'index'])->middleware('permission:website.view,admin')->name('website.dashboard');
    Route::get('/website/settings', [WebsiteSettingsController::class, 'index'])->middleware('permission:website.settings.manage,admin')->name('website.settings');
    Route::get('/affiliate', [AffiliateController::class, 'index'])->middleware('permission:affiliate.view,admin')->name('affiliate.index');
    Route::get('/homepage-settings', [HomeSettingsController::class, 'index'])->middleware('permission:website.home.manage,admin')->name('home.settings');
    Route::get('/header-settings', [HeaderController::class, 'index'])->middleware('permission:website.menu.manage,admin')->name('header.settings');
    Route::get('/footer-settings', [FooterController::class, 'index'])->middleware('permission:website.footer.manage,admin')->name('footer.settings');
    Route::get('/banners', [BannerController::class, 'index'])->middleware('permission:website.banner.manage,admin')->name('banners');
    Route::get('/flash-sales', [FlashSaleController::class, 'index'])->middleware('permission:marketing.flash-sale.view,admin')->name('flash-sales');

    Route::prefix('coupons')->name('coupons.')->controller(CouponController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:marketing.coupon.view,admin')->name('index');
        Route::get('/create', 'create')->middleware('permission:marketing.coupon.manage,admin')->name('create');
        Route::get('/{id}/edit', 'edit')->middleware('permission:marketing.coupon.manage,admin')->name('edit');
    });

    Route::prefix('customers')->name('customers.')->controller(CustomerController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:customer.view,admin')->name('index');
        Route::get('/create', 'create')->middleware('permission:customer.create,admin')->name('create');
        Route::get('/{id}', 'show')->middleware('permission:customer.view,admin')->name('show');
    });
});
