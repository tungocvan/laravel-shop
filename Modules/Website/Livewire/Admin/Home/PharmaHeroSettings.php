<?php

namespace Modules\Website\Livewire\Admin\Home;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;

class PharmaHeroSettings extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public array $hero = [];
    public $heroImage;

    public function mount(SettingsService $settings): void
    {
        $this->hero = array_merge([
            'eyebrow' => 'Thông tin dược phẩm',
            'title' => 'Khám phá danh mục sản phẩm dược phẩm',
            'description' => 'Tìm kiếm và tham khảo thông tin sản phẩm theo danh mục chuyên môn, với dữ liệu được cập nhật từ hệ thống.',
            'button' => 'Tra cứu sản phẩm',
            'image' => '',
        ], (array) $settings->get('website.pharma.hero', []));
    }

    public function save(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $validated = $this->validate([
            'hero.eyebrow' => 'required|string|max:80',
            'hero.title' => 'required|string|max:180',
            'hero.description' => 'required|string|max:500',
            'hero.button' => 'required|string|max:60',
            'hero.image' => 'nullable|string|max:500',
            'heroImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $image = $this->heroImage?->store('website/pharma/hero', 'public');
        $hero = $validated['hero'];
        if ($image) {
            $hero['image'] = $image;
        }
        try {
            $settings->set('website.pharma.hero', $hero, 'website', 'json');
        } catch (\Throwable $exception) {
            if ($image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($image);
            }
            throw $exception;
        }
        $this->hero = $hero;
        $this->heroImage = null;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu nội dung Hero Pharma.']);
    }

    public function render()
    {
        return view('Website::livewire.admin.home.pharma-hero-settings');
    }
}
