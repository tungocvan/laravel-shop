<?php

namespace Modules\Admin\Livewire\Footer;

use Livewire\Component;
use Modules\Website\Services\SettingsService;

class FooterInfo extends Component
{
    // Brand Info
    public $brand_description;
    public $address;
    public $email;
    public $phone;

    // App Links
    public $appstore_url;
    public $playstore_url;

    // Bottom Bar
    public $copyright;

    public function mount(SettingsService $settingsService)
    {
        $this->brand_description = $settingsService->get('footer.brand_description');
        $this->address = $settingsService->get('footer.address');
        $this->email = $settingsService->get('footer.email');
        $this->phone = $settingsService->get('footer.phone');

        $this->appstore_url = $settingsService->get('footer.appstore_url');
        $this->playstore_url = $settingsService->get('footer.playstore_url');

        $this->copyright = $settingsService->get('footer.copyright', '© 2024 FlexBiz. All rights reserved.');
    }

    public function save(SettingsService $settingsService)
    {
        $settingsService->updateMany([
            'footer.brand_description' => $this->brand_description,
            'footer.address' => $this->address,
            'footer.email' => $this->email,
            'footer.phone' => $this->phone,
            'footer.appstore_url' => $this->appstore_url,
            'footer.playstore_url' => $this->playstore_url,
            'footer.copyright' => $this->copyright,
        ]);

        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Đã lưu thông tin Footer!']);
    }

    public function render()
    {
        return view('Admin::livewire.footer.footer-info');
    }
}
