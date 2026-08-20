<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AuthController extends Controller
{
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
            return redirect()->route('admin.dashboard');
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

        return Route::has('client.apps.login')
            ? redirect()->route('client.apps.login')
            : redirect()->route('login');
    }

    public function adminLogout(): RedirectResponse
    {
        Auth::guard('admin')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
