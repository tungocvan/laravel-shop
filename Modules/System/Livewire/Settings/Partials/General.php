<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SettingsService;
use Throwable;

class General extends Component
{
    use AuthorizesSystemActions;

    public array $settings = [];
    public bool $canUpdate = false;

    public function mount(SettingsService $service): void
    {
        $this->settings = $service->getGeneral();
        $this->canUpdate = (bool) (auth('admin')->user() ?: auth()->user())?->can('system.settings.update');
    }

    protected function rules(): array
    {
        return [
            'settings.site_name' => ['required', 'string', 'max:255'],
            'settings.site_email' => ['nullable', 'email', 'max:255'],
            'settings.site_hotline' => ['nullable', 'string', 'max:50'],
            'settings.site_address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function save(SettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $validated = $this->validate();

        try {
            $service->saveGeneral($validated['settings']);
            $this->settings = $service->getGeneral();
            $this->dispatch('site-name-updated');
            $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình chung');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu cấu hình chung. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.general');
    }
}
