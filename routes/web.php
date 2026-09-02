<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        return redirect()->route('admin.dashboard');
    }

    abort(404);
});
