<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

// All Demo UI, dataset, asset and menu routes are registered by Modules/Demo.

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});



