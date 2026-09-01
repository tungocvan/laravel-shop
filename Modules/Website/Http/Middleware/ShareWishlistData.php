<?php

namespace Modules\Website\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Modules\Product\Services\WishlistService;
use Symfony\Component\HttpFoundation\Response;

class ShareWishlistData
{
    public function handle(Request $request, Closure $next): Response
    {
        $wishlistIds = [];

        if (Auth::check()) {
            $wishlistIds = app(WishlistService::class)->getUserWishlistIds(Auth::id());
        }

        View::share('globalWishlistIds', $wishlistIds);

        return $next($request);
    }
}
