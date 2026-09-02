<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Modules\Auth\Services\LoginPresentationService;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SettingsService;
use Throwable;

class LoginTheme extends Component
{
    use AuthorizesSystemActions;

    public string $target = 'admin';

    public array $settings = [];

    public bool $canUpdate = false;

    public function mount(SettingsService $service): void
    {
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.settings.update');
        $requestedTarget = request()->query('target');
        $this->target = in_array($requestedTarget, ['admin', 'client'], true) ? $requestedTarget : 'admin';
        $this->loadSettings($service);
    }

    public function setTarget(string $target, SettingsService $service): void
    {
        $this->target = in_array($target, ['admin', 'client'], true) ? $target : 'admin';
        $this->loadSettings($service);
    }

    protected function rules(): array
    {
        return [
            'settings.theme' => ['required', 'in:classic-card,split-brand,hero-overlay,minimal'],
            'settings.title_line_1' => ['nullable', 'string', 'max:160'],
            'settings.title_line_2' => ['required', 'string', 'max:200'],
            'settings.description' => ['nullable', 'string', 'max:300'],
            'settings.primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings.overlay_opacity' => ['required', 'integer', 'min:0', 'max:90'],
            'settings.show_google' => ['boolean'],
            'settings.footer' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function save(SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $validated = $this->validate();

        try {
            $prefix = $this->prefix();
            $values = collect($validated['settings'])
                ->mapWithKeys(fn ($value, $key): array => [$prefix.$key => is_string($value) ? trim($value) : $value])
                ->all();

            $service->updateMany($values, 'auth_login');
            $this->loadSettings($service);
            $this->dispatch('notify', type: 'success', message: 'Đã lưu giao diện đăng nhập');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu giao diện đăng nhập. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.login-theme', [
            'themes' => [
                'classic-card' => 'Classic Card',
                'split-brand' => 'Split Brand',
                'hero-overlay' => 'Hero Overlay',
                'minimal' => 'Minimal',
            ],
        ]);
    }

    private function loadSettings(SettingsService $service): void
    {
        $config = app(LoginPresentationService::class)->forGuard($this->target === 'admin' ? 'admin' : 'web');
        $this->settings = [
            'theme' => $config['theme'],
            'title_line_1' => $config['title_line_1'],
            'title_line_2' => $config['title_line_2'],
            'description' => $config['description'],
            'primary_color' => $config['primary_color'],
            'overlay_opacity' => $config['overlay_opacity'],
            'show_google' => $config['show_google'],
            'footer' => $config['footer'],
            'logo_url' => $config['logo_url'],
            'background_url' => $config['background_url'],
        ];
    }

    private function prefix(): string
    {
        return $this->target === 'admin' ? 'auth_login_admin_' : 'auth_login_client_';
    }
}
