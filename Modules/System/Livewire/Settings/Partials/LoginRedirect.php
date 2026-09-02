<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\SettingsService;
use Throwable;

class LoginRedirect extends Component
{
    use AuthorizesSystemActions;

    public string $routeName = AdminLoginRedirectService::DEFAULT_ROUTE;
    public array $routeOptions = [];
    public bool $canUpdate = false;

    public function mount(AdminLoginRedirectService $redirect): void
    {
        $this->routeName = $redirect->configuredRoute();
        $this->routeOptions = $redirect->availableRoutes();
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.settings.update');
    }

    public function save(SettingsService $settings, AdminLoginRedirectService $redirect): void
    {
        $this->authorizePermission('system.settings.update');

        if (! $redirect->isAllowedRoute($this->routeName)) {
            $this->addError('routeName', 'Route điều hướng không hợp lệ hoặc không còn khả dụng.');

            return;
        }

        try {
            $settings->set(AdminLoginRedirectService::SETTING_KEY, $this->routeName, 'system', 'text');
            $this->routeName = $redirect->configuredRoute();
            $this->dispatch('notify', type: 'success', message: 'Đã lưu trang mặc định sau đăng nhập.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu trang mặc định sau đăng nhập. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.login-redirect');
    }
}
