<?php

namespace Modules\Website\Livewire\Admin\Header;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Throwable;

class GeneralSettings extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public $hotline;
    public $email;
    public $help_url;
    public $order_tracking_url;
    public $brand_name;
    public $header_script;
    public $brand_logo_upload;
    public $current_brand_logo;
    public $fallback_site_logo;
    public $fallback_site_name;

    protected $rules = [
        'hotline' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:100',
        'brand_name' => 'required|string|max:100',
        'help_url' => 'nullable|string|max:255',
        'order_tracking_url' => 'nullable|string|max:255',
        'brand_logo_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
    ];

    public function mount(SettingsService $settingsService): void
    {
        $this->hotline = $settingsService->get('header.topbar.hotline', '0903971949');
        $this->email = $settingsService->get('header.topbar.email', 'tungocvan@gmail.com');
        $this->help_url = $settingsService->get('header.topbar.help_url', '/');
        $this->order_tracking_url = $settingsService->get('header.topbar.order_tracking_url', 'account/orders');
        $this->fallback_site_name = $settingsService->get('site_name', 'FlexBiz');
        $this->brand_name = $settingsService->get('header.brand_name', $this->fallback_site_name);
        $this->header_script = $settingsService->get('header_script', '');
        $this->current_brand_logo = $settingsService->get('header.brand_logo');
        $this->fallback_site_logo = $settingsService->get('site_logo');
    }

    public function save(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $this->validate();

        $oldLogo = $this->current_brand_logo;
        $newLogo = $this->brand_logo_upload
            ? $this->brand_logo_upload->store('header/brand', 'public')
            : null;

        try {
            $values = [
                'header.topbar.hotline' => $this->hotline,
                'header.topbar.email' => $this->email,
                'header.topbar.help_url' => $this->help_url,
                'header.topbar.order_tracking_url' => $this->order_tracking_url,
                'header.brand_name' => $this->brand_name,
            ];

            if ($newLogo !== null) {
                $values['header.brand_logo'] = $newLogo;
            }

            $settingsService->updateMany($values, 'header');
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }
            throw $exception;
        }

        if ($newLogo !== null) {
            $this->deleteOwnedBrandLogo($oldLogo);
            $this->current_brand_logo = $newLogo;
            $this->brand_logo_upload = null;
        }

        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu cấu hình Header.',
        ]]);
    }

    public function removeBrandLogo(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $oldLogo = $this->current_brand_logo;
        $settingsService->set('header.brand_logo', null, 'header');
        $this->deleteOwnedBrandLogo($oldLogo);
        $this->current_brand_logo = null;
        $this->brand_logo_upload = null;

        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã xóa logo Header. Header sẽ dùng site_logo mặc định.',
        ]]);
    }

    public function render()
    {
        return view('Website::livewire.admin.header.general-settings');
    }

    public function saveAdvanced(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validate(['header_script' => 'nullable|string|max:65535']);

        $settingsService->set('header_script', $this->header_script, 'advanced', 'textarea');

        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu script nâng cao.',
        ]]);
    }

    private function deleteOwnedBrandLogo(?string $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'header/brand/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
