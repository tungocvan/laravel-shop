<?php

namespace Modules\Website\Livewire\Admin\Header;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderPresentationService;

class HeaderSettingsHub extends Component
{
    use AuthorizesAdminPermissions;

    public $activeTab = 'general';

    public array $builderSlots = [];
    public array $presentation = [];

    private const BUILDER_SLOTS = [
        'desktop.topbar',
        'desktop.main.left',
        'desktop.main.center',
        'desktop.main.right',
        'mobile.search',
        'mobile.drawer',
    ];

    public function mount(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $savedLayout = $settingsService->get('header.layout');
        $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.header.layout', []);

        foreach (self::BUILDER_SLOTS as $slot) {
            $items = data_get($rawLayout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }

        $savedPresentation = $settingsService->get('header.presentation');
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
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
        $this->moveComponentByDrag($fromSlot, $index, $toSlot, count($this->builderSlots[$toSlot] ?? []), $registry);
    }

    public function moveComponentByDrag(
        string $fromSlot,
        int $fromIndex,
        string $toSlot,
        int $toIndex,
        HeaderComponentRegistry $registry
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->resetErrorBag('builder');

        if (! in_array($fromSlot, self::BUILDER_SLOTS, true)
            || ! in_array($toSlot, self::BUILDER_SLOTS, true)
            || ! isset($this->builderSlots[$fromSlot][$fromIndex])) {
            return;
        }

        $item = $this->builderSlots[$fromSlot][$fromIndex];

        try {
            $registry->resolve((string) ($item['type'] ?? ''), $toSlot);
        } catch (\InvalidArgumentException) {
            $this->addError('builder', 'Component không được phép chuyển vào vị trí đã chọn.');

            return;
        }

        $targetCount = count($this->builderSlots[$toSlot] ?? []);
        $toIndex = max(0, min($toIndex, $targetCount));

        array_splice($this->builderSlots[$fromSlot], $fromIndex, 1);
        $this->builderSlots[$fromSlot] = array_values($this->builderSlots[$fromSlot]);

        if ($fromSlot === $toSlot && $fromIndex < $toIndex) {
            $toIndex--;
        }

        $toIndex = max(0, min($toIndex, count($this->builderSlots[$toSlot] ?? [])));
        array_splice($this->builderSlots[$toSlot], $toIndex, 0, [$item]);
        $this->builderSlots[$toSlot] = array_values($this->builderSlots[$toSlot]);
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

    public function render(HeaderComponentRegistry $registry)
    {
        return view('Website::livewire.admin.header.header-settings-hub', [
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
}
