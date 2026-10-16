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

    public string $previewDevice = 'desktop';

    public array $settings = [];

    public bool $canUpdate = false;

    public int $selectedPreset = 1;

    public array $presets = [];

    private const PRESET_DEFAULTS = [
        1 => ['name' => 'FlexBiz Light', 'background_type' => 'color', 'background_color' => '#f1f5f9', 'form_background_color' => '#ffffff', 'primary_color' => '#0f766e', 'overlay_opacity' => 45, 'logo_size' => 'large'],
        2 => ['name' => 'Corporate Blue', 'background_type' => 'color', 'background_color' => '#0f2747', 'form_background_color' => '#ffffff', 'primary_color' => '#2563eb', 'overlay_opacity' => 45, 'logo_size' => 'large'],
        3 => ['name' => 'Emerald Modern', 'background_type' => 'color', 'background_color' => '#e6f4f1', 'form_background_color' => '#ffffff', 'primary_color' => '#047857', 'overlay_opacity' => 45, 'logo_size' => 'large'],
    ];

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

    public function setPreviewDevice(string $device): void
    {
        $this->previewDevice = in_array($device, ['desktop', 'mobile'], true) ? $device : 'desktop';
    }

    public function applyPreset(int $slot): void
    {
        if (! isset($this->presets[$slot])) {
            return;
        }

        $this->selectedPreset = $slot;
        foreach (array_keys(self::PRESET_DEFAULTS[1]) as $key) {
            if ($key !== 'name' && isset($this->presets[$slot][$key])) {
                $this->settings[$key] = $this->presets[$slot][$key];
            }
        }
    }

    public function savePreset(int $slot, SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        if (! isset(self::PRESET_DEFAULTS[$slot])) {
            return;
        }

        $validated = $this->validate();
        $preset = ['name' => self::PRESET_DEFAULTS[$slot]['name']];
        foreach (array_keys(self::PRESET_DEFAULTS[1]) as $key) {
            if ($key !== 'name') {
                $preset[$key] = $validated['settings'][$key];
            }
        }
        $service->set($this->prefix().'preset_'.$slot, $preset, 'auth_login');
        $this->presets[$slot] = $preset;
        $this->selectedPreset = $slot;
        $this->dispatch('notify', type: 'success', message: 'Đã lưu bộ thiết lập '.$slot.'.');
    }

    protected function rules(): array
    {
        return [
            'settings.logo_size' => ['required', 'in:small,medium,large'],
            'settings.background_type' => ['required', 'in:image,color'],
            'settings.background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings.form_background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
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
        $this->presets = [];
        foreach (self::PRESET_DEFAULTS as $slot => $default) {
            $stored = $service->get($this->prefix().'preset_'.$slot);
            $this->presets[$slot] = is_array($stored) && array_keys($default) === array_keys($stored) ? $stored : $default;
        }
        $this->selectedPreset = 1;
        $this->settings = [
            'theme' => $config['theme'],
            'logo_size' => $config['logo_size'],
            'background_type' => $config['background_type'],
            'background_color' => $config['background_color'],
            'form_background_color' => $config['form_background_color'],
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
