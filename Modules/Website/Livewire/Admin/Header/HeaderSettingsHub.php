<?php

namespace Modules\Website\Livewire\Admin\Header;

use Illuminate\Support\Str;
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
    public array $layoutThemes = [];
    public string $selectedTheme = '';
    public string $themeName = '';

    private const THEME_VERSION = 1;
    private const MAX_THEMES = 20;

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
        $this->loadBuilderLayout($rawLayout);

        $savedPresentation = $settingsService->get('header.presentation');
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);

        $savedThemes = $settingsService->get('header.layout_themes', []);
        $this->layoutThemes = is_array($savedThemes) ? $savedThemes : [];
    }

    public function toggleComponent(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->hasItem($slot, $index)) {
            return;
        }

        $this->builderSlots[$slot][$index]['enabled'] = ! (bool) ($this->builderSlots[$slot][$index]['enabled'] ?? true);
    }

    public function moveUp(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($index <= 0 || ! $this->hasItem($slot, $index)) {
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
            || ! $this->hasItem($fromSlot, $fromIndex)) {
            return;
        }

        $item = $this->builderSlots[$fromSlot][$fromIndex];
        try {
            $registry->resolve((string) ($item['type'] ?? ''), $toSlot);
        } catch (\InvalidArgumentException) {
            $this->addError('builder', 'Component không được phép chuyển vào vị trí đã chọn.');
            return;
        }

        array_splice($this->builderSlots[$fromSlot], $fromIndex, 1);
        $this->builderSlots[$fromSlot] = array_values($this->builderSlots[$fromSlot]);
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
        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);

        $settingsService->updateMany([
            'header.layout' => $layout,
            'header.presentation' => $presentation,
        ], 'header');

        $this->presentation = $presentation;
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu bố cục Header.',
        ]]);
    }

    public function resetBuilder(HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->loadBuilderLayout((array) config('website.header.layout', []));
        $this->presentation = $presentationService->resolve((array) config('website.header.presentation', []));
        $this->selectedTheme = '';
        $this->resetErrorBag(['builder', 'theme']);
    }

    public function saveTheme(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validateThemeName();
        if (count($this->layoutThemes) >= self::MAX_THEMES) {
            $this->addError('theme', 'Chỉ được lưu tối đa '.self::MAX_THEMES.' Header themes.');
            return;
        }

        $baseSlug = Str::slug($this->themeName) ?: 'header-theme';
        $slug = $baseSlug;
        $suffix = 2;
        while (isset($this->layoutThemes[$slug])) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $this->layoutThemes[$slug] = $this->themeSnapshot($this->themeName, $registry, $presentationService);
        $this->selectedTheme = $slug;
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã lưu Header theme mới.');
    }

    public function applyTheme(HeaderComponentRegistry $registry, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $this->loadBuilderLayout($this->safeLayout(is_array($theme['layout'] ?? null) ? $theme['layout'] : [], $registry));
        $this->presentation = $presentationService->resolve(is_array($theme['presentation'] ?? null) ? $theme['presentation'] : null);
        $this->themeName = (string) ($theme['name'] ?? '');
        $this->resetErrorBag('theme');
        $this->dispatchThemeToast('Đã nạp theme vào Builder. Kiểm tra Preview rồi bấm Lưu bố cục để publish.');
    }

    public function updateTheme(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $name = trim($this->themeName) !== '' ? trim($this->themeName) : (string) ($theme['name'] ?? 'Header Theme');
        $this->themeName = $name;
        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme] = $this->themeSnapshot($name, $registry, $presentationService);
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã cập nhật Header theme.');
    }

    public function renameTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->selectedThemeData() === null) {
            return;
        }

        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme]['name'] = trim($this->themeName);
        $this->layoutThemes[$this->selectedTheme]['updated_at'] = now()->toIso8601String();
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã đổi tên Header theme.');
    }

    public function deleteTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->selectedTheme === '' || ! isset($this->layoutThemes[$this->selectedTheme])) {
            $this->addError('theme', 'Hãy chọn Header theme cần xóa.');
            return;
        }

        unset($this->layoutThemes[$this->selectedTheme]);
        $this->selectedTheme = '';
        $this->themeName = '';
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã xóa Header theme.');
    }

    public function selectTheme(string $slug): void
    {
        if (! isset($this->layoutThemes[$slug])) {
            return;
        }

        $this->selectedTheme = $slug;
        $this->themeName = (string) ($this->layoutThemes[$slug]['name'] ?? '');
        $this->resetErrorBag('theme');
    }

    public function render(HeaderComponentRegistry $registry, HeaderPresentationService $presentationService)
    {
        return view('Website::livewire.admin.header.header-settings-hub', [
            'headerComponents' => $registry->all(),
            'previewPresentation' => $presentationService->resolve($this->presentation),
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

    private function themeSnapshot(string $name, HeaderComponentRegistry $registry, HeaderPresentationService $presentationService): array
    {
        return [
            'version' => self::THEME_VERSION,
            'name' => trim($name),
            'layout' => $this->safeLayout($this->builderSlots, $registry),
            'presentation' => $presentationService->resolve($this->presentation),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function safeLayout(array $source, HeaderComponentRegistry $registry): array
    {
        $layout = [];
        foreach (self::BUILDER_SLOTS as $slot) {
            $items = array_key_exists($slot, $source) ? $source[$slot] : data_get($source, $slot, []);
            $clean = [];
            foreach (is_array($items) ? $items : [] as $item) {
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
        return $layout;
    }

    private function loadBuilderLayout(array $layout): void
    {
        foreach (self::BUILDER_SLOTS as $slot) {
            $items = array_key_exists($slot, $layout) ? $layout[$slot] : data_get($layout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }
    }

    private function selectedThemeData(): ?array
    {
        if ($this->selectedTheme === '' || ! is_array($this->layoutThemes[$this->selectedTheme] ?? null)) {
            $this->addError('theme', 'Hãy chọn Header theme trước.');
            return null;
        }
        return $this->layoutThemes[$this->selectedTheme];
    }

    private function validateThemeName(): void
    {
        $this->themeName = trim($this->themeName);
        $this->validate(['themeName' => ['required', 'string', 'min:2', 'max:60']], [], ['themeName' => 'tên Header theme']);
    }

    private function persistThemes(SettingsService $settingsService): void
    {
        $settingsService->set('header.layout_themes', $this->layoutThemes, 'header', 'json');
        $this->resetErrorBag('theme');
    }

    private function dispatchThemeToast(string $message): void
    {
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => $message,
        ]]);
    }

    private function hasItem(string $slot, int $index): bool
    {
        return in_array($slot, self::BUILDER_SLOTS, true) && isset($this->builderSlots[$slot][$index]);
    }
}
