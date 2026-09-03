<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\SettingsService;

class SettingController extends Controller
{
    public function index()
    {
        return view('System::pages.settings.index');
    }

    public function loginTheme()
    {
        return view('System::pages.settings.login-theme');
    }

    public function loginRedirect(AdminLoginRedirectService $redirect)
    {
        return view('System::pages.settings.login-redirect', [
            'routeName' => $redirect->configuredRoute(),
            'routeOptions' => $redirect->availableRoutes(),
        ]);
    }

    public function updateLoginRedirect(
        Request $request,
        SettingsService $settings,
        AdminLoginRedirectService $redirect,
    ): RedirectResponse {
        $validated = $request->validate([
            'route_name' => ['required', 'string'],
        ]);

        $routeName = trim($validated['route_name']);

        if (! $redirect->isAllowedRoute($routeName)) {
            return back()
                ->withInput()
                ->withErrors(['route_name' => 'Route điều hướng không hợp lệ hoặc không còn khả dụng.']);
        }

        $settings->set(AdminLoginRedirectService::SETTING_KEY, $routeName, 'system', 'text');

        return redirect()
            ->route('admin.system.settings.login-redirect')
            ->with('success', 'Đã lưu trang mặc định của Admin.');
    }

    public function profile()
    {
        return view('System::pages.settings.profile');
    }

    public function modules()
    {
        return view('System::pages.settings.modules');
    }

    public function artisan()
    {
        return view('System::pages.settings.artisan');
    }

    public function scripts()
    {
        return view('System::pages.settings.scripts');
    }
}
