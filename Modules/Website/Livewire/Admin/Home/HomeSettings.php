<?php

namespace Modules\Website\Livewire\Admin\Home;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Admin\Home\Concerns\ManagesHomepageLayoutThemes;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\HomepageContentService;
use Modules\Website\Services\HomepageContentWriteService;
use Modules\Website\Services\HomepagePresentationService;
use Modules\Website\Services\HomepageSectionRegistry;

class HomeSettings extends Component
{
    use AuthorizesAdminPermissions, ManagesHomepageLayoutThemes, WithFileUploads;

    public $activeTab = 'layout';
    public string $editingTemplate = 'default';
    public string $publishedTemplate = 'default';
    public array $pharmaLayout = [];
    public array $pharmaSectionOrder = [];
    public array $pharmaCategoryDisplay = [];
    public array $pharmaProductSections = [];
    public ?string $editingPharmaProductSection = null;
    public bool $editingPharmaCategories = false;
    public bool $editingPharmaProductFilters = false;
    public array $pharmaProductFilters = [];
    public string $pharmaTheme = 'clinical';
    public string $pharmaPrimaryColor = '#047857';
    public string $pharmaAccentColor = '#10b981';

    public array $layout = [];
    public array $sectionOrder = [];
    public array $sectionTypes = [];
    public array $presentation = [];

    public $data = [
        'category_ids' => [],
        'featured_ids' => [],
        'trust_badges' => [],
    ];

    public $productSearchQuery = '';
    public $showProductPicker = false;
    public $newArrivalsCount = 10;
    public $bestSellersCount = 8;
    public $blogCount = 3;
    public $newPromoImage;

    public $promoBanner = [
        'show' => true,
        'image' => '',
        'title' => '',
        'sub_title' => '',
        'btn_text' => 'Mua Ngay',
        'link' => '#',
        'details_link' => '',
    ];

    public $newsletter = [
        'show' => true,
        'badge' => 'Tham gia cộng đồng',
        'title' => 'Mở khóa ưu đãi <span class="text-blue-400">10%</span> cho đơn hàng đầu tiên.',
        'description' => 'Đăng ký để nhận tin tức về bộ sưu tập mới, mẹo phối đồ và các ưu đãi độc quyền chỉ dành cho thành viên.',
    ];

    public function mount(
        SettingsService $settings,
        HomepageContentService $homepage,
        HomepageSectionRegistry $registry,
        HomepagePresentationService $presentationService
    ): void {
        $this->publishedTemplate = $settings->get('website.public_template', 'default') === 'pharma' ? 'pharma' : 'default';
        $this->editingTemplate = $this->publishedTemplate;
        $theme = (array) $settings->get('website.pharma_homepage.theme', []);
        $this->pharmaTheme = in_array($theme['preset'] ?? '', ['clinical', 'ocean', 'modern'], true) ? $theme['preset'] : 'clinical';
        $this->pharmaPrimaryColor = $theme['primary'] ?? '#047857';
        $this->pharmaAccentColor = $theme['accent'] ?? '#10b981';
        $this->pharmaLayout = (array) $settings->get('website.pharma_homepage.layout', [
            'hero' => 'all', 'categories' => 'all', 'new_arrivals' => 'all',
        ]);
        $this->pharmaCategoryDisplay = array_merge([
            'eyebrow' => 'Danh mục',
            'title' => 'Khám phá theo nhóm dược phẩm',
            'description' => '',
            'limit' => 8,
            'columns' => 4,
            'card_style' => 'clinical',
        ], (array) $settings->get('website.pharma_homepage.categories', []));
        $this->pharmaProductSections = (array) $settings->get('website.pharma_homepage.product_sections', []);
        $this->pharmaProductSections = array_replace(['new_arrivals' => $this->defaultPharmaProductSection()], $this->pharmaProductSections);
        $this->pharmaProductFilters = array_replace(array_fill_keys(['category', 'supplier', 'product_type', 'circular_group'], true), (array) $settings->get('website.pharma_catalog.filters', []));
        $this->pharmaSectionOrder = (array) $settings->get('website.pharma_homepage.order', [
            'hero', 'categories', 'new_arrivals',
        ]);

        $this->layout = collect($registry->all())
            ->mapWithKeys(fn (array $definition, string $key): array => ['show_'.$key => 'all'])
            ->all();

        $this->layout = array_merge($this->layout, $homepage->visibility());
        $this->sectionOrder = array_map(fn (string $key): string => 'show_'.$key, $homepage->order());
        $this->sectionTypes = $homepage->sectionTypes();
        $this->loadStructuredContent($homepage);
        $this->presentation = $presentationService->resolve($settings->get('homepage.presentation', []));
    }

    private function loadStructuredContent(HomepageContentService $homepage): void
    {
        $this->data['category_ids'] = $homepage->referenceIds('categories', 'category', 'home_category_ids');
        $this->data['featured_ids'] = $homepage->referenceIds('featured', 'product', 'home_featured_ids');
        $this->newArrivalsCount = $homepage->limit('new_arrivals', 'home_new_arrivals_count', 10);
        $this->bestSellersCount = $homepage->limit('best_sellers', 'home_best_sellers_count', 8);
        $this->blogCount = $homepage->limit('blog_highlight', 'home_blog_count', 3);

        $this->promoBanner = array_merge(
            $this->promoBanner,
            $homepage->config('promo_banner', 'home_promo_banner')
        );
        $this->newsletter = array_merge(
            $this->newsletter,
            $homepage->config('newsletter', 'home_newsletter')
        );
        $this->data['trust_badges'] = $homepage->itemConfigs('trust_badges', 'home_trust_badges');
    }

    public function render(HomepageSectionRegistry $registry)
    {
        $allCategories = Category::query()->select('id', 'name')->orderBy('name')->get();
        $pharmaMedicineCategories = Category::query()->ofType('medicine')->active()->sorted()->get(['id', 'name']);

        $searchProducts = [];
        if ($this->showProductPicker) {
            $query = Product::query()->select('id', 'title', 'image', 'regular_price');
            if (! empty($this->productSearchQuery)) {
                $query->where('title', 'like', '%'.$this->productSearchQuery.'%');
            }
            $searchProducts = $query->limit(10)->get();
        }

        $selectedProducts = [];
        if (! empty($this->data['featured_ids'])) {
            $idsStr = implode(',', $this->data['featured_ids']);
            if ($idsStr) {
                $selectedProducts = Product::query()
                    ->whereIn('id', $this->data['featured_ids'])
                    ->orderByRaw("FIELD(id, $idsStr)")
                    ->select('id', 'title', 'image')
                    ->get();
            }
        }

        return view('Website::livewire.admin.home.home-settings-v3', [
            'allCategories' => $allCategories,
            'pharmaMedicineCategories' => $pharmaMedicineCategories,
            'searchProducts' => $searchProducts,
            'selectedProducts' => $selectedProducts,
            'homepageSections' => $registry->all(),
            'sectionCards' => $registry->adminCards($this->sectionOrder, $this->sectionTypes),
        ]);
    }


    public function publishTemplate(string $template, SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        if (! in_array($template, ['default', 'pharma'], true)) {
            return;
        }

        $settings->updateMany(['website.public_template' => $template], 'homepage');
        $this->publishedTemplate = $template;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã xuất bản template '.ucfirst($template).'.']);
    }

    public function selectEditingTemplate(string $template): void
    {
        if (! in_array($template, ['default', 'pharma'], true)) {
            return;
        }

        $this->editingTemplate = $template;
        $this->activeTab = 'layout';
    }

    public function choosePharmaTheme(string $preset): void
    {
        $presets = [
            'clinical' => ['#047857', '#10b981'],
            'ocean' => ['#075985', '#06b6d4'],
            'modern' => ['#1e3a8a', '#6366f1'],
        ];
        if (! isset($presets[$preset])) {
            return;
        }
        $this->pharmaTheme = $preset;
        [$this->pharmaPrimaryColor, $this->pharmaAccentColor] = $presets[$preset];
    }

    public function savePharmaTheme(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $this->validate([
            'pharmaTheme' => 'required|in:clinical,ocean,modern',
            'pharmaPrimaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pharmaAccentColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $settings->updateMany([
            'website.pharma_homepage.theme' => [
                'preset' => $this->pharmaTheme,
                'primary' => $this->pharmaPrimaryColor,
                'accent' => $this->pharmaAccentColor,
            ],
        ], 'homepage');
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu Themes Pharma riêng biệt.']);
    }

    public function savePharmaCategories(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $validated = $this->validate([
            'pharmaCategoryDisplay.eyebrow' => 'required|string|max:60',
            'pharmaCategoryDisplay.title' => 'required|string|max:120',
            'pharmaCategoryDisplay.description' => 'nullable|string|max:300',
            'pharmaCategoryDisplay.limit' => 'required|integer|min:4|max:24',
            'pharmaCategoryDisplay.columns' => 'required|integer|in:2,3,4',
            'pharmaCategoryDisplay.card_style' => 'required|in:clinical,compact',
        ]);
        $settings->updateMany([
            'website.pharma_homepage.categories' => $validated['pharmaCategoryDisplay'],
        ], 'homepage');
        $this->editingPharmaCategories = false;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu thiết lập danh mục Pharma.']);
    }

    public function savePharmaProductFilters(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $filters = [];
        foreach (['category', 'supplier', 'product_type', 'circular_group'] as $key) {
            $filters[$key] = (bool) ($this->pharmaProductFilters[$key] ?? false);
        }
        $settings->updateMany(['website.pharma_catalog.filters' => $filters], 'homepage');
        $this->pharmaProductFilters = $filters;
        $this->editingPharmaProductFilters = false;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu bộ lọc trang sản phẩm Pharma.']);
    }


    private function defaultPharmaProductSection(): array
    {
        return ['eyebrow' => 'Danh mục sản phẩm', 'title' => 'Sản phẩm mới cập nhật', 'category_ids' => [], 'sort' => 'newest', 'period' => 'all', 'limit' => 8];
    }

    public function duplicatePharmaProductSection(string $key): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        if (! isset($this->pharmaProductSections[$key]) || count($this->pharmaProductSections) >= 20) {
            return;
        }
        $newKey = 'products_'.str_replace('-', '', (string) \\Illuminate\\Support\\Str::uuid());
        $this->pharmaProductSections[$newKey] = $this->pharmaProductSections[$key];
        $this->pharmaProductSections[$newKey]['title'] = ($this->pharmaProductSections[$key]['title'] ?? 'Sản phẩm').' (bản sao)';
        $index = array_search($key, $this->pharmaSectionOrder, true);
        array_splice($this->pharmaSectionOrder, $index === false ? count($this->pharmaSectionOrder) : $index + 1, 0, [$newKey]);
        $this->pharmaLayout[$newKey] = $this->pharmaLayout[$key] ?? 'all';
        $this->editingPharmaProductSection = $newKey;
    }

    public function removePharmaProductSection(string $key): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        if ($key === 'new_arrivals' || ! isset($this->pharmaProductSections[$key])) {
            return;
        }
        unset($this->pharmaProductSections[$key], $this->pharmaLayout[$key]);
        $this->pharmaSectionOrder = array_values(array_diff($this->pharmaSectionOrder, [$key]));
        if ($this->editingPharmaProductSection === $key) {
            $this->editingPharmaProductSection = null;
        }
    }

    public function savePharmaProductSections(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $allowedCategories = Category::query()->ofType('medicine')->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $clean = [];
        foreach ($this->pharmaProductSections as $key => $section) {
            if ($key !== 'new_arrivals' && ! preg_match('/^products_[a-f0-9]{32}$/', (string) $key)) {
                continue;
            }
            $title = trim((string) ($section['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 120) {
                $this->addError('pharmaProductSections', 'Tiêu đề section phải từ 1 đến 120 ký tự.');
                return;
            }
            $ids = array_values(array_unique(array_intersect(array_map('intval', (array) ($section['category_ids'] ?? [])), $allowedCategories)));
            $clean[$key] = [
                'eyebrow' => mb_substr(trim((string) ($section['eyebrow'] ?? '')), 0, 60),
                'title' => $title,
                'category_ids' => $ids,
                'sort' => in_array($section['sort'] ?? '', ['newest', 'created', 'name_asc', 'name_desc'], true) ? $section['sort'] : 'newest',
                'period' => in_array((string) ($section['period'] ?? 'all'), ['all', '7', '30', '90'], true) ? (string) $section['period'] : 'all',
                'limit' => in_array((int) ($section['limit'] ?? 8), [4, 8, 12, 16, 20, 24], true) ? (int) $section['limit'] : 8,
            ];
        }
        $this->pharmaProductSections = $clean;
        $this->savePharmaLayout($settings);
        $settings->updateMany(['website.pharma_homepage.product_sections' => $clean], 'homepage');
        $this->editingPharmaProductSection = null;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu các section sản phẩm Pharma.']);
    }

    public function savePharmaLayout(SettingsService $settings): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $allowed = array_merge(['hero', 'categories', 'new_arrivals'], array_keys($this->pharmaProductSections));
        $visibility = [];
        foreach ($allowed as $key) {
            $value = $this->pharmaLayout[$key] ?? 'all';
            $visibility[$key] = in_array($value, ['all', 'desktop', 'mobile', 'none'], true) ? $value : 'all';
        }
        $order = collect($this->pharmaSectionOrder)
            ->filter(fn ($key) => in_array($key, $allowed, true))
            ->unique()->values()->all();
        $order = array_values(array_merge($order, array_diff($allowed, $order)));
        $settings->updateMany([
            'website.pharma_homepage.layout' => $visibility,
            'website.pharma_homepage.order' => $order,
        ], 'homepage');
        $this->pharmaLayout = $visibility;
        $this->pharmaSectionOrder = $order;
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Đã lưu bố cục Pharma độc lập với Default.']);
    }

    public function movePharmaSection(string $key, string $direction): void
    {
        $this->authorizeAdminPermission('website.home.manage');
        $index = array_search($key, $this->pharmaSectionOrder, true);
        if ($index === false || ! in_array($direction, ['up', 'down'], true)) {
            return;
        }
        $target = $index + ($direction === 'up' ? -1 : 1);
        if ($target < 0 || $target >= count($this->pharmaSectionOrder)) {
            return;
        }
        [$this->pharmaSectionOrder[$index], $this->pharmaSectionOrder[$target]] =
            [$this->pharmaSectionOrder[$target], $this->pharmaSectionOrder[$index]];
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['layout', 'data', 'trust_badges'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function addBadge()
    {
        $this->data['trust_badges'][] = [
            'icon' => 'fa-solid fa-check',
            'title' => '',
            'sub_title' => '',
        ];
    }

    public function removeBadge($index)
    {
        unset($this->data['trust_badges'][$index]);
        $this->data['trust_badges'] = array_values($this->data['trust_badges']);
    }

    public function openProductPicker()
    {
        $this->showProductPicker = true;
        $this->productSearchQuery = '';
    }

    public function toggleProduct($id)
    {
        if (in_array($id, $this->data['featured_ids'])) {
            $this->data['featured_ids'] = array_diff($this->data['featured_ids'], [$id]);
        } else {
            $this->data['featured_ids'][] = $id;
        }
        $this->data['featured_ids'] = array_values($this->data['featured_ids']);
    }

    public function reorderSections(array $orderedKeys): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        $allowed = array_keys($this->layout);
        $ordered = collect($orderedKeys)
            ->map(fn ($key): string => (string) $key)
            ->filter(fn (string $key): bool => in_array($key, $allowed, true))
            ->unique()
            ->values();

        $this->sectionOrder = $ordered->merge(array_diff($allowed, $ordered->all()))->values()->all();
    }

    public function moveSectionByDrag(string $draggedKey, string $targetKey): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        if ($draggedKey === $targetKey
            || ! in_array($draggedKey, $this->sectionOrder, true)
            || ! in_array($targetKey, $this->sectionOrder, true)) {
            return;
        }

        $order = array_values(array_diff($this->sectionOrder, [$draggedKey]));
        $targetIndex = array_search($targetKey, $order, true);
        array_splice($order, $targetIndex === false ? count($order) : $targetIndex, 0, [$draggedKey]);
        $this->sectionOrder = $order;
    }

    public function duplicateSection(string $layoutKey, HomepageSectionRegistry $registry): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        $key = $this->sectionKey($layoutKey);
        $definition = $registry->resolve($key, $this->sectionTypes[$key] ?? null);
        if (! (bool) ($definition['duplicatable'] ?? false)) {
            $this->addError('builder', 'Section này không hỗ trợ nhân bản.');
            return;
        }

        $copyKey = $this->nextCopyKey($registry->canonicalKey($key));
        $copyLayoutKey = 'show_'.$copyKey;
        $position = array_search($layoutKey, $this->sectionOrder, true);

        array_splice(
            $this->sectionOrder,
            $position === false ? count($this->sectionOrder) : $position + 1,
            0,
            [$copyLayoutKey]
        );

        $this->layout[$copyLayoutKey] = $this->layout[$layoutKey] ?? 'all';
        $this->sectionTypes[$copyKey] = $definition['type'];
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Đã nhân bản section trong Builder. Bấm Lưu thay đổi để publish.',
        ]);
    }

    public function removeSection(string $layoutKey): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        $key = $this->sectionKey($layoutKey);
        if (str_contains($key, '_copy_')) {
            unset($this->layout[$layoutKey], $this->sectionTypes[$key]);
            $this->sectionOrder = array_values(array_diff($this->sectionOrder, [$layoutKey]));
        } else {
            $this->layout[$layoutKey] = 'none';
        }

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Đã cập nhật Builder. Bấm Lưu thay đổi để publish.',
        ]);
    }

    public function restoreSection(string $layoutKey): void
    {
        $this->authorizeAdminPermission('website.home.manage');

        if (array_key_exists($layoutKey, $this->layout)) {
            $this->layout[$layoutKey] = 'all';
        }
    }

    public function save(HomepageContentWriteService $writer, HomepagePresentationService $presentationService)
    {
        if ($this->editingTemplate === 'pharma') {
            if ($this->activeTab === 'themes') {
                $this->savePharmaTheme(app(SettingsService::class));
                return;
            }
            $this->savePharmaLayout(app(SettingsService::class));
            return;
        }
        $this->authorizeAdminPermission('website.home.manage');

        $this->validate([
            'newPromoImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'newArrivalsCount' => 'required|integer|min:1|max:50',
            'bestSellersCount' => 'required|integer|min:1|max:50',
            'blogCount' => 'required|integer|min:1|max:10',
            'presentation.mode' => 'required|in:basic,advanced',
            'presentation.container' => 'required|in:standard,wide,full',
            'presentation.spacing' => 'required|in:compact,normal,comfortable',
            'presentation.custom.container_width' => 'required|integer|min:960|max:1920',
            'presentation.custom.page_padding' => 'required|integer|min:0|max:64',
            'presentation.custom.section_gap' => 'required|integer|min:16|max:120',
            'presentation.custom.mobile_section_gap' => 'required|integer|min:12|max:96',
        ]);

        $this->presentation = $presentationService->resolve($this->presentation);
        $oldImage = $this->promoBanner['image'] ?? null;
        $newImage = $this->newPromoImage?->store('banners', 'public');
        if ($newImage) {
            $this->promoBanner['image'] = $newImage;
        }

        $values = [];
        foreach ($this->layout as $key => $value) {
            if ($value === true || $value === '1') {
                $value = 'all';
            }
            if ($value === false || $value === '0') {
                $value = 'hidden';
            }

            $values['home_'.$key] = $value;
        }

        $cleanBadges = array_values(array_filter(
            $this->data['trust_badges'] ?? [],
            fn ($item) => ! empty($item['title'])
        ));
        $values += [
            'home_category_ids' => $this->data['category_ids'],
            'home_featured_ids' => $this->data['featured_ids'],
            'home_new_arrivals_count' => $this->newArrivalsCount,
            'home_best_sellers_count' => $this->bestSellersCount,
            'home_blog_count' => $this->blogCount,
            'home_promo_banner' => $this->promoBanner,
            'home_newsletter' => $this->newsletter,
            'home_trust_badges' => $cleanBadges,
            'homepage.presentation' => $this->presentation,
        ];

        try {
            $writer->save($values, $this->sectionOrder, $this->layout, $this->sectionTypes);
        } catch (\Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
                $this->promoBanner['image'] = $oldImage;
            }

            throw $exception;
        }

        if ($newImage && $oldImage && $oldImage !== $newImage) {
            Storage::disk('public')->delete($oldImage);
        }

        $this->newPromoImage = null;

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Đã lưu cấu hình thành công!',
        ]);
    }

    private function sectionKey(string $layoutKey): string
    {
        return str_starts_with($layoutKey, 'show_') ? substr($layoutKey, 5) : $layoutKey;
    }

    private function nextCopyKey(string $canonicalKey): string
    {
        $number = 1;
        $existing = collect(array_keys($this->layout))
            ->map(fn (string $key): string => $this->sectionKey($key))
            ->flip();

        do {
            $candidate = $canonicalKey.'_copy_'.$number++;
        } while ($existing->has($candidate));

        return $candidate;
    }
}
