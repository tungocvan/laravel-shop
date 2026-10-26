<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

/*
 * Standalone customer demo. The original HTML/CSS/JS is deliberately served
 * without Blade, Vite, or a module to preserve the reference UI.
 * Demo records are stored in the visitor's browser, never on the server.
 */
Route::get('/demo/doanh-so-nhan-vien', function () {
    $path = resource_path('demo/sales-employees.html');

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-store, private',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('demo.sales-employees');

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});
