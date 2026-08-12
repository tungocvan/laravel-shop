<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SettingsService;
use Throwable;

class Images extends Component
{
    use AuthorizesSystemActions;
    use WithFileUploads;

    public $site_logo;
    public $site_favicon;
    public $new_logo;
    public $new_favicon;
    public bool $canUpdate = false;

    public function mount(SettingsService $service): void
    {
        $images = $service->getImages();
        $this->site_logo = $images['site_logo'];
        $this->site_favicon = $images['site_favicon'];
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.settings.update');
    }

    protected function rules(): array
    {
        return [
            'new_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'new_favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:1024'],
        ];
    }

    public function save(SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $this->validate();

        try {
            if ($this->new_logo) {
                $path = $service->replaceImage('logo', $this->new_logo);
                $this->site_logo = $path;
                $this->new_logo = null;
                $this->dispatch('logo-updated', url: asset('storage/' . $path) . '?v=' . md5($path . microtime(true)));
            }

            if ($this->new_favicon) {
                $path = $service->replaceImage('favicon', $this->new_favicon);
                $this->site_favicon = $path;
                $this->new_favicon = null;
                $this->dispatch('favicon-updated', url: asset('storage/' . $path) . '?v=' . md5($path . microtime(true)), type: strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'ico' ? 'image/x-icon' : 'image/png');
            }

            $this->dispatch('notify', type: 'success', message: 'Đã cập nhật hình ảnh');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật hình ảnh. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function remove(string $type, SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');

        try {
            $service->removeImage($type);
            if ($type === 'logo') {
                $this->site_logo = null;
                $this->dispatch('logo-updated', url: '');
            } elseif ($type === 'favicon') {
                $this->site_favicon = null;
                $this->dispatch('favicon-updated', url: '', type: 'image/png');
            }
            $this->dispatch('notify', type: 'success', message: 'Đã xóa hình ảnh');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể xóa hình ảnh. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.images');
    }
}
