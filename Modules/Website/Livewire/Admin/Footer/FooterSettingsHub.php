<?php

namespace Modules\Website\Livewire\Admin\Footer;

use Illuminate\Support\Str;
use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\FooterComponentRegistry;
use Modules\Website\Services\FooterPresentationService;

class FooterSettingsHub extends Component
{
    use AuthorizesAdminPermissions;

    public string $editingTemplate = 'default';
    public string $publishedTemplate = 'default';
    public array $pharmaContent = [];
    public array $builderSlots = [];
    public array $presentation = [];
    public array $layoutThemes = [];
    public string $selectedTheme = '';
    public string $themeName = '';

    private const THEME_VERSION = 1;
    private const MAX_THEMES = 20;

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
        $this->publishedTemplate = $settingsService->get('website.public_template', 'default') === 'pharma' ? 'pharma' : 'default';
        $this->editingTemplate = $this->publishedTemplate;
        $this->loadPharmaContent($settingsService);
        $savedLayout = $settingsService->get($this->settingKey('layout'));
        $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.footer.layout', []);
        $this->loadBuilderLayout($rawLayout);

        $savedPresentation = $settingsService->get($this->settingKey('presentation'));
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);

        $savedThemes = $settingsService->get($this->settingKey('layout_themes'), []);
        $this->layoutThemes = is_array($savedThemes) ? $savedThemes : [];
    }

    private function loadPharmaContent(SettingsService $settingsService): void
    {
        $saved = $settingsService->get('website.pharma_footer.content', []);
        $saved = is_array($saved) ? $saved : [];
        $defaults = [
            'description' => 'Cung cấp thông tin sản phẩm và danh mục dược phẩm được công bố để hỗ trợ tra cứu, tìm hiểu và tham khảo.',
            'links_title' => 'Khám phá',
            'links' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Danh mục dược phẩm', 'url' => '/product'],
            ],
            'notice_title' => 'Lưu ý y khoa',
            'notice' => 'Thông tin trên website mang tính tham khảo, không thay thế chẩn đoán, chỉ định hoặc tư vấn của nhân viên y tế. Sử dụng thuốc theo hướng dẫn của người có chuyên môn.',
            'copyright' => 'Bảo lưu quyền.',
            'background' => '#020617',
            'foreground' => '#cbd5e1',
            'heading' => '#ffffff',
        ];
        $this->pharmaContent = array_replace($defaults, $saved);
        $this->pharmaContent['links'] = is_array($saved['links'] ?? null) ? array_values($saved['links']) : $defaults['links'];
    }

    public function updatedEditingTemplate(): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->editingTemplate = $this->editingTemplate === 'pharma' ? 'pharma' : 'default';
        $settings = app(SettingsService::class);
        $presentationService = app(FooterPresentationService::class);
        $layout = $settings->get($this->settingKey('layout'));
        $this->loadBuilderLayout(is_array($layout) ? $layout : (array) config('website.footer.layout', []));
        $savedPresentation = $settings->get($this->settingKey('presentation'));
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
        $themes = $settings->get($this->settingKey('layout_themes'), []);
        $this->layoutThemes = is_array($themes) ? $themes : [];
        $this->selectedTheme = '';
        $this->themeName = '';
    }

    private function settingKey(string $suffix): string
    {
        return $this->editingTemplate === 'pharma' ? 'website.pharma_footer.'.$suffix : 'footer.'.$suffix;
    }

    public function addPharmaLink(): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        if (count($this->pharmaContent['links'] ?? []) < 12) {
            $this->pharmaContent['links'][] = ['label' => '', 'url' => ''];
        }
    }

    public function removePharmaLink(int $index): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        unset($this->pharmaContent['links'][$index]);
        $this->pharmaContent['links'] = array_values($this->pharmaContent['links']);
    }

    public function savePharmaContent(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->validate([
            'pharmaContent.description' => ['nullable', 'string', 'max:1000'],
            'pharmaContent.links_title' => ['required', 'string', 'max:100'],
            'pharmaContent.links' => ['array', 'max:12'],
            'pharmaContent.links.*.label' => ['nullable', 'string', 'max:100'],
            'pharmaContent.links.*.url' => ['nullable', 'string', 'max:500'],
            'pharmaContent.notice_title' => ['required', 'string', 'max:100'],
            'pharmaContent.notice' => ['required', 'string', 'max:1500'],
            'pharmaContent.copyright' => ['nullable', 'string', 'max:255'],
            'pharmaContent.background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pharmaContent.foreground' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pharmaContent.heading' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $content = $this->pharmaContent;
        $content['links'] = collect($content['links'])->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))->map(fn ($link) => [
            'label' => trim((string) $link['label']),
            'url' => trim((string) $link['url']),
        ])->values()->all();
        $settingsService->set('website.pharma_footer.content', $content, 'footer', 'json');
        $this->pharmaContent = $content;
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu Footer Pharma.']]);
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

        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);

        $settingsService->updateMany([
            $this->settingKey('layout') => $layout,
            $this->settingKey('presentation') => $presentation,
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
        $this->loadBuilderLayout((array) config('website.footer.layout', []));
        $this->presentation = $presentationService->resolve((array) config('website.footer.presentation', []));
        $this->selectedTheme = '';
        $this->resetErrorBag(['builder', 'theme']);
    }

    public function saveTheme(
        SettingsService $settingsService,
        FooterComponentRegistry $registry,
        FooterPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.footer.manage');
        $this->validateThemeName();

        if (count($this->layoutThemes) >= self::MAX_THEMES) {
            $this->addError('theme', 'Chỉ được lưu tối đa '.self::MAX_THEMES.' Footer themes.');
            return;
        }

        $baseSlug = Str::slug($this->themeName) ?: 'footer-theme';
        $slug = $baseSlug;
        $suffix = 2;
        while (isset($this->layoutThemes[$slug])) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $this->layoutThemes[$slug] = $this->themeSnapshot($this->themeName, $registry, $presentationService);
        $this->selectedTheme = $slug;
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã lưu Footer theme mới.');
    }

    public function applyTheme(
        FooterComponentRegistry $registry,
        FooterPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.footer.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $layout = is_array($theme['layout'] ?? null) ? $theme['layout'] : [];
        $safeLayout = $this->safeLayout($layout, $registry);
        $this->loadBuilderLayout($safeLayout);
        $this->presentation = $presentationService->resolve(is_array($theme['presentation'] ?? null) ? $theme['presentation'] : null);
        $this->themeName = (string) ($theme['name'] ?? '');
        $this->resetErrorBag('theme');
        $this->dispatchThemeToast('Đã nạp theme vào Builder. Kiểm tra Preview rồi bấm Lưu bố cục để publish.');
    }

    public function updateTheme(
        SettingsService $settingsService,
        FooterComponentRegistry $registry,
        FooterPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.footer.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $name = trim($this->themeName) !== '' ? trim($this->themeName) : (string) ($theme['name'] ?? 'Footer Theme');
        $this->themeName = $name;
        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme] = $this->themeSnapshot($name, $registry, $presentationService);
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã cập nhật Footer theme.');
    }

    public function renameTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme]['name'] = trim($this->themeName);
        $this->layoutThemes[$this->selectedTheme]['updated_at'] = now()->toIso8601String();
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã đổi tên Footer theme.');
    }

    public function deleteTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.footer.manage');
        if ($this->selectedTheme === '' || ! isset($this->layoutThemes[$this->selectedTheme])) {
            $this->addError('theme', 'Hãy chọn Footer theme cần xóa.');
            return;
        }

        unset($this->layoutThemes[$this->selectedTheme]);
        $this->selectedTheme = '';
        $this->themeName = '';
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã xóa Footer theme.');
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

    private function themeSnapshot(
        string $name,
        FooterComponentRegistry $registry,
        FooterPresentationService $presentationService
    ): array {
        return [
            'version' => self::THEME_VERSION,
            'name' => trim($name),
            'layout' => $this->safeLayout($this->builderSlots, $registry),
            'presentation' => $presentationService->resolve($this->presentation),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function safeLayout(array $source, FooterComponentRegistry $registry): array
    {
        $layout = [];

        foreach (self::BUILDER_SLOTS as $slot) {
            $items = array_key_exists($slot, $source)
                ? $source[$slot]
                : data_get($source, $slot, []);
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
            $items = array_key_exists($slot, $layout)
                ? $layout[$slot]
                : data_get($layout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }
    }

    private function selectedThemeData(): ?array
    {
        if ($this->selectedTheme === '' || ! is_array($this->layoutThemes[$this->selectedTheme] ?? null)) {
            $this->addError('theme', 'Hãy chọn Footer theme trước.');
            return null;
        }

        return $this->layoutThemes[$this->selectedTheme];
    }

    private function validateThemeName(): void
    {
        $this->themeName = trim($this->themeName);
        $this->validate([
            'themeName' => ['required', 'string', 'min:2', 'max:60'],
        ], [], [
            'themeName' => 'tên Footer theme',
        ]);
    }

    private function persistThemes(SettingsService $settingsService): void
    {
        $settingsService->set($this->settingKey('layout_themes'), $this->layoutThemes, 'footer', 'json');
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
