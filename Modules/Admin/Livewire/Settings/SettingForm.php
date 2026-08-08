<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingForm extends Component
{
    use WithFileUploads;

    // ==========================================
    // 1. SYSTEM SETTINGS (CẤU HÌNH CỐ ĐỊNH)
    // ==========================================
    public $settings = [
        'site_name' => '',
        'site_email' => '',
        'site_hotline' => '',
        'site_address' => '',
        'seo_title' => '',
        'seo_description' => '',
        'social_facebook' => '',
        'social_zalo' => '',
        'header_script' => '',
    ];

    public $site_logo;      // Path ảnh cũ
    public $new_logo;       // File mới upload

    public $site_favicon;   // Path ảnh cũ
    public $new_favicon;    // File mới upload

    // ==========================================
    // 2. DYNAMIC SETTINGS (CẤU HÌNH ĐỘNG)
    // ==========================================
    public $customSettings = []; // Danh sách Settings lấy từ DB

    public $dynamicValues = [];  // Chứa giá trị: Text, HTML, Mảng Gallery (ảnh cũ)
    public $dynamicImages = [];  // Chứa file upload: Image đơn
    public $galleryUploads = []; // Chứa file upload: Gallery (nhiều ảnh)

    // Form thêm mới
    public $newField = [
        'label' => '',
        'key'   => '',
        'type'  => 'text',
    ];

    public $activeTab = 'general';

    public function updatedNewFavicon()
    {
        $this->validateFavicon();
    }

    protected function validateFavicon(): void
    {
        $this->validate([
            'new_favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
        ], [
            'new_favicon.file' => 'Icon tải lên không hợp lệ.',
            'new_favicon.mimes' => 'Icon phải có định dạng PNG hoặc ICO.',
            'new_favicon.max' => 'Dung lượng icon không được vượt quá 512 KB.',
        ]);

        if (!$this->new_favicon) {
            return;
        }

        $imageSize = @getimagesize($this->new_favicon->getRealPath());
        $width = $imageSize[0] ?? null;
        $height = $imageSize[1] ?? null;

        if (!$width || !$height) {
            throw ValidationException::withMessages([
                'new_favicon' => 'Không thể đọc file icon. Vui lòng chọn file PNG hoặc ICO hợp lệ.',
            ]);
        }

        if ($width !== $height || !in_array($width, [32, 64], true)) {
            throw ValidationException::withMessages([
                'new_favicon' => "Icon phải là ảnh vuông 32x32 hoặc 64x64 pixel (file hiện tại: {$width}x{$height}).",
            ]);
        }
    }

    /**
     * Khởi tạo dữ liệu
     */
    public function mount()
    {
        // 1. Load System Settings
        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = Setting::getValue($key);
        }

        $this->site_logo = Setting::getValue('site_logo');
        $this->site_favicon = Setting::getValue('site_favicon');

        // 2. Load Custom Settings
        $this->loadCustomSettings();
    }

    /**
     * Load cấu hình động và map vào biến hiển thị
     */
    public function loadCustomSettings()
    {
        $this->customSettings = Setting::where('group_name', 'custom')->get();

        foreach ($this->customSettings as $setting) {

            // A. Gallery: Decode JSON thành Mảng để hiển thị
            if ($setting->type === 'gallery') {
                $decoded = json_decode($setting->value, true);
                // Đảm bảo luôn là mảng (tránh null)
                $this->dynamicValues[$setting->id] = is_array($decoded) ? $decoded : [];
            }

            // B. Image đơn: Bỏ qua (Xử lý riêng ở view hiển thị ảnh)
            elseif ($setting->type === 'image') {
                continue;
            }

            // C. Text / Textarea / HTML: Gán trực tiếp
            else {
                $this->dynamicValues[$setting->id] = $setting->value;
            }
        }
    }

    /**
     * Chuyển Tab
     */
    public function setTab($tab)
    {
        $allowedTabs = ['general', 'images', 'seo', 'custom'];

        $this->activeTab = in_array($tab, $allowedTabs, true) ? $tab : 'general';
    }

    /**
     * Thêm trường cấu hình mới
     */
    public function addField()
    {
        $this->validate([
            'newField.label' => 'required|string|max:255',
            'newField.key'   => 'required|alpha_dash|unique:settings,key',
            'newField.type'  => 'required|in:text,textarea,image,html,gallery',
        ], [
            'newField.key.unique' => 'Key này đã tồn tại.',
            'newField.key.alpha_dash' => 'Key không được chứa dấu cách hoặc ký tự đặc biệt.',
        ]);

        Setting::create([
            'label'      => $this->newField['label'],
            'key'        => Str::slug($this->newField['key'], '_'),
            'type'       => $this->newField['type'],
            'value'      => null,
            'group_name' => 'custom',
        ]);

        // Reset form & Reload
        $this->newField = ['label' => '', 'key' => '', 'type' => 'text'];
        $this->loadCustomSettings();

        session()->flash('success', 'Đã thêm trường mới.');
    }

    /**
     * Xóa trường cấu hình
     */
    public function deleteField($id)
    {
        Setting::destroy($id);
        $this->loadCustomSettings();
        session()->flash('success', 'Đã xóa trường cấu hình.');
    }

    /**
     * Xóa 1 ảnh cụ thể trong Gallery (khi chưa bấm Save cũng xóa trên giao diện)
     */
    public function removeGalleryImage($settingId, $index)
    {
        $currentImages = $this->dynamicValues[$settingId] ?? [];

        if (isset($currentImages[$index])) {
            unset($currentImages[$index]);
            // Re-index lại mảng (0, 1, 2...) để tránh lỗi JSON object
            $this->dynamicValues[$settingId] = array_values($currentImages);
        }
    }

    /**
     * LƯU TOÀN BỘ CẤU HÌNH
     */
    public function save()
    {
        $this->validateFavicon();

        // ------------------------------------
        // 1. LƯU SYSTEM SETTINGS
        // ------------------------------------
        foreach ($this->settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $this->dispatch('site-name-updated');

        // Upload Logo
        if ($this->new_logo) {
            $path = $this->new_logo->store('settings', 'public');
            Setting::setValue('site_logo', $path);
            $this->site_logo = $path;
            $this->new_logo = null;

            $this->dispatch(
                'logo-updated',
                url: asset('storage/' . $path) . '?v=' . md5($path . microtime(true)),
            );
        }

        // Upload Favicon
        if ($this->new_favicon) {
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

        // ------------------------------------
        // 2. LƯU CUSTOM SETTINGS
        // ------------------------------------
        foreach ($this->customSettings as $setting) {

            // === CASE: IMAGE (Đơn) ===
            if ($setting->type === 'image') {
                if (isset($this->dynamicImages[$setting->id])) {
                    $path = $this->dynamicImages[$setting->id]->store('settings/custom', 'public');
                    $setting->update(['value' => $path]);
                    unset($this->dynamicImages[$setting->id]); // Xóa temp
                }
            }

            // === CASE: GALLERY (Nhiều ảnh) ===
            elseif ($setting->type === 'gallery') {

                // Lấy mảng ảnh hiện tại (đã trừ đi những ảnh bị xóa bởi removeGalleryImage)
                $currentImages = $this->dynamicValues[$setting->id] ?? [];
                if (!is_array($currentImages)) $currentImages = [];

                // Xử lý file mới upload
                if (!empty($this->galleryUploads[$setting->id])) {
                    foreach ($this->galleryUploads[$setting->id] as $photo) {
                        $path = $photo->store('settings/gallery', 'public');
                        $currentImages[] = $path;
                    }
                    // Reset upload
                    unset($this->galleryUploads[$setting->id]);
                }
 
                // Cập nhật vào DB (Encode JSON)
                $setting->update(['value' => json_encode($currentImages)]);

                // Cập nhật lại UI
                $this->dynamicValues[$setting->id] = $currentImages;
            }

            // === CASE: TEXT / HTML / TEXTAREA ===
            else {
                if (isset($this->dynamicValues[$setting->id])) {
                    $setting->update(['value' => $this->dynamicValues[$setting->id]]);
                }
            }
        }

        // Reload lại lần nữa để chắc chắn đồng bộ
        $this->loadCustomSettings();

        session()->flash('success', 'Đã lưu toàn bộ cấu hình hệ thống.');
    }

    public function render()
    {
        return view('Admin::livewire.settings.setting-form');
    }
}
