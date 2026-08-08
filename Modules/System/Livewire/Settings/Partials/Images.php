<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Models\Setting;
use Illuminate\Support\Facades\Storage;

class Images extends Component
{
    use WithFileUploads;

    // ==============================
    // CURRENT VALUES (DB)
    // ==============================
    public $site_logo;
    public $site_favicon;

    // ==============================
    // NEW UPLOAD
    // ==============================
    public $new_logo;
    public $new_favicon;

    // ==============================
    // INIT
    // ==============================
    public function mount()
    {
        $this->site_logo = Setting::getValue('site_logo');
        $this->site_favicon = Setting::getValue('site_favicon');
    }

    // ==============================
    // VALIDATION RULES
    // ==============================
    protected function rules()
    {
        return [
            'new_logo'    => 'nullable|image|max:2048', // 2MB
            'new_favicon' => 'nullable|file|mimes:png,ico|max:1024', // 1MB
        ];
    }

    // ==============================
    // SAVE
    // ==============================
    public function save()
    {
        $this->validate();

        // ----------------------
        // LOGO
        // ----------------------
        if ($this->new_logo) {

            // delete old
            if ($this->site_logo && Storage::disk('public')->exists($this->site_logo)) {
                Storage::disk('public')->delete($this->site_logo);
            }

            $path = $this->new_logo->store('settings', 'public');

            Setting::setValue('site_logo', $path);

            $this->site_logo = $path;
            $this->new_logo = null;

            $this->dispatch(
                'logo-updated',
                url: asset('storage/' . $path) . '?v=' . md5($path . microtime(true)),
            );
        }

        // ----------------------
        // FAVICON
        // ----------------------
        if ($this->new_favicon) {

            if ($this->site_favicon && Storage::disk('public')->exists($this->site_favicon)) {
                Storage::disk('public')->delete($this->site_favicon);
            }

            $path = $this->new_favicon->store('settings', 'public');

            Setting::setValue('site_favicon', $path);

            $this->site_favicon = $path;
            $this->new_favicon = null;

            $this->dispatch(
                'favicon-updated',
                url: asset('storage/' . $path) . '?v=' . md5($path . microtime(true)),
                type: strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'ico' ? 'image/x-icon' : 'image/png',
            );
        }

        $this->dispatch('notify', type: 'success', message: 'Đã cập nhật hình ảnh');
    }

    // ==============================
    // REMOVE IMAGE (OPTIONAL)
    // ==============================
    public function remove($type)
    {
        if ($type === 'logo' && $this->site_logo) {
            Storage::disk('public')->delete($this->site_logo);
            Setting::setValue('site_logo', null);
            $this->site_logo = null;
        }

        if ($type === 'favicon' && $this->site_favicon) {
            Storage::disk('public')->delete($this->site_favicon);
            Setting::setValue('site_favicon', null);
            $this->site_favicon = null;
        }
    }

    // ==============================
    // RENDER
    // ==============================
    public function render()
    {
        return view('System::livewire.settings.partials.images');
    }
}
