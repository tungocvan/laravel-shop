<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Modules\Admin\Services\AuthService;

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
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Đăng nhập Google chưa được cấu hình đầy đủ. Vui lòng kiểm tra GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET và APP_URL.',
            ]);
        }

        $canonicalGoogleUrl = rtrim((string) config('app.url'), '/').'/auth/google';
        $canonicalHost = parse_url($canonicalGoogleUrl, PHP_URL_HOST);

        if ($canonicalHost && $request->getHost() !== $canonicalHost) {
            return redirect()->away($canonicalGoogleUrl);
        }

        $request->session()->regenerate();

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

        } catch (InvalidStateException $e) {
            Log::warning('Google OAuth state mismatch.', [
                'exception' => $e::class,
                'session_id' => request()->session()->getId(),
                'host' => request()->getHost(),
                'secure' => request()->isSecure(),
            ]);

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Phiên đăng nhập Google đã hết hạn hoặc cookie bị thay đổi. Vui lòng thử đăng nhập lại.',
            ]);
        } catch (Exception $e) {
            // Log lỗi hệ thống (Section 12)
            Log::error('Google Login Error.', [
                'exception' => $e::class,
                'code' => $e->getCode(),
            ]);

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại hoặc liên hệ quản trị viên.',
            ]);
        }
    }

    private function hasGoogleConfiguration(): bool
    {
        $google = config('services.google', []);

        return collect(['client_id', 'client_secret', 'redirect'])
            ->every(fn (string $key): bool => filled($google[$key] ?? null));
    }
}
