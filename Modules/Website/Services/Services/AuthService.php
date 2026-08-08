<?php

namespace Modules\Admin\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class AuthService
{
    /**
     * Xử lý logic sau khi nhận dữ liệu từ Google Socialite
     * Tuân thủ: Check permission cấp nghiệp vụ (Section 4)
     */
    public function handleGoogleUser($googleUser)
    {
        // 1. Tìm user theo google_id hoặc email
        $user = User::where('google_id', $googleUser->id)
                    ->orWhere('email', $googleUser->email)
                    ->first();

        if ($user) {
            // Cập nhật thông tin mới nhất từ Google
            $user->update([
                'google_id' => $googleUser->id,
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'avatar' => $user->avatar ?? $googleUser->avatar,
            ]);
        } else {
            // 2. Tạo user mới nếu chưa tồn tại
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'avatar' => $googleUser->avatar,
                'password' => Hash::make(Str::random(24)), // Password ngẫu nhiên bảo mật
                'is_active' => true,
            ]);
        }

        // 3. Kiểm tra trạng thái hoạt động (Section 4: Check permission nghiệp vụ)
        if (!$user->is_active) {
            throw new Exception('Tài khoản của bạn đã bị khóa.');
        }

        // 4. Thực hiện đăng nhập vào guard admin
        Auth::guard('admin')->login($user);

        // 5. Audit Log (Section 12)
        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
