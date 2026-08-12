<?php

namespace Modules\System\Livewire\Settings\Partials;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SeoSettingsService;
use Throwable;

class Seo extends Component
{
    use AuthorizesSystemActions;

    public array $settings = [
        'seo_title' => '',
        'seo_description' => '',
        'social_facebook' => '',
        'social_zalo' => '',
        'header_script' => '',
    ];

    public bool $canUpdate = false;

    public function mount(SeoSettingsService $service): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.settings.update');
        $this->settings = $service->all();
    }

    protected function rules(): array
    {
        return [
            'settings.seo_title' => ['nullable', 'string', 'max:255'],
            'settings.seo_description' => ['nullable', 'string', 'max:500'],
            'settings.social_facebook' => ['nullable', 'url', 'max:2048'],
            'settings.social_zalo' => ['nullable', 'string', 'max:255'],
            'settings.header_script' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function save(SeoSettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $validated = $this->validate();

        try {
            $service->save($validated['settings'], auth('admin')->id());
            $this->settings = $service->all();
            $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình SEO');
        } catch (Throwable $e) {
            Log::warning('SEO settings save failed in Livewire.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu cấu hình SEO. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.seo');
    }
}
