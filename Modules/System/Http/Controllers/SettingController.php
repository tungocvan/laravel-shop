<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\ApplicationRootRedirectService;
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

    public function loginRedirect(
        AdminLoginRedirectService $redirect,
        ApplicationRootRedirectService $rootRedirect,
    ) {
        return view('System::pages.settings.login-redirect', [
            'routeName' => $redirect->configuredRoute(),
            'routeOptions' => $redirect->availableRoutes(),
            'rootRouteName' => $rootRedirect->configuredRoute(),
            'rootRouteOptions' => $rootRedirect->availableRoutes(),
        ]);
    }

    public function updateLoginRedirect(
        Request $request,
        SettingsService $settings,
        AdminLoginRedirectService $redirect,
        ApplicationRootRedirectService $rootRedirect,
    ): RedirectResponse {
        $validated = $request->validate([
            'route_name' => ['required', 'string'],
            'root_route_name' => ['required', 'string'],
        ]);

        $routeName = trim($validated['route_name']);
        $rootRouteName = trim($validated['root_route_name']);

        $errors = [];

        if (! $redirect->isAllowedRoute($routeName)) {
            $errors['route_name'] = 'Route điều hướng Admin không hợp lệ hoặc không còn khả dụng.';
        }

        if (! $rootRedirect->isAllowedRoute($rootRouteName)) {
            $errors['root_route_name'] = 'Route thay thế cho / không hợp lệ, không còn khả dụng hoặc có thể gây vòng lặp.';
        }

        if ($errors !== []) {
            return back()
                ->withInput()
                ->withErrors($errors);
        }

        $settings->set(AdminLoginRedirectService::SETTING_KEY, $routeName, 'system', 'text');
        $settings->set(ApplicationRootRedirectService::SETTING_KEY, $rootRouteName, 'system', 'text');

        return redirect()
            ->route('admin.system.settings.login-redirect')
            ->with('success', 'Đã lưu cấu hình điều hướng Admin và trang thay thế cho /.');
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
