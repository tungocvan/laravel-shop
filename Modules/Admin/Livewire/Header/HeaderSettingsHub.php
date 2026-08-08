<?php

namespace Modules\Admin\Livewire\Header;

use Livewire\Component;
use Modules\Admin\Services\SettingsService;
use Modules\Admin\Services\HeaderMenuService;
use \Modules\Admin\Models\HeaderMenuItem;

class HeaderSettingsHub extends Component
{
    // Tab State
    public $activeTab = 'general'; // 'general' hoặc 'menu'

    // Dữ liệu cho General Tab
    public $generalData = [];

    // Dữ liệu cho Menu Tab
    public $currentLocation = 'primary';
    // State cho Modal
    public $isModalOpen = false;
    public $editingId = null;
    public $formData = [
        'title' => '',
        'url' => '',
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => true
    ];

    public function mount(SettingsService $settingsService)
    {
        // Load toàn bộ setting group 'header' một lần duy nhất
        $this->generalData = $settingsService->getGroup('header');

        // Gán mặc định nếu thiếu (tránh lỗi null input)
        $this->generalData['brand_name'] = $this->generalData['brand_name'] ?? 'FlexBiz';
    }

    /**
     * Xử lý lưu thông tin chung (General Tab)
     */
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
            'message' => 'Cập nhật cấu hình chung thành công!'
        ]);
    }

    public function render(HeaderMenuService $menuService)
    {
        return view('Admin::livewire.header.header-settings-hub', [
            'menuLocations' => $menuService->getAvailableLocations(),
            // Thay getMenuTreeByLocation() bằng getMenuTree()
            'menuTree' => $menuService->getMenuTree($this->currentLocation),
        ]);
    }
    public function openModal($id = null, HeaderMenuService $menuService)
{
    $this->resetErrorBag();
    if ($id) {
        $this->editingId = $id;
        $item = HeaderMenuItem::find($id);
        $this->formData = [
            'title' => $item->title,
            'url' => $item->url,
            'parent_id' => $item->parent_id,
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active
        ];
    } else {
        $this->editingId = null;
        $this->formData = ['title' => '', 'url' => '', 'parent_id' => null, 'sort_order' => 0, 'is_active' => true];
    }
    $this->isModalOpen = true;
}

public function saveMenuItem(HeaderMenuService $menuService)
{
    $this->validate([
        'formData.title' => 'required|string|max:100',
        'formData.url' => 'nullable|string',
    ]);

    // Tìm hoặc tạo Menu cha cho vị trí hiện tại
    $menu = $menuService->findOrCreateMenu($this->currentLocation);

    $data = array_merge($this->formData, ['header_menu_id' => $menu->id]);

    $menuService->saveItem($data, $this->editingId);

    $this->isModalOpen = false;
    $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Cập nhật mục menu thành công!']);
}
/**
 * Xóa Menu Item thông qua Service
 */
public function deleteMenuItem($id, HeaderMenuService $menuService)
{
    $menuService->deleteItem($id);

    $this->dispatch('show-toast', [
        'type' => 'success',
        'message' => 'Đã xóa mục menu thành công!'
    ]);
}
}
