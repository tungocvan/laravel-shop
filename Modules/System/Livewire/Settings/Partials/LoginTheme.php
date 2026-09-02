<?php

namespace Modules\System\Livewire\Settings\Partials;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Auth\Services\LoginPresentationService;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SettingsService;
use Throwable;

class LoginTheme extends Component
{
    use AuthorizesSystemActions;
    use WithFileUploads;

    public string $target = 'admin';
    public array $settings = [];
    public $newLogo;
    public $newBackground;
    public bool $canUpdate = false;

    public function mount(SettingsService $service): void
    {
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.settings.update');
        $this->loadSettings($service);
    }

    public function setTarget(string $target, SettingsService $service): void
    {
        $this->target = in_array($target, ['admin', 'client'], true) ? $target : 'admin';
        $this->reset(['newLogo', 'newBackground']);
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
            'newLogo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'newBackground' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:6144'],
        ];
    }

    public function save(SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $validated = $this->validate();
        $newPaths = [];
        $oldPaths = [];

        try {
            $prefix = $this->prefix();
            $values = collect($validated['settings'])
                ->mapWithKeys(fn ($value, $key): array => [$prefix.$key => is_string($value) ? trim($value) : $value])
                ->all();

            if ($this->newLogo) {
                $oldPaths[] = (string) $service->get($prefix.'logo', '');
                $newPaths[] = $values[$prefix.'logo'] = $this->newLogo->store('login-branding/logos', 'public');
            }

            if ($this->newBackground) {
                $oldPaths[] = (string) $service->get($prefix.'background', '');
                $newPaths[] = $values[$prefix.'background'] = $this->newBackground->store('login-branding/backgrounds', 'public');
            }

            $service->updateMany($values, 'auth_login');

            foreach ($oldPaths as $oldPath) {
                if ($this->isManagedPath($oldPath) && ! in_array($oldPath, $newPaths, true)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $this->reset(['newLogo', 'newBackground']);
            $this->loadSettings($service);
            $this->dispatch('notify', type: 'success', message: 'Đã lưu giao diện đăng nhập');
        } catch (Throwable $e) {
            foreach ($newPaths as $newPath) {
                Storage::disk('public')->delete($newPath);
            }

            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu giao diện đăng nhập. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function removeAsset(string $type, SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');

        if (! in_array($type, ['logo', 'background'], true)) {
            return;
        }

        $key = $this->prefix().$type;
        $path = (string) $service->get($key, '');
        $service->set($key, null, 'auth_login');

        if ($this->isManagedPath($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->loadSettings($service);
        $this->dispatch('notify', type: 'success', message: 'Đã xóa hình ảnh đăng nhập');
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

    private function isManagedPath(string $path): bool
    {
        return $path !== '' && str_starts_with($path, 'login-branding/');
    }
}
