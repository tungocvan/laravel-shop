<?php

namespace Modules\Website\Livewire\Admin\Home;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\SettingsService;

class HomeSettings extends Component
{
    use AuthorizesAdminPermissions, WithFileUploads;

    public $activeTab = 'layout';

    public $layout = [
        'show_hero' => 'all',
        'show_categories' => 'all',
        'show_flash_sale' => 'all',
        'show_featured' => 'all',
        'show_new_arrivals' => 'all',
        'show_best_sellers' => 'all',
        'show_blog_highlight' => 'all',
        'show_promo_banner' => 'all',
        'show_trust_badges' => 'all',
        'show_newsletter' => 'all',
    ];

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

    public function mount(SettingsService $settings)
    {
        $this->loadSettings($settings);
    }

    public function loadSettings(SettingsService $settings)
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

    public function render()
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

        return view('Website::livewire.admin.home.home-settings', [
            'allCategories' => $allCategories,
            'searchProducts' => $searchProducts,
            'selectedProducts' => $selectedProducts,
        ]);
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
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

    public function save(SettingsService $settings)
    {
        $this->authorizeAdminPermission('website.home.manage');

        $this->validate([
            'newPromoImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'newArrivalsCount' => 'required|integer|min:1|max:50',
            'bestSellersCount' => 'required|integer|min:1|max:50',
            'blogCount' => 'required|integer|min:1|max:10',
        ]);

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
        ];

        try {
            $settings->updateMany($values, 'homepage');
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
}
