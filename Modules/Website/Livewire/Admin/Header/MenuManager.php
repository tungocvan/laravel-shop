<?php

namespace Modules\Website\Livewire\Admin\Header;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\HeaderMenuItem;
use Modules\Website\Services\HeaderMenuService;

class MenuManager extends Component
{
    use AuthorizesAdminPermissions;

    public string $location = 'primary';
    public array $menuLocations = [];
    public array $headerActions = [];
    public bool $isModalOpen = false;
    public $editingId = null;
    public $title;
    public $url;
    public $parent_id;
    public $icon;
    public $sort_order = 0;
    public $is_active = true;

    protected $listeners = ['refreshMenu' => '$refresh'];
    protected $rules = ['title' => 'required|string|max:100', 'url' => 'nullable|string|max:255', 'parent_id' => 'nullable|exists:header_menu_items,id', 'sort_order' => 'integer', 'is_active' => 'boolean'];

    public function mount(HeaderMenuService $service, SettingsService $settings): void
    {
        $this->menuLocations = $service->getAvailableLocations();
        if (! array_key_exists($this->location, $this->menuLocations)) $this->location = (string) array_key_first($this->menuLocations);
        $saved = $settings->get('header.actions');
        $this->headerActions = $this->normalizeActions(is_array($saved) ? $saved : []);
    }

    public function render(HeaderMenuService $service)
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $currentMenu = $service->getMenuForAdmin($this->location);
        $menuTree = $currentMenu ? $service->getMenuTreeForAdmin($this->location) : new Collection;
        $flatItems = $currentMenu ? HeaderMenuItem::where('header_menu_id', $currentMenu->id)->whereNull('parent_id')->orderBy('sort_order')->get() : new Collection;
        return view('Website::livewire.admin.header.menu-manager', compact('menuTree', 'flatItems') + ['currentMenuId' => $currentMenu?->id]);
    }

    public function saveHeaderActions(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $this->validate([
            'headerActions.order' => ['required','array','size:3'],
            'headerActions.account.guest.login_label' => ['required','string','max:40'],
            'headerActions.account.guest.register_label' => ['required','string','max:40'],
            'headerActions.account.authenticated.logout_label' => ['required','string','max:40'],
        ]);
        $this->headerActions = $this->normalizeActions($this->headerActions);
        $settings->set('header.actions', $this->headerActions, 'header', 'json');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu hành động Header.']]);
    }

    public function reorderHeaderActions(array $order): void
    {
        $allowed = ['wishlist','cart','account'];
        $clean = array_values(array_unique(array_filter($order, fn ($item) => in_array($item, $allowed, true))));
        foreach ($allowed as $item) if (! in_array($item, $clean, true)) $clean[] = $item;
        $this->headerActions['order'] = $clean;
    }

    public function openModal($id = null): void
    {
        $this->reset(['title','url','parent_id','icon','sort_order','is_active','editingId']); $this->is_active = true;
        if ($id) { $this->editingId=$id; $item=HeaderMenuItem::findOrFail($id); $this->title=$item->title; $this->url=$item->url; $this->parent_id=$item->parent_id; $this->sort_order=$item->sort_order; $this->is_active=$item->is_active; }
        $this->isModalOpen = true;
    }

    public function save(HeaderMenuService $service): void
    {
        $this->authorizeAdminPermission('website.menu.manage'); $this->validate(); $menu=$service->ensureMenu($this->location);
        if ($this->parent_id && ! HeaderMenuItem::query()->where('header_menu_id',$menu->id)->whereKey($this->parent_id)->exists()) { $this->addError('parent_id','Menu cha không thuộc vị trí menu hiện tại.'); return; }
        $data=['header_menu_id'=>$menu->id,'title'=>$this->title,'url'=>$this->url,'parent_id'=>$this->parent_id ?: null,'sort_order'=>(int)$this->sort_order,'is_active'=>(bool)$this->is_active];
        $this->editingId ? $service->updateItem((int)$this->editingId,$data) : $service->createItem($data); $this->isModalOpen=false;
        $this->dispatch('show-toast', [['type'=>'success','message'=>'Đã lưu menu item.']]);
    }

    public function delete($id, HeaderMenuService $service): void { $this->authorizeAdminPermission('website.menu.manage'); $service->deleteItem((int)$id); $this->dispatch('show-toast', [['type'=>'success','message'=>'Đã xóa menu item.']]); }

    public function moveItemByDrag(int $itemId, ?int $targetParentId, array $orderedIds, HeaderMenuService $service): void
    {
        $this->authorizeAdminPermission('website.menu.manage'); $menu=$service->getMenuForAdmin($this->location); if(!$menu)return;
        try { $service->moveItemByDrag($menu->id,$itemId,$targetParentId,$orderedIds); } catch (InvalidArgumentException $e) { $this->addError('menu',$e->getMessage()); }
    }

    private function normalizeActions(array $value): array
    {
        $defaults=(array)config('website.header.actions',[]); $merged=array_replace_recursive($defaults,$value);
        $allowed=['wishlist','cart','account']; $order=array_values(array_unique(array_filter((array)($merged['order']??[]),fn($v)=>in_array($v,$allowed,true))));
        foreach($allowed as $item) if(!in_array($item,$order,true))$order[]=$item; $merged['order']=$order;
        foreach(['wishlist','cart','account'] as $key)$merged[$key]['enabled']=(bool)($merged[$key]['enabled']??true);
        foreach(['login_enabled','register_enabled'] as $key)$merged['account']['guest'][$key]=(bool)($merged['account']['guest'][$key]??true);
        foreach(['show_avatar','show_name','logout_enabled'] as $key)$merged['account']['authenticated'][$key]=(bool)($merged['account']['authenticated'][$key]??true);
        return $merged;
    }
}
