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
        $user = auth('admin')->user();

        $groups = [
            'Vận hành hệ thống' => [
                ['label' => 'System Center', 'description' => 'Runtime Operations, Queue Manager và trạng thái vận hành.', 'route' => 'admin.system.index', 'permission' => 'system.manage', 'icon' => 'activity'],
                ['label' => 'Quản lý Modules', 'description' => 'Trạng thái, dependency và quản trị vòng đời modules.', 'route' => 'admin.system.modules', 'permission' => 'system.modules.view', 'icon' => 'blocks'],
            ],
            'Dữ liệu & Sao lưu' => [
                ['label' => 'Database Manager', 'description' => 'Cơ sở dữ liệu, sao lưu và khôi phục có kiểm soát.', 'route' => 'admin.system.database.index', 'permission' => 'database.view', 'icon' => 'database'],
            ],
            'Quản trị truy cập' => [
                ['label' => 'Admin Layout', 'description' => 'Bố cục, header, sidebar, footer và điều hướng Admin.', 'route' => 'admin.layout', 'permission' => 'admin.layout.view', 'icon' => 'layout'],
                ['label' => 'Giao diện & Đăng nhập', 'description' => 'Branding, đăng nhập và điều hướng sau đăng nhập.', 'route' => 'admin.system.settings.login-theme', 'permission' => 'system.settings.view', 'icon' => 'palette'],
            ],
            'Công cụ kỹ thuật' => [
                ['label' => 'Artisan Console', 'description' => 'Lệnh Artisan được giới hạn bởi hệ thống.', 'route' => 'admin.system.artisan', 'permission' => 'system.commands.run', 'icon' => 'terminal'],
                ['label' => 'Scripts Manager', 'description' => 'Registry script vận hành được kiểm soát.', 'route' => 'admin.system.scripts', 'permission' => 'system.commands.run', 'icon' => 'code'],
            ],
            'Môi trường & Tích hợp' => [
                ['label' => 'Environment & Integrations', 'description' => 'Database connection, Email, NodeJS Bridge, Cloud, Web và thanh toán.', 'route' => 'admin.system.index', 'params' => ['tab' => 'env-database'], 'permission' => 'system.env.view', 'icon' => 'settings'],
            ],
        ];

        $groups = collect($groups)
            ->map(fn (array $items) => collect($items)
                ->filter(fn (array $item): bool => (bool) $user?->can($item['permission']))
                ->values()
                ->all())
            ->filter(fn (array $items): bool => $items !== [])
            ->all();

        return view('System::pages.settings.index', compact('groups'));
    }

    public function loginTheme(
        AdminLoginRedirectService $redirect,
        ApplicationRootRedirectService $rootRedirect,
    ) {
        return view('System::pages.settings.login-theme', [
            'routeName' => $redirect->configuredRoute(),
            'routeOptions' => $redirect->availableRoutes(),
            'rootRouteName' => $rootRedirect->configuredRoute(),
            'rootRouteOptions' => $rootRedirect->availableRoutes(),
        ]);
    }

    public function loginRedirect(): RedirectResponse
    {
        return redirect()
            ->route('admin.system.settings.login-theme')
            ->with('info', 'Đăng nhập & Điều hướng đã được hợp nhất vào workspace Giao diện & Đăng nhập.');
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
            return back()->withInput()->withErrors($errors);
        }

        $settings->set(AdminLoginRedirectService::SETTING_KEY, $routeName, 'system', 'text');
        $settings->set(ApplicationRootRedirectService::SETTING_KEY, $rootRouteName, 'system', 'text');

        return redirect()
            ->route('admin.system.settings.login-theme')
            ->with('success', 'Đã lưu cấu hình đăng nhập và điều hướng.');
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
