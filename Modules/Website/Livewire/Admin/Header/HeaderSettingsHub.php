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
    public string $editingTemplate = 'default';
    public string $publishedTemplate = 'default';
    public array $builderSlots = [];
    public array $presentation = [];
    public array $layoutThemes = [];
    public array $accountSettings = [];
    public array $topbarSettings = [];
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
        $this->publishedTemplate = $settingsService->get('website.public_template', 'default') === 'pharma' ? 'pharma' : 'default';
        $this->editingTemplate = $this->publishedTemplate;
        $this->loadTemplateSettings($settingsService, $presentationService);
    }

    public function updatedEditingTemplate(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->editingTemplate = $this->editingTemplate === 'pharma' ? 'pharma' : 'default';
        $this->loadTemplateSettings($settingsService, $presentationService);
    }

    private function settingKey(string $suffix): string
    {
        return $this->editingTemplate === 'pharma' ? 'website.pharma_header.'.$suffix : 'header.'.$suffix;
    }

    private function defaultLayout(): array
    {
        return $this->editingTemplate === 'pharma'
            ? (array) config('website.header.pharma_layout', [])
            : (array) config('website.header.layout', []);
    }

    private function loadTemplateSettings(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $savedLayout = $settingsService->get($this->settingKey('layout'));
        $this->loadBuilderLayout(is_array($savedLayout) ? $savedLayout : $this->defaultLayout());
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        $savedPresentation = $settingsService->get($this->settingKey('presentation'));
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
        $savedThemes = $settingsService->get($this->settingKey('layout_themes'), []);
        $this->layoutThemes = is_array($savedThemes) ? $savedThemes : [];
        $this->selectedTheme = '';
        $this->themeName = '';
        $this->accountSettings = $this->normalizeAccountSettings($settingsService->get('website.pharma_header.account'));
        $this->topbarSettings = $this->normalizeTopbarSettings($settingsService->get('website.pharma_header.topbar'));
        $this->resetErrorBag();
    }

    private function normalizeAccountSettings(mixed $saved): array
    {
        $defaults = ['style' => 'avatar_name', 'show_name' => true, 'show_orders' => true, 'show_admin' => true, 'show_profile' => true, 'show_password' => true];
        $values = is_array($saved) ? $saved : [];
        return [
            'style' => in_array($values['style'] ?? null, ['avatar_name', 'avatar', 'button'], true) ? $values['style'] : $defaults['style'],
            'show_name' => (bool) ($values['show_name'] ?? true),
            'show_orders' => (bool) ($values['show_orders'] ?? true),
            'show_admin' => (bool) ($values['show_admin'] ?? true),
            'show_profile' => (bool) ($values['show_profile'] ?? true),
            'show_password' => (bool) ($values['show_password'] ?? true),
        ];
    }

    public function saveAccountSettings(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->accountSettings = $this->normalizeAccountSettings($this->accountSettings);
        $settingsService->set('website.pharma_header.account', $this->accountSettings, 'header', 'json');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu Account Dropdown Pharma.']]);
    }

    private function normalizeTopbarSettings(mixed $saved): array
    {
        $values = is_array($saved) ? $saved : [];
        return [
            'enabled' => (bool) ($values['enabled'] ?? true),
            'show_desktop' => (bool) ($values['show_desktop'] ?? true),
            'show_mobile' => (bool) ($values['show_mobile'] ?? false),
            'left_text' => mb_substr((string) ($values['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO'), 0, 180),
            'right_text' => mb_substr((string) ($values['right_text'] ?? 'Thông tin không thay thế tư vấn chuyên môn y tế'), 0, 180),
            'show_hotline' => (bool) ($values['show_hotline'] ?? false),
            'show_email' => (bool) ($values['show_email'] ?? false),
        ];
    }

    public function saveTopbarSettings(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->validate([
            'topbarSettings.left_text' => ['nullable', 'string', 'max:180'],
            'topbarSettings.right_text' => ['nullable', 'string', 'max:180'],
        ]);
        $this->topbarSettings = $this->normalizeTopbarSettings($this->topbarSettings);
        $settingsService->set('website.pharma_header.topbar', $this->topbarSettings, 'header', 'json');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu Topbar Pharma.']]);
    }

    public function setTopbarDeviceVisibility(string $visibility): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
            return;
        }

        $this->topbarSettings['show_desktop'] = in_array($visibility, ['both', 'desktop'], true);
        $this->topbarSettings['show_mobile'] = in_array($visibility, ['both', 'mobile'], true);
    }

    public function setComponentVisibility(string $slot, int $index, string $visibility): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->hasItem($slot, $index) || ! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
            return;
        }

        if ($this->editingTemplate === 'pharma' && ($this->builderSlots[$slot][$index]['type'] ?? null) === 'mobile-menu') {
            $visibility = $visibility === 'hidden' ? 'hidden' : 'mobile';
        }
        $this->builderSlots[$slot][$index]['enabled'] = $visibility !== 'hidden';
        $this->builderSlots[$slot][$index]['config']['visibility'] = $visibility;
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

    /**
     * Persist the Pharma builder with device choices supplied by the browser.
     * The old deferred $wire.set(..., false) did not reliably reach the
     * server action and could silently restore the previous saved layout.
     */
    public function savePharmaBuilder(
        array $visibilityOverrides,
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }

        $this->normalizePharmaBuilder();
        foreach ($this->builderSlots as $slot => &$items) {
            foreach ($items as &$item) {
                $type = $item['type'] ?? '';
                if (! array_key_exists($type, $visibilityOverrides)) {
                    continue;
                }
                $visibility = $visibilityOverrides[$type];
                if (! is_string($visibility) || ! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
                    continue;
                }
                if ($type === 'mobile-menu') {
                    $visibility = $visibility === 'hidden' ? 'hidden' : 'mobile';
                }
                $item['enabled'] = $visibility !== 'hidden';
                $item['config'] = is_array($item['config'] ?? null) ? $item['config'] : [];
                $item['config']['visibility'] = $visibility;
            }
            unset($item);
        }
        unset($items);

        // Save the already-normalized layout once. A second normalization inside
        // saveBuilder() can reinterpret hidden Search visibility as a legacy
        // desktop/mobile combination and silently overwrite the user's choice.
        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);
        $key = $this->settingKey('layout');

        $settingsService->updateMany([
            $key => $layout,
            $this->settingKey('presentation') => $presentation,
        ], 'header');

        // Verify actual storage read-back before reporting success.
        $stored = $settingsService->get($key);
        if (! is_array($stored) || $stored !== $layout) {
            $this->addError('builder', 'Không thể xác nhận bố cục đã được lưu. Vui lòng thử lại.');
            return;
        }

        $this->presentation = $presentation;
        $this->loadBuilderLayout($stored);
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu và xác nhận bố cục Header.',
        ]]);
    }

    public function saveBuilder(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);

        $settingsService->updateMany([
            $this->settingKey('layout') => $layout,
            $this->settingKey('presentation') => $presentation,
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
        $this->loadBuilderLayout($this->defaultLayout());
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
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

    /**
     * Pharma has one Search component. Older layouts stored separate desktop and
     * mobile Search entries; fold them into one canonical desktop Search entry.
     * The frontend uses its visibility for both breakpoints.
     */
    private function normalizePharmaBuilder(): void
    {
        foreach ($this->builderSlots['mobile.drawer'] ?? [] as $index => $item) {
            if (($item['type'] ?? null) === 'mobile-menu') {
                $this->builderSlots['mobile.drawer'][$index]['config']['visibility'] = ($item['enabled'] ?? true) ? 'mobile' : 'hidden';
            }
        }

        $search = null;
        $searchSlot = 'desktop.main.right';
        $desktopVisible = false;
        $mobileVisible = false;
        $explicit = null;

        foreach (self::BUILDER_SLOTS as $slot) {
            $kept = [];
            foreach ($this->builderSlots[$slot] ?? [] as $item) {
                if (($item['type'] ?? null) !== 'search') {
                    $kept[] = $item;
                    continue;
                }
                if ($search === null || ($searchSlot === 'desktop.main.right' && $slot !== 'mobile.search')) {
                    $search = $item;
                    if (str_starts_with($slot, 'desktop.main.')) {
                        $searchSlot = $slot;
                    }
                }
                $visibility = $item['config']['visibility'] ?? (($item['enabled'] ?? true) ? (str_starts_with($slot, 'mobile.') ? 'mobile' : 'desktop') : 'hidden');
                if ($slot !== 'mobile.search' && array_key_exists('visibility', $item['config'] ?? [])) {
                    $explicit = $visibility;
                }
                if (($item['enabled'] ?? true) && in_array($visibility, ['desktop', 'both'], true)) {
                    $desktopVisible = true;
                }
                if (($item['enabled'] ?? true) && in_array($visibility, ['mobile', 'both'], true)) {
                    $mobileVisible = true;
                }
            }
            $this->builderSlots[$slot] = array_values($kept);
        }

        if ($search === null) {
            return;
        }

        $visibility = ($explicit === null || ($explicit === 'hidden' && $mobileVisible))
            ? ($desktopVisible && $mobileVisible ? 'both' : ($desktopVisible ? 'desktop' : ($mobileVisible ? 'mobile' : 'hidden')))
            : $explicit;
        $search['enabled'] = $visibility !== 'hidden';
        $search['config'] = is_array($search['config'] ?? null) ? $search['config'] : [];
        $search['config']['visibility'] = $visibility;
        $this->builderSlots[$searchSlot][] = $search;
    }

    public function setPharmaComponentPosition(string $slot, int $index, string $position, HeaderComponentRegistry $registry): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma' || ! $this->hasItem($slot, $index)) {
            return;
        }
        if (($this->builderSlots[$slot][$index]['type'] ?? null) === 'mobile-menu') {
            return;
        }
        if (! in_array($position, ['desktop.main.left', 'desktop.main.center', 'desktop.main.right'], true)) {
            return;
        }
        $this->moveComponent($slot, $index, $position, $registry);
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
        $settingsService->set($this->settingKey('layout_themes'), $this->layoutThemes, 'header', 'json');
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
