<?php

namespace Modules\Admin\Livewire\Header;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Models\HeaderMenuItem;
use Modules\Website\Services\HeaderMenuService;

class HeaderSettingsHub extends Component
{
    public $activeTab = 'general';
    public $generalData = [];
    public $currentLocation = 'primary';
    public $isModalOpen = false;
    public $editingId = null;

    public $formData = [
        'title' => '',
        'url' => '',
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => true,
    ];

    public function mount(SettingsService $settingsService)
    {
        $this->generalData = $settingsService->getGroup('header');
        $this->generalData['brand_name'] = $this->generalData['brand_name'] ?? 'FlexBiz';
    }

    public function saveGeneral(SettingsService $settingsService)
    {
        $this->validate([
            'generalData.brand_name' => 'required|string|max:100',
            'generalData.topbar_hotline' => 'nullable|string|max:50',
            'generalData.topbar_email' => 'nullable|email',
        ]);

        $settingsService->updateGroup('header', $this->generalData);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Cập nhật cấu hình chung thành công!',
        ]);
    }

    public function render(HeaderMenuService $menuService)
    {
        return view('Admin::livewire.header.header-settings-hub', [
            'menuLocations' => $menuService->getAvailableLocations(),
            'menuTree' => $menuService->getMenuTreeForAdmin($this->currentLocation),
        ]);
    }

    public function openModal($id): void
    {
        $this->resetErrorBag();

        if ($id) {
            $this->editingId = $id;
            $item = HeaderMenuItem::findOrFail($id);
            $this->formData = [
                'title' => $item->title,
                'url' => $item->url,
                'parent_id' => $item->parent_id,
                'sort_order' => $item->sort_order,
                'is_active' => $item->is_active,
            ];
        } else {
            $this->editingId = null;
            $this->formData = [
                'title' => '',
                'url' => '',
                'parent_id' => null,
                'sort_order' => 0,
                'is_active' => true,
            ];
        }

        $this->isModalOpen = true;
    }

    public function saveMenuItem(HeaderMenuService $menuService): void
    {
        $this->validate([
            'formData.title' => 'required|string|max:100',
            'formData.url' => 'nullable|string',
        ]);

        $menu = $menuService->ensureMenu($this->currentLocation);
        $data = array_merge($this->formData, ['header_menu_id' => $menu->id]);

        if ($this->editingId) {
            $menuService->updateItem($this->editingId, $data);
        } else {
            $menuService->createItem($data);
        }

        $this->isModalOpen = false;
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Cập nhật mục menu thành công!']);
    }

    public function deleteMenuItem($id, HeaderMenuService $menuService): void
    {
        $menuService->deleteItem($id);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Đã xóa mục menu thành công!',
        ]);
    }
}
