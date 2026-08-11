<?php

namespace Modules\Website\Livewire\Admin\Home;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Models\Setting;

class HomeSettings extends Component
{
    use WithFileUploads, AuthorizesAdminPermissions;

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

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        foreach ($this->layout as $key => $default) {
            $value = Setting::where('key', 'home_' . $key)->value('value');
            $this->layout[$key] = $value ?? 'all';
        }

        $catIds = Setting::where('key', 'home_category_ids')->value('value');
        $this->data['category_ids'] = $catIds ? json_decode($catIds, true) : [];

        $featIds = Setting::where('key', 'home_featured_ids')->value('value');
        $this->data['featured_ids'] = $featIds ? json_decode($featIds, true) : [];

        $this->newArrivalsCount = (int) (Setting::where('key', 'home_new_arrivals_count')->value('value') ?? 10);
        $this->bestSellersCount = (int) (Setting::where('key', 'home_best_sellers_count')->value('value') ?? 8);
        $this->blogCount = (int) (Setting::where('key', 'home_blog_count')->value('value') ?? 3);

        $promoSettings = Setting::where('key', 'home_promo_banner')->value('value');
        if ($promoSettings) {
            $this->promoBanner = array_merge($this->promoBanner, json_decode($promoSettings, true));
        }

        $newsletterSettings = Setting::where('key', 'home_newsletter')->value('value');
        if ($newsletterSettings) {
            $this->newsletter = array_merge($this->newsletter, json_decode($newsletterSettings, true));
        }

        $badgesJson = Setting::where('key', 'home_trust_badges')->value('value');
        $this->data['trust_badges'] = $badgesJson ? json_decode($badgesJson, true) : [];
    }

    public function render()
    {
        $allCategories = DB::table('categories')->select('id', 'name')->get();

        $searchProducts = [];
        if ($this->showProductPicker) {
            $query = DB::table('wp_products')->select('id', 'title', 'image', 'regular_price');
            if (! empty($this->productSearchQuery)) {
                $query->where('title', 'like', '%' . $this->productSearchQuery . '%');
            }
            $searchProducts = $query->limit(10)->get();
        }

        $selectedProducts = [];
        if (! empty($this->data['featured_ids'])) {
            $idsStr = implode(',', $this->data['featured_ids']);
            if ($idsStr) {
                $selectedProducts = DB::table('wp_products')
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

    public function save()
    {
        $this->authorizeAdminPermission('website.home.manage');

        if ($this->newPromoImage) {
            $this->validate([
                'newPromoImage' => 'image|max:3072',
            ]);

            $path = $this->newPromoImage->store('banners', 'public');
            $this->promoBanner['image'] = $path;
            $this->newPromoImage = null;
        }

        foreach ($this->layout as $key => $value) {
            if ($value === true || $value === '1') {
                $value = 'all';
            }
            if ($value === false || $value === '0') {
                $value = 'hidden';
            }

            Setting::updateOrCreate(
                ['key' => 'home_' . $key],
                ['value' => $value, 'group_name' => 'homepage']
            );
        }

        Setting::updateOrCreate(['key' => 'home_category_ids'], ['value' => json_encode($this->data['category_ids'])]);
        Setting::updateOrCreate(['key' => 'home_featured_ids'], ['value' => json_encode($this->data['featured_ids'])]);
        Setting::updateOrCreate(['key' => 'home_new_arrivals_count'], ['value' => $this->newArrivalsCount]);
        Setting::updateOrCreate(['key' => 'home_best_sellers_count'], ['value' => $this->bestSellersCount]);
        Setting::updateOrCreate(['key' => 'home_blog_count'], ['value' => $this->blogCount]);
        Setting::updateOrCreate(['key' => 'home_promo_banner'], ['value' => json_encode($this->promoBanner)]);
        Setting::updateOrCreate(['key' => 'home_newsletter'], ['value' => json_encode($this->newsletter)]);

        if (isset($this->data['trust_badges']) && is_array($this->data['trust_badges'])) {
            $cleanBadges = array_filter($this->data['trust_badges'], fn ($item) => ! empty($item['title']));
            Setting::updateOrCreate(
                ['key' => 'home_trust_badges'],
                ['value' => json_encode(array_values($cleanBadges))]
            );
        }

        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Đã lưu cấu hình thành công!',
        ]);
    }
}
