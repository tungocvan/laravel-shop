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
        $this->layout = collect($registry->all())
            ->mapWithKeys(fn (array $definition, string $key): array => ['show_'.$key => 'all'])
            ->all();

        $this->loadSettings($settings);
        $this->layout = array_merge($this->layout, $homepage->visibility());
        $this->sectionOrder = array_map(fn (string $key): string => 'show_'.$key, $homepage->order());
        $this->sectionTypes = $homepage->sectionTypes();
        $this->presentation = $presentationService->resolve($settings->get('homepage.presentation', []));
    }

    public function loadSettings(SettingsService $settings): void
    {
        foreach ($this->layout as $key => $default) {
            $value = $settings->get('home_'.$key);
            $this->layout[$key] = $value ?? 'all';
        }

        $this->data['category_ids'] = (array) $settings->get('home_category_ids', []);
        $this->data['featured_ids'] = (array) $settings->get('home_featured_ids', []);
        $this->newArrivalsCount = (int) $settings->get('home_new_arrivals_count', 10);
        $this->bestSellersCount = (int) $settings->get('home_best_sellers_count', 8);
        $this->blogCount = (int) $settings->get('home_blog_count', 3);

        $promoSettings = $settings->get('home_promo_banner', []);
        if (is_array($promoSettings)) {
            $this->promoBanner = array_merge($this->promoBanner, $promoSettings);
        }

        $newsletterSettings = $settings->get('home_newsletter', []);
        if (is_array($newsletterSettings)) {
            $this->newsletter = array_merge($this->newsletter, $newsletterSettings);
        }

        $this->data['trust_badges'] = (array) $settings->get('home_trust_badges', []);
    }

    public function render(HomepageSectionRegistry $registry)
    {
        $allCategories = Category::query()->select('id', 'name')->orderBy('name')->get();

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
            'searchProducts' => $searchProducts,
            'selectedProducts' => $selectedProducts,
            'homepageSections' => $registry->all(),
            'sectionCards' => $registry->adminCards($this->sectionOrder, $this->sectionTypes),
        ]);
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
