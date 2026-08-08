<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Modules\System\Models\Setting;

class General extends Component
{
    public $settings = [
        'site_name' => '',
        'site_email' => '',
        'site_hotline' => '',
        'site_address' => '',
    ];

    public function mount()
    {
        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = Setting::getValue($key);
        }
    }

    public function save()
    {
        $this->validate([
            'settings.site_name' => 'required|string|max:255',
            'settings.site_email' => 'nullable|email',
            'settings.site_hotline' => 'nullable|string|max:50',
            'settings.site_address' => 'nullable|string|max:500',
        ]);

        foreach ($this->settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $this->dispatch('site-name-updated');
        $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình chung');
    }

    public function render()
    {
        return view('System::livewire.settings.partials.general');
    }
}
