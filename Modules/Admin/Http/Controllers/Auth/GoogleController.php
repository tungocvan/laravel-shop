<?php

namespace Modules\Admin\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Modules\Admin\Services\AuthService;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleController extends Controller
{
    protected $authService;

    // Dependency Injection AuthService theo đúng kiến trúc Service Layer (Section 4)
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Điều hướng người dùng sang Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Nhận callback từ Google và ủy quyền xử lý cho Service
     */
    public function handleGoogleCallback()
    {
        try {
            // Lấy thông tin user từ Socialite
            $googleUser = Socialite::driver('google')->user();

            // Gọi Service xử lý nghiệp vụ (Section 4: Controller không xử lý logic)
            $this->authService->handleGoogleUser($googleUser);

            // Chuyển hướng về Dashboard sau khi login thành công
            return redirect()->route('admin.dashboard');

        } catch (Exception $e) {
            // Log lỗi hệ thống (Section 12)
            Log::error('Google Login Error: ' . $e->getMessage());

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Đã xảy ra lỗi khi đăng nhập bằng Google: ' . $e->getMessage()
            ]);
        }
    }
}
