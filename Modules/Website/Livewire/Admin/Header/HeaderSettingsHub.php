<?php

namespace Modules\Website\Livewire\Admin\Header;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\HeaderMenuItem;
use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderMenuService;
use Modules\Website\Services\HeaderPresentationService;

class HeaderSettingsHub extends Component
{
    use AuthorizesAdminPermissions;

    public $activeTab = 'general';
    public $generalData = [];
    public $currentLocation = 'primary';
    public $isModalOpen = false;
    public $editingId = null;

    public array $builderSlots = [];
    public array $presentation = [];

    public $formData = [
        'title' => '',
        'url' => '',
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => true,
    ];

    private const BUILDER_SLOTS = [
        'desktop.topbar',
        'desktop.main.left',
        'desktop.main.center',
        'desktop.main.right',
        'mobile.search',
        'mobile.drawer',
    ];

    public function mount(SettingsService $settingsService, HeaderPresentationService $presentationService)
    {
        $this->generalData = $settingsService->getGroup('header');
        $this->generalData['brand_name'] = $this->generalData['brand_name'] ?? 'FlexBiz';

        $savedLayout = $settingsService->get('header.layout');
        $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.header.layout', []);
        foreach (self::BUILDER_SLOTS as $slot) {
            $items = data_get($rawLayout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }

        $savedPresentation = $settingsService->get('header.presentation');
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
    }

    public function saveGeneral(SettingsService $settingsService)
    {
        $this->authorizeAdminPermission('website.menu.manage');

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

    public function toggleComponent(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! isset($this->builderSlots[$slot][$index])) {
            return;
        }

        $current = (bool) ($this->builderSlots[$slot][$index]['enabled'] ?? true);
        $this->builderSlots[$slot][$index]['enabled'] = ! $current;
    }

    public function moveUp(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($index <= 0 || ! isset($this->builderSlots[$slot][$index])) {
            return;
        }

        [$this->builderSlots[$slot][$index - 1], $this->builderSlots[$slot][$index]] = [
            $this->builderSlots[$slot][$index],
            $this->builderSlots[$slot][$index - 1],
        ];
        $this->builderSlots[$slot] = array_values($this->builderSlots[$slot]);
    }

    public function moveDown(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! isset($this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1])) {
            return;
        }

        [$this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1]] = [
            $this->builderSlots[$slot][$index + 1],
            $this->builderSlots[$slot][$index],
        ];
        $this->builderSlots[$slot] = array_values($this->builderSlots[$slot]);
    }

    public function moveComponent(string $fromSlot, int $index, string $toSlot, HeaderComponentRegistry $registry): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! in_array($toSlot, self::BUILDER_SLOTS, true) || ! isset($this->builderSlots[$fromSlot][$index])) {
            return;
        }

        $item = $this->builderSlots[$fromSlot][$index];
        try {
            $registry->resolve((string) ($item['type'] ?? ''), $toSlot);
        } catch (\InvalidArgumentException) {
            $this->addError('builder', 'Component không được phép chuyển vào vị trí đã chọn.');
            return;
        }

        array_splice($this->builderSlots[$fromSlot], $index, 1);
        $this->builderSlots[$fromSlot] = array_values($this->builderSlots[$fromSlot]);
        $this->builderSlots[$toSlot][] = $item;
    }

    public function saveBuilder(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');

        $layout = [];
        foreach (self::BUILDER_SLOTS as $slot) {
            $clean = [];
            foreach ($this->builderSlots[$slot] ?? [] as $item) {
                if (! is_array($item) || ! is_string($item['type'] ?? null)) {
                    continue;
                }
                try {
                    $registry->resolve($item['type'], $slot);
                } catch (\InvalidArgumentException) {
                    continue;
                }
                $clean[] = [
                    'type' => $item['type'],
                    'enabled' => (bool) ($item['enabled'] ?? true),
                    'config' => is_array($item['config'] ?? null) ? $item['config'] : [],
                ];
            }
            data_set($layout, $slot, $clean);
        }

        $presentation = $presentationService->resolve($this->presentation);
        $settingsService->updateMany([
            'header.layout' => $layout,
            'header.presentation' => $presentation,
        ], 'header');

        $this->presentation = $presentation;
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Đã lưu bố cục Header.',
        ]);
    }

    public function resetBuilder(HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $layout = (array) config('website.header.layout', []);
        foreach (self::BUILDER_SLOTS as $slot) {
            $this->builderSlots[$slot] = array_values((array) data_get($layout, $slot, []));
        }
        $this->presentation = $presentationService->resolve((array) config('website.header.presentation', []));
    }

    public function render(HeaderMenuService $menuService, HeaderComponentRegistry $registry)
    {
        return view('Website::livewire.admin.header.header-settings-hub', [
            'menuLocations' => $menuService->getAvailableLocations(),
            'menuTree' => $menuService->getMenuTree($this->currentLocation),
            'headerComponents' => $registry->all(),
            'builderSlotNames' => [
                'desktop.topbar' => 'Topbar',
                'desktop.main.left' => 'Desktop · Trái',
                'desktop.main.center' => 'Desktop · Giữa',
                'desktop.main.right' => 'Desktop · Phải',
                'mobile.search' => 'Mobile · Tìm kiếm',
                'mobile.drawer' => 'Mobile · Drawer',
            ],
        ]);
    }

    public function openModal($id, HeaderMenuService $menuService)
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

    public function saveMenuItem(HeaderMenuService $menuService)
    {
        $this->authorizeAdminPermission('website.menu.manage');

        $this->validate([
            'formData.title' => 'required|string|max:100',
            'formData.url' => 'nullable|string',
        ]);

        $menu = $menuService->findOrCreateMenu($this->currentLocation);
        $data = array_merge($this->formData, ['header_menu_id' => $menu->id]);
        $menuService->saveItem($data, $this->editingId);

        $this->isModalOpen = false;
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Cập nhật mục menu thành công!']);
    }

    public function deleteMenuItem($id, HeaderMenuService $menuService)
    {
        $this->authorizeAdminPermission('website.menu.manage');
        $menuService->deleteItem($id);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Đã xóa mục menu thành công!',
        ]);
    }
}
