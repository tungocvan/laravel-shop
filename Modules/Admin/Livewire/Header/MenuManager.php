<?php

namespace Modules\Admin\Livewire\Header;

use Livewire\Component;
use Modules\Admin\Models\HeaderMenu;
use Modules\Admin\Models\HeaderMenuItem;
use Modules\Admin\Services\HeaderMenuService;

class MenuManager extends Component
{
    public $location = 'primary';

    public $menuLocations = [
        'primary' => 'Desktop Main Menu',
        'mobile' => 'Mobile Slide-over',
        'admin' => 'Admin Menu Dropdown',
    ];

    public $isModalOpen = false;
    public $editingId = null;

    public $title;
    public $url;
    public $parent_id;
    public $icon;
    public $sort_order = 0;
    public $is_active = true;

    protected $listeners = ['refreshMenu' => '$refresh'];

    protected $rules = [
        'title' => 'required|string|max:100',
        'url' => 'nullable|string|max:255',
        'parent_id' => 'nullable|exists:header_menu_items,id',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function render(HeaderMenuService $service)
    {
        $currentMenu = HeaderMenu::firstOrCreate(
            ['location' => $this->location],
            ['name' => $this->menuLocations[$this->location]]
        );

        $menuTree = $service->getMenuTreeByLocation($this->location);

        $flatItems = HeaderMenuItem::where('header_menu_id', $currentMenu->id)
            ->whereNull('parent_id')
            ->get();

        return view('Admin::livewire.header.menu-manager', [
            'menuTree' => $menuTree,
            'flatItems' => $flatItems,
            'currentMenuId' => $currentMenu->id,
        ]);
    }

    public function openModal($id = null): void
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

    public function save(HeaderMenuService $service): void
    {
        $this->authorizePermission('admin.header.update');
        $this->validate();

        $menuId = HeaderMenu::where('location', $this->location)->value('id');

        $data = [
            'header_menu_id' => $menuId,
            'title' => $this->title,
            'url' => $this->url,
            'parent_id' => $this->parent_id ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $service->updateItem($this->editingId, $data);
        } else {
            $service->createItem($data);
        }

        $this->isModalOpen = false;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Menu item saved.']);
    }

    public function delete($id, HeaderMenuService $service): void
    {
        $this->authorizePermission('admin.header.update');

        $service->deleteItem($id);
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Menu item deleted.']);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
