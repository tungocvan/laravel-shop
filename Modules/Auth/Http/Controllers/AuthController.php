<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Modules\System\Services\AdminLoginRedirectService;

class AuthController extends Controller
{
    public function __construct(private readonly AdminLoginRedirectService $adminLoginRedirect)
    {
    }

    public function clientLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return Route::has('client.apps.index')
                ? redirect()->route('client.apps.index')
                : redirect('/');
        }

        return view('Auth::pages.auth.login', [
            'guard' => 'web',
        ]);
    }

    public function adminLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route($this->adminLoginRedirect->configuredRoute());
        }

        return view('Auth::pages.auth.login', [
            'guard' => 'admin',
        ]);
    }

    public function clientLogout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        $response = Route::has('client.apps.login')
            ? redirect()->route('client.apps.login')
            : redirect()->route('login');

        return $this->withSiteDataCleared($response);
    }

    public function adminLogout(): RedirectResponse
    {
        Auth::guard('admin')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->withSiteDataCleared(redirect()->route('admin.login'));
    }

    private function withSiteDataCleared(RedirectResponse $response): RedirectResponse
    {
        $response->headers->set('Clear-Site-Data', '"cache", "storage"');

        return $response;
    }
}
