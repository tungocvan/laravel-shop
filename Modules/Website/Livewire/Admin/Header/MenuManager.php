<?php

namespace Modules\Website\Livewire\Admin\Header;

use Livewire\Component;
use Modules\Website\Services\HeaderMenuService;
use Modules\Website\Models\HeaderMenu;
use Modules\Website\Models\HeaderMenuItem; // Dùng model để binding modal cho tiện

class MenuManager extends Component
{
    public $location = 'primary'; // Mặc định chỉnh Main Menu
    public $menuLocations = [
        'primary' => 'Desktop Main Menu',
        'mobile' => 'Mobile Slide-over',
        'admin' => 'Admin Menu Dropdown'
    ];

    // State cho Modal
    public $isModalOpen = false;
    public $editingId = null;

    // Form Data
    public $title, $url, $parent_id, $icon, $sort_order = 0, $is_active = true;

    protected $listeners = ['refreshMenu' => '$refresh'];

    protected $rules = [
        'title' => 'required|string|max:100',
        'url' => 'nullable|string|max:255',
        'parent_id' => 'nullable|exists:header_menu_items,id',
        'sort_order' => 'integer',
        'is_active' => 'boolean'
    ];

    public function render(HeaderMenuService $service)
    {
        // Đảm bảo menu cha tồn tại
        $currentMenu = HeaderMenu::firstOrCreate(
            ['location' => $this->location],
            ['name' => $this->menuLocations[$this->location]]
        );

        // Lấy cây menu
        $menuTree = $service->getMenuTreeByLocation($this->location);

        // Lấy danh sách items phẳng để làm Dropdown Parent (trừ chính nó nếu đang edit)
        $flatItems = HeaderMenuItem::where('header_menu_id', $currentMenu->id)
            ->whereNull('parent_id') // Chỉ cho phép 2 cấp (Root -> Child) để đơn giản UI
            ->get();

        return view('Website::livewire.admin.header.menu-manager', [
            'menuTree' => $menuTree,
            'flatItems' => $flatItems,
            'currentMenuId' => $currentMenu->id
        ]);
    }

    public function openModal($id = null)
    {
        $this->reset(['title', 'url', 'parent_id', 'icon', 'sort_order', 'is_active', 'editingId']);

        if ($id) {
            $this->editingId = $id;
            $item = HeaderMenuItem::find($id);
            $this->title = $item->title;
            $this->url = $item->url;
            $this->parent_id = $item->parent_id;
            $this->sort_order = $item->sort_order;
            $this->is_active = $item->is_active;
        }

        $this->isModalOpen = true;
    }

    public function save(HeaderMenuService $service)
    {
        $this->validate();

        // Lấy ID menu hiện tại
        $menuId = HeaderMenu::where('location', $this->location)->value('id');

        $data = [
            'header_menu_id' => $menuId,
            'title' => $this->title,
            'url' => $this->url,
            'parent_id' => $this->parent_id ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active
        ];

        if ($this->editingId) {
            $service->updateItem($this->editingId, $data);
        } else {
            $service->createItem($data);
        }

        $this->isModalOpen = false;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu menu item!']);
    }

    public function delete($id, HeaderMenuService $service)
    {
        $service->deleteItem($id);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã xóa menu item!']);
    }
}
