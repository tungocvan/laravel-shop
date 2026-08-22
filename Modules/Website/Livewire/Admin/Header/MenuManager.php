<?php

namespace Modules\Website\Livewire\Admin\Header;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Livewire\Component;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\HeaderMenuItem;
use Modules\Website\Services\HeaderMenuService;

class MenuManager extends Component
{
    use AuthorizesAdminPermissions;

    public string $location = 'primary';
    public array $menuLocations = [];

    public bool $isModalOpen = false;
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

    public function mount(HeaderMenuService $service): void
    {
        $this->menuLocations = $service->getAvailableLocations();
        if (! array_key_exists($this->location, $this->menuLocations)) {
            $this->location = (string) array_key_first($this->menuLocations);
        }
    }

    public function render(HeaderMenuService $service)
    {
        $this->authorizeAdminPermission('website.menu.manage');

        $currentMenu = $service->getMenuForAdmin($this->location);
        $menuTree = $currentMenu ? $service->getMenuTreeForAdmin($this->location) : new Collection;
        $flatItems = $currentMenu
            ? HeaderMenuItem::where('header_menu_id', $currentMenu->id)->whereNull('parent_id')->orderBy('sort_order')->get()
            : new Collection;

        return view('Website::livewire.admin.header.menu-manager', [
            'menuTree' => $menuTree,
            'flatItems' => $flatItems,
            'currentMenuId' => $currentMenu?->id,
        ]);
    }

    public function openModal($id = null): void
    {
        $this->reset(['title', 'url', 'parent_id', 'icon', 'sort_order', 'is_active', 'editingId']);
        $this->is_active = true;

        if ($id) {
            $this->editingId = $id;
            $item = HeaderMenuItem::findOrFail($id);
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
        $this->authorizeAdminPermission('website.menu.manage');
        $this->validate();

        $menu = $service->ensureMenu($this->location);
        if ($this->parent_id) {
            $validParent = HeaderMenuItem::query()
                ->where('header_menu_id', $menu->id)
                ->whereKey($this->parent_id)
                ->exists();
            if (! $validParent) {
                $this->addError('parent_id', 'Menu cha không thuộc vị trí menu hiện tại.');
                return;
            }
        }

        $data = [
            'header_menu_id' => $menu->id,
            'title' => $this->title,
            'url' => $this->url,
            'parent_id' => $this->parent_id ?: null,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
        ];

        $this->editingId ? $service->updateItem((int) $this->editingId, $data) : $service->createItem($data);
        $this->isModalOpen = false;
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu menu item.',
        ]]);
    }

    public function delete($id, HeaderMenuService $service): void
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $service->deleteItem((int) $id);
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã xóa menu item.',
        ]]);
    }

    public function moveItemByDrag(
        int $itemId,
        ?int $targetParentId,
        array $orderedIds,
        HeaderMenuService $service
    ): void {
        $this->authorizeAdminPermission('website.menu.manage');
        $menu = $service->getMenuForAdmin($this->location);
        if (! $menu) {
            return;
        }

        try {
            $service->moveItemByDrag($menu->id, $itemId, $targetParentId, $orderedIds);
        } catch (InvalidArgumentException $exception) {
            $this->addError('menu', $exception->getMessage());
        }
    }
}
