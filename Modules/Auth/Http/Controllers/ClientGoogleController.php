<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Modules\Auth\Services\GoogleIdentityService;

class ClientGoogleController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return $this->redirectToGoogle($request, false);
    }

    public function linkRedirect(Request $request): RedirectResponse
    {
        return $this->redirectToGoogle($request, true);
    }

    public function callback(Request $request, GoogleIdentityService $identities): RedirectResponse
    {
        try {
            $this->useClientCallback();
            $googleUser = Socialite::driver('google')->user();
            $linking = $request->session()->pull('auth.google_linking', false);

            if ($linking) {
                $current = Auth::guard('web')->user();

                if (! $current) {
                    return redirect()->route('client.apps.login')->withErrors([
                        'email' => 'Phiên liên kết Google đã hết hạn. Hãy đăng nhập lại bằng mật khẩu.',
                    ]);
                }

                $identities->link($current, $googleUser);
                $request->session()->regenerate();

                return redirect()->route('client.apps.index')
                    ->with('status', 'Đã liên kết tài khoản Google thành công.');
            }

            $user = $identities->resolve($googleUser);

            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();

            return Route::has('client.apps.index')
                ? redirect()->route('client.apps.index')
                : redirect('/');
        } catch (ValidationException $e) {
            return redirect()->route('client.apps.login')->withErrors($e->errors());
        } catch (InvalidStateException $e) {
            Log::warning('Client Google OAuth state mismatch.', [
                'exception' => $e::class,
                'session_id' => $request->session()->getId(),
            ]);

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('client.apps.login')->withErrors([
                'email' => 'Phiên đăng nhập Google đã hết hạn. Vui lòng thử lại.',
            ]);
        } catch (Exception $e) {
            Log::error('Client Google login error.', [
                'exception' => $e::class,
                'code' => $e->getCode(),
            ]);

            return redirect()->route('client.apps.login')->withErrors([
                'email' => 'Không thể xử lý tài khoản Google. Vui lòng thử lại.',
            ]);
        }
    }

    private function redirectToGoogle(Request $request, bool $linking): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()->route('client.apps.login')->withErrors([
                'email' => 'Đăng nhập Google chưa được cấu hình đầy đủ.',
            ]);
        }

        $this->useClientCallback();
        $request->session()->put('auth.google_linking', $linking);
        $request->session()->regenerate();

        return Socialite::driver('google')->redirect();
    }

    private function useClientCallback(): void
    {
        config(['services.google.redirect' => route('client.apps.google.callback')]);
    }

    private function hasGoogleConfiguration(): bool
    {
        $google = config('services.google', []);

        return collect(['client_id', 'client_secret'])
            ->every(fn (string $key): bool => filled($google[$key] ?? null));
    }
}
