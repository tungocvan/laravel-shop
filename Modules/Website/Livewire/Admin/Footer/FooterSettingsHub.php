<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\FooterComponentRegistry;
use Modules\Website\Services\FooterPresentationService;

class FooterSettingsHub extends Component
{
    use AuthorizesAdminPermissions;

    public array $builderSlots = [];
    public array $presentation = [];

    private const BUILDER_SLOTS = [
        'desktop.top',
        'desktop.main.brand',
        'desktop.main.columns',
        'desktop.main.extra',
        'desktop.bottom.left',
        'desktop.bottom.right',
        'mobile.main',
        'mobile.bottom',
        'overlay',
    ];

    public function mount(SettingsService $settingsService, FooterPresentationService $presentationService): void
    {
        $savedLayout = $settingsService->get('footer.layout');
        $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.footer.layout', []);

        foreach (self::BUILDER_SLOTS as $slot) {
            $items = data_get($rawLayout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }

        $savedPresentation = $settingsService->get('footer.presentation');
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
    }

    public function toggleComponent(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.footer.manage');

        if (! $this->hasItem($slot, $index)) {
            return;
        }

        $current = (bool) ($this->builderSlots[$slot][$index]['enabled'] ?? true);
        $this->builderSlots[$slot][$index]['enabled'] = ! $current;
    }

    public function moveUp(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.footer.manage');

        if (! in_array($slot, self::BUILDER_SLOTS, true) || $index <= 0 || ! $this->hasItem($slot, $index)) {
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
        $this->authorizeAdminPermission('website.footer.manage');

        if (! in_array($slot, self::BUILDER_SLOTS, true)
            || ! isset($this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1])) {
            return;
        }

        [$this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1]] = [
            $this->builderSlots[$slot][$index + 1],
            $this->builderSlots[$slot][$index],
        ];

        $this->builderSlots[$slot] = array_values($this->builderSlots[$slot]);
    }

    public function moveComponent(string $fromSlot, int $index, string $toSlot, FooterComponentRegistry $registry): void
    {
        $this->moveComponentByDrag($fromSlot, $index, $toSlot, count($this->builderSlots[$toSlot] ?? []), $registry);
    }

    public function moveComponentByDrag(
        string $fromSlot,
        int $fromIndex,
        string $toSlot,
        int $toIndex,
        FooterComponentRegistry $registry
    ): void {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->resetErrorBag('builder');

        if (! in_array($fromSlot, self::BUILDER_SLOTS, true)
            || ! in_array($toSlot, self::BUILDER_SLOTS, true)
            || ! $this->hasItem($fromSlot, $fromIndex)) {
            return;
        }

        $item = $this->builderSlots[$fromSlot][$fromIndex];

        try {
            $registry->resolve((string) ($item['type'] ?? ''), $toSlot);
        } catch (\InvalidArgumentException) {
            $this->addError('builder', 'Component không được phép chuyển vào vùng đã chọn.');

            return;
        }

        $targetCount = count($this->builderSlots[$toSlot] ?? []);
        $toIndex = max(0, min($toIndex, $targetCount));

        array_splice($this->builderSlots[$fromSlot], $fromIndex, 1);
        $this->builderSlots[$fromSlot] = array_values($this->builderSlots[$fromSlot]);

        $toIndex = max(0, min($toIndex, count($this->builderSlots[$toSlot] ?? [])));
        array_splice($this->builderSlots[$toSlot], $toIndex, 0, [$item]);
        $this->builderSlots[$toSlot] = array_values($this->builderSlots[$toSlot]);
    }

    public function saveBuilder(
        SettingsService $settingsService,
        FooterComponentRegistry $registry,
        FooterPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.footer.manage');

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
            'footer.layout' => $layout,
            'footer.presentation' => $presentation,
        ], 'footer');

        $this->presentation = $presentation;

        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu bố cục Footer.',
        ]]);
    }

    public function resetBuilder(FooterPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');

        $layout = (array) config('website.footer.layout', []);

        foreach (self::BUILDER_SLOTS as $slot) {
            $this->builderSlots[$slot] = array_values((array) data_get($layout, $slot, []));
        }

        $this->presentation = $presentationService->resolve((array) config('website.footer.presentation', []));
        $this->resetErrorBag('builder');
    }

    public function render(FooterComponentRegistry $registry, FooterPresentationService $presentationService)
    {
        return view('Website::livewire.admin.footer.footer-settings-hub', [
            'footerComponents' => $registry->all(),
            'previewPresentation' => $presentationService->resolve($this->presentation),
            'builderSlotNames' => [
                'desktop.top' => 'Desktop · Top',
                'desktop.main.brand' => 'Desktop · Brand / Contact',
                'desktop.main.columns' => 'Desktop · Menu Columns',
                'desktop.main.extra' => 'Desktop · Extra',
                'desktop.bottom.left' => 'Desktop · Bottom Left',
                'desktop.bottom.right' => 'Desktop · Bottom Right',
                'mobile.main' => 'Mobile · Main',
                'mobile.bottom' => 'Mobile · Bottom',
                'overlay' => 'Dùng chung · Overlay',
            ],
        ]);
    }

    private function hasItem(string $slot, int $index): bool
    {
        return in_array($slot, self::BUILDER_SLOTS, true) && isset($this->builderSlots[$slot][$index]);
    }
}
