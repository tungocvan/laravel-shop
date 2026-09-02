<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Modules\Auth\Services\GoogleWebAuthService;

class GoogleController extends Controller
{
    public function __construct(
        private readonly GoogleWebAuthService $googleAuthService,
    ) {}

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

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->googleAuthService->resolve($googleUser);

            Auth::guard('admin')->login($user);
            request()->session()->regenerate();

            $user->forceFill(['last_login_at' => now()])->save();

            return redirect()->route('admin.dashboard');
        } catch (ValidationException $e) {
            return redirect()->route('admin.login')->withErrors($e->errors());
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
