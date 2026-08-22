<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Throwable;

class FooterInfo extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public $brand_name;
    public $brand_description;
    public $address;
    public $email;
    public $phone;
    public $appstore_url;
    public $playstore_url;
    public $copyright;
    public $brand_logo_upload;
    public $current_brand_logo;
    public $fallback_site_logo;
    public $fallback_site_name;

    public function mount(SettingsService $settingsService)
    {
        $this->fallback_site_name = $settingsService->get('site_name', 'FlexBiz');
        $this->brand_name = $settingsService->get('footer.brand_name', $this->fallback_site_name);
        $this->brand_description = $settingsService->get('footer.brand_description');
        $this->address = $settingsService->get('footer.address');
        $this->email = $settingsService->get('footer.email');
        $this->phone = $settingsService->get('footer.phone');
        $this->appstore_url = $settingsService->get('footer.appstore_url');
        $this->playstore_url = $settingsService->get('footer.playstore_url');
        $this->copyright = $settingsService->get('footer.copyright', '© 2024 FlexBiz. All rights reserved.');
        $this->current_brand_logo = $settingsService->get('footer.brand_logo');
        $this->fallback_site_logo = $settingsService->get('site_logo');
    }

    public function save(SettingsService $settingsService)
    {
        $this->authorizeAdminPermission('website.footer.manage');

        $this->validate([
            'brand_name' => ['nullable', 'string', 'max:120'],
            'brand_description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'appstore_url' => ['nullable', 'string', 'max:500'],
            'playstore_url' => ['nullable', 'string', 'max:500'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'brand_logo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $oldLogo = $this->current_brand_logo;
        $newLogo = null;

        if ($this->brand_logo_upload) {
            $newLogo = $this->brand_logo_upload->store('footer/brand', 'public');
        }

        try {
            $values = [
                'footer.brand_name' => filled($this->brand_name) ? trim($this->brand_name) : null,
                'footer.brand_description' => $this->brand_description,
                'footer.address' => $this->address,
                'footer.email' => $this->email,
                'footer.phone' => $this->phone,
                'footer.appstore_url' => $this->appstore_url,
                'footer.playstore_url' => $this->playstore_url,
                'footer.copyright' => $this->copyright,
            ];

            if ($newLogo !== null) {
                $values['footer.brand_logo'] = $newLogo;
            }

            $settingsService->updateMany($values, 'footer');
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

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã lưu thông tin Footer!']);
    }

    public function removeBrandLogo(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');

        $oldLogo = $this->current_brand_logo;
        $settingsService->set('footer.brand_logo', null, 'footer');
        $this->deleteOwnedBrandLogo($oldLogo);
        $this->current_brand_logo = null;
        $this->brand_logo_upload = null;

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã xóa logo Footer. Footer sẽ dùng logo Website mặc định.']);
    }

    public function render()
    {
        return view('Website::livewire.admin.footer.footer-info');
    }

    private function deleteOwnedBrandLogo(?string $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'footer/brand/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
