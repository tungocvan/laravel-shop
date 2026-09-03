<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});
