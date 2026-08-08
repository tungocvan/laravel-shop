<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Modules\System\Models\Setting;

class Seo extends Component
{
    public $settings = [
        'seo_title'        => '',
        'seo_description'  => '',
        'social_facebook'  => '',
        'social_zalo'      => '',
        'header_script'    => '',
    ];

    // ==============================
    // INIT
    // ==============================
    public function mount()
    {
        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = Setting::getValue($key);
        }
    }

    // ==============================
    // VALIDATE
    // ==============================
    protected function rules()
    {
        return [
            'settings.seo_title'       => 'nullable|string|max:255',
            'settings.seo_description' => 'nullable|string',
            'settings.social_facebook' => 'nullable|url',
            'settings.social_zalo'     => 'nullable|string|max:50',
            'settings.header_script'   => 'nullable|string',
        ];
    }

    // ==============================
    // SAVE
    // ==============================
    public function save()
    {
        $this->validate();

        foreach ($this->settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình SEO');
    }

    // ==============================
    // RENDER
    // ==============================
    public function render()
    {
        return view('System::livewire.settings.partials.seo');
    }
}
