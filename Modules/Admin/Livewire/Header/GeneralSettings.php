<?php

namespace Modules\Admin\Livewire\Header;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\SettingsService;

class GeneralSettings extends Component
{
    use WithFileUploads;

    public $hotline;
    public $email;
    public $help_url;
    public $order_tracking_url;
    public $brand_name;

    protected $rules = [
        'hotline' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:100',
        'brand_name' => 'required|string|max:100',
        'help_url' => 'nullable|string|max:255',
        'order_tracking_url' => 'nullable|string|max:255',
    ];

    public function mount(SettingsService $settingsService)
    {
        $this->hotline = $settingsService->get('header.topbar.hotline','0903971949');
        $this->email = $settingsService->get('header.topbar.email','tungocvan@gmail.com');
        $this->help_url = $settingsService->get('header.topbar.help_url','/');
        $this->order_tracking_url = $settingsService->get('header.topbar.order_tracking_url','account/orders');
        $this->brand_name = $settingsService->get('header.brand_name', 'FlexBiz');
    }

    public function save(SettingsService $settingsService)
    {
        $this->authorizePermission('admin.header.update');

        $this->validate();

        $settingsService->updateMany([
            'header.topbar.hotline' => $this->hotline,
            'header.topbar.email' => $this->email,
            'header.topbar.help_url' => $this->help_url,
            'header.topbar.order_tracking_url' => $this->order_tracking_url,
            'header.brand_name' => $this->brand_name,
        ]);

        // ✅ FIX: Sử dụng Native Livewire Event thay vì Library
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Đã lưu cấu hình Header thành công!'
        ]);
    }

    public function render()
    {
        return view('Admin::livewire.header.general-settings');
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
