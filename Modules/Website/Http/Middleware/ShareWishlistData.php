<?php

namespace Modules\Website\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\WishlistService;

class ShareWishlistData
{
    public function handle(Request $request, Closure $next): Response
    {
        // Mặc định là mảng rỗng
        $wishlistIds = [];

        // Chỉ query nếu user đã đăng nhập
        if (Auth::check()) {
            // Gọi Service lấy list ID (Code tối ưu 1 query mà ta đã viết)
            $service = App::make(WishlistService::class);
            $wishlistIds = $service->getUserWishlistIds(Auth::id());
        }

        // --- KEYWORD QUAN TRỌNG: View::share ---
        // Biến 'globalWishlistIds' sẽ khả dụng ở TOÀN BỘ file view (.blade.php)
        View::share('globalWishlistIds', $wishlistIds);

        return $next($request);
    }
}
