<?php

namespace Modules\Admin\Livewire\Banner;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\BannerService;

class BannerManager extends Component
{
    use WithFileUploads;

    // 2. LIVEWIRE_BANNER
    public $banners;
    public $isModalOpen = false;
    public $isEditMode = false;

    // Form Fields
    public $bannerId;
    public $title;
    public $link;
    public $sub_title;
    public $btn_text;
    public $position = 'hero'; // Default
    public $order = 0;
    public $is_active = true;

    // Uploads
    public $newImageDesktop;
    public $newImageMobile;
    public $currentImageDesktop; // Để hiển thị preview khi edit
    public $currentImageMobile;

    public function mount(BannerService $service)
    {
        $this->loadBanners($service);
    }
 
    public function loadBanners(BannerService $service)
    {
        $this->banners = $service->getAll();
    }

    public function render()
    {
        return view('Admin::livewire.banner.banner-manager');
    }

    // Actions
    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;

        // Find banner manually or via service
        $banner = \Modules\Admin\Models\Banner::find($id);

        $this->bannerId = $banner->id;
        $this->title = $banner->title;
        $this->sub_title = $banner->sub_title; // Mới
        $this->btn_text = $banner->btn_text;   // Mới
        $this->link = $banner->link;
        $this->position = $banner->position;
        $this->order = $banner->order;
        $this->is_active = $banner->is_active;

        $this->currentImageDesktop = $banner->image_desktop;
        $this->currentImageMobile = $banner->image_mobile;

        $this->isModalOpen = true;
    }

    public function save(BannerService $service)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'position' => 'required',
            'order' => 'integer',
        ];

        // Nếu tạo mới bắt buộc phải có ảnh desktop
        if (!$this->isEditMode) {
            $rules['newImageDesktop'] = 'required|image|max:3072'; // 2MB
        }

        $this->validate($rules);

        $data = [
            'id' => $this->bannerId,
            'title' => $this->title,
            'sub_title' => $this->sub_title, // Mới
            'btn_text' => $this->btn_text,   // Mới
            'link' => $this->link,
            'position' => $this->position,
            'order' => $this->order,
            'is_active' => $this->is_active,
        ];

        $service->save($data, $this->newImageDesktop, $this->newImageMobile);

        $this->isModalOpen = false;
        $this->loadBanners($service);
        $this->dispatch('show-toast', type: 'success', message: 'Đã lưu Banner!');
    }

    public function delete($id, BannerService $service)
    {
        $service->delete($id);
        $this->loadBanners($service);
        $this->dispatch('show-toast', type: 'success', message: 'Đã xóa Banner!');
    }

    public function resetForm()
    {
        // Reset thêm biến mới
        $this->reset(['bannerId', 'title', 'sub_title', 'btn_text', 'link', 'position', 'order', 'is_active', 'newImageDesktop', 'newImageMobile', 'currentImageDesktop', 'currentImageMobile']);
    }
    // End 2.
}
