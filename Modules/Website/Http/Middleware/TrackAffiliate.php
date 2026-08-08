<?php

namespace Modules\Website\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliate
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra URL có tham số ?ref=...
        if ($request->has('ref')) {
            $referralId = $request->query('ref');
            $currentUserId = Auth::id();

            // 2. Logic: Không cho phép tự giới thiệu chính mình (nếu đang đăng nhập)
            // Nếu chưa đăng nhập ($currentUserId null) thì vẫn ghi nhận bình thường
            if (!$currentUserId || $currentUserId != $referralId) {
                
                // 3. Tạo Cookie 'affiliate_ref' tồn tại 30 ngày (phút * giờ * ngày)
                // queue() sẽ tự động gắn cookie vào response
                Cookie::queue('affiliate_ref', $referralId, 60 * 24 * 30);
            }
        }

        return $next($request);
    }
}