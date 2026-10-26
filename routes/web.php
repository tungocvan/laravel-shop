<?php

use App\Http\Controllers\Demo\SalesEmployeesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

// Standalone demo: a single GET route, with browser-only persistence.
Route::get('/demo/doanh-so-nhan-vien', SalesEmployeesController::class)
    ->name('demo.sales-employees');

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});
