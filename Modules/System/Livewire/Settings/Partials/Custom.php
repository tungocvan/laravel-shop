<?php

namespace Modules\System\Livewire\Settings\Partials;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Custom extends Component
{
    use WithFileUploads;

    public $customSettings = [];

    public $dynamicValues = [];
    public $dynamicImages = [];
    public $galleryUploads = [];

    public $newField = [
        'label' => '',
        'key'   => '',
        'type'  => 'text',
    ];

    // ==============================
    // INIT
    // ==============================
    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->customSettings = Setting::where('group_name', 'custom')->get();

        foreach ($this->customSettings as $setting) {

            if ($setting->type === 'gallery') {
                $this->dynamicValues[$setting->id] = json_decode($setting->value, true) ?? [];
            } elseif ($setting->type === 'image') {
                continue;
            } else {
                $this->dynamicValues[$setting->id] = $setting->value;
            }
        }
    }

    // ==============================
    // ADD FIELD
    // ==============================
    public function addField()
    {
        $this->validate([
            'newField.label' => 'required|string|max:255',
            'newField.key'   => 'required|alpha_dash|unique:settings,key',
            'newField.type'  => 'required|in:text,textarea,image,html,gallery',
        ]);

        Setting::create([
            'label'      => $this->newField['label'],
            'key'        => Str::slug($this->newField['key'], '_'),
            'type'       => $this->newField['type'],
            'group_name' => 'custom',
        ]);

        $this->reset('newField');
        $this->loadSettings();

        $this->dispatch('notify', type: 'success', message: 'Đã thêm field');
    }

    // ==============================
    // DELETE
    // ==============================
    public function deleteField($id)
    {
        Setting::destroy($id);
        $this->loadSettings();
    }

    // ==============================
    // GALLERY REMOVE
    // ==============================
    public function removeGalleryImage($id, $index)
    {
        $images = $this->dynamicValues[$id] ?? [];

        unset($images[$index]);

        $this->dynamicValues[$id] = array_values($images);
    }

    // ==============================
    // SAVE
    // ==============================
    public function save()
    {
        foreach ($this->customSettings as $setting) {

            // IMAGE
            if ($setting->type === 'image') {
                if (isset($this->dynamicImages[$setting->id])) {

                    if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    $path = $this->dynamicImages[$setting->id]->store('settings/custom', 'public');

                    $setting->update(['value' => $path]);
                }
            }

            // GALLERY
            elseif ($setting->type === 'gallery') {

                $current = $this->dynamicValues[$setting->id] ?? [];

                if (!empty($this->galleryUploads[$setting->id])) {
                    foreach ($this->galleryUploads[$setting->id] as $file) {
                        $current[] = $file->store('settings/gallery', 'public');
                    }
                }

                $setting->update([
                    'value' => json_encode($current)
                ]);

                $this->dynamicValues[$setting->id] = $current;
            }

            // TEXT / HTML
            else {
                $setting->update([
                    'value' => $this->dynamicValues[$setting->id] ?? null
                ]);
            }
        }

        $this->loadSettings();

        $this->dispatch('notify', type: 'success', message: 'Đã lưu cấu hình');
    }

    public function render()
    {
        return view('System::livewire.settings.partials.custom');
    }
}
