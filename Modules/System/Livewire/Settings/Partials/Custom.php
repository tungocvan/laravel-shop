<?php

namespace Modules\System\Livewire\Settings\Partials;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\CustomSettingsService;
use Throwable;

class Custom extends Component
{
    use AuthorizesSystemActions;
    use WithFileUploads;

    public array $customSettings = [];
    public array $dynamicValues = [];
    public array $dynamicImages = [];
    public array $galleryUploads = [];
    public bool $canUpdate = false;

    public array $newField = [
        'label' => '',
        'key' => '',
        'type' => 'text',
    ];

    public function mount(CustomSettingsService $service): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.settings.update');
        $this->loadSettings($service);
    }

    public function loadSettings(?CustomSettingsService $service = null): void
    {
        $service ??= app(CustomSettingsService::class);
        $this->customSettings = $service->all();
        $this->dynamicValues = [];

        foreach ($this->customSettings as $setting) {
            if ($setting['type'] === 'gallery') {
                $decoded = json_decode((string) ($setting['value'] ?? ''), true);
                $this->dynamicValues[$setting['id']] = is_array($decoded) ? array_values($decoded) : [];
            } elseif ($setting['type'] !== 'image') {
                $this->dynamicValues[$setting['id']] = $setting['value'];
            }
        }
    }

    public function addField(CustomSettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');
        $validated = $this->validate([
            'newField.label' => ['required', 'string', 'max:255'],
            'newField.key' => ['required', 'alpha_dash', 'max:255'],
            'newField.type' => ['required', 'in:text,textarea,image,html,gallery'],
        ]);

        try {
            $service->create($validated['newField'], auth('admin')->id());
            $this->newField = ['label' => '', 'key' => '', 'type' => 'text'];
            $this->loadSettings($service);
            $this->dispatch('notify', type: 'success', message: 'Đã thêm field');
        } catch (Throwable $e) {
            Log::warning('Custom setting create failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể thêm cấu hình. Vui lòng kiểm tra dữ liệu hoặc log hệ thống.');
        }
    }

    public function deleteField(int $id, CustomSettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');

        try {
            $service->delete($id, auth('admin')->id());
            $this->loadSettings($service);
            $this->dispatch('notify', type: 'success', message: 'Đã xóa field');
        } catch (Throwable $e) {
            Log::warning('Custom setting delete failed.', ['setting_id' => $id, 'exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể xóa cấu hình. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function removeGalleryImage(int $id, int $index): void
    {
        $this->authorizePermission('system.settings.update');
        $images = $this->dynamicValues[$id] ?? [];

        if (! is_array($images) || ! array_key_exists($index, $images)) {
            return;
        }

        unset($images[$index]);
        $this->dynamicValues[$id] = array_values($images);
    }

    public function save(CustomSettingsService $service): void
    {
        $this->authorizePermission('system.settings.update');

        $this->validate([
            'dynamicImages.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'galleryUploads.*' => ['nullable', 'array', 'max:20'],
            'galleryUploads.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $service->save(
                $this->dynamicValues,
                $this->dynamicImages,
                $this->galleryUploads,
                auth('admin')->id(),
            );

            $this->dynamicImages = [];
            $this->galleryUploads = [];
            $this->loadSettings($service);
            $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình');
        } catch (Throwable $e) {
            Log::warning('Custom settings save failed in Livewire.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể lưu cấu hình. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.partials.custom');
    }
}
