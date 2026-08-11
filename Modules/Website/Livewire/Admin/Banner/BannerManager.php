<?php

namespace Modules\Website\Livewire\Admin\Banner;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\Banner;
use Modules\Website\Services\BannerService;

class BannerManager extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public $banners;

    public $isModalOpen = false;

    public $isEditMode = false;

    public $bannerId;

    public $title;

    public $link;

    public $sub_title;

    public $btn_text;

    public $position = 'hero';

    public $order = 0;

    public $is_active = true;

    public $newImageDesktop;

    public $newImageMobile;

    public $currentImageDesktop;

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
        return view('Website::livewire.admin.banner.banner-manager');
    }

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
        $banner = Banner::findOrFail($id);
        $this->bannerId = $banner->id;
        $this->title = $banner->title ?? '';
        $this->sub_title = $banner->sub_title ?? '';
        $this->btn_text = $banner->btn_text;
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
        $this->authorizeAdminPermission('website.banner.manage');

        $rules = [
            'title' => 'nullable|string|max:255',
            'position' => 'required',
            'order' => 'integer',
            'newImageDesktop' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'newImageMobile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ];

        if (! $this->isEditMode) {
            $rules['newImageDesktop'] = 'required|image|mimes:jpg,jpeg,png,webp|max:3072';
        }

        $this->validate($rules);

        $service->save([
            'id' => $this->bannerId,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'btn_text' => $this->btn_text,
            'link' => $this->link,
            'position' => $this->position,
            'order' => $this->order,
            'is_active' => $this->is_active,
        ], $this->newImageDesktop, $this->newImageMobile);

        $this->isModalOpen = false;
        $this->loadBanners($service);
        $this->dispatch('show-toast', type: 'success', message: 'Đã lưu Banner!');
    }

    public function delete($id, BannerService $service)
    {
        $this->authorizeAdminPermission('website.banner.manage');
        $service->delete($id);
        $this->loadBanners($service);
        $this->dispatch('show-toast', type: 'success', message: 'Đã xóa Banner!');
    }

    public function resetForm()
    {
        $this->reset(['bannerId', 'title', 'sub_title', 'btn_text', 'link', 'position', 'order', 'is_active', 'newImageDesktop', 'newImageMobile', 'currentImageDesktop', 'currentImageMobile']);
    }
}
