<?php

namespace Modules\Admin\Livewire\Home;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Setting;
use Modules\Website\Models\Product;
use Modules\Website\Models\Category;
use Livewire\WithFileUploads;

class HomeSettings extends Component
{
    use WithFileUploads;

    // 1. PROPERTIES
    public $activeTab = 'layout'; // layout, data, trust_badges

    // Cấu hình hiển thị (Bật/Tắt các khối)
    // Mặc định là 'all' để hiển thị tất cả nếu chưa có cấu hình
    public $layout = [
        'show_hero'         => 'all',
        'show_categories'   => 'all',
        'show_flash_sale'   => 'all',
        'show_featured'     => 'all',
        'show_new_arrivals' => 'all',
        'show_best_sellers' => 'all',
        'show_blog_highlight' => 'all',
        'show_promo_banner' => 'all',
        'show_trust_badges' => 'all',
        'show_newsletter'   => 'all',
    ];

    // Dữ liệu chính (Lưu tất cả vào đây để wire:model cho gọn)
    public $data = [
        'category_ids' => [], // Mảng ID danh mục
        'featured_ids' => [], // Mảng ID sản phẩm nổi bật
        'trust_badges' => [], // Mảng Repeater: [['icon' => '', 'title' => '', ...]]
    ];

    // Search & Picker States
    public $productSearchQuery = '';
    public $showProductPicker = false;

    // Các cấu hình số lượng
    public $newArrivalsCount = 10;
    public $bestSellersCount = 8;
    public $blogCount = 3;

    // Upload hình ảnh
    public $newPromoImage;

    // Cấu hình Promo Banner
    public $promoBanner = [
        'show' => true,
        'image' => '',
        'title' => '',
        'sub_title' => '',
        'btn_text' => 'Mua Ngay',
        'link' => '#',
        'details_link' => '',
    ];

    // Cấu hình Newsletter
    public $newsletter = [
        'show' => true,
        'badge' => 'Tham gia cộng đồng',
        'title' => 'Mở khóa ưu đãi <span class="text-blue-400">10%</span> cho đơn hàng đầu tiên.',
        'description' => 'Đăng ký để nhận tin tức về bộ sưu tập mới, mẹo phối đồ và các ưu đãi độc quyền chỉ dành cho thành viên.',
    ];

    // 2. LIFECYCLE HOOKS
    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        // 2.1. LOAD LAYOUT SETTINGS (Từng key lẻ: home_show_hero...)
        foreach ($this->layout as $key => $default) {
            // Lấy giá trị từ DB, nếu không có thì dùng mặc định 'all'
            $value = Setting::where('key', 'home_' . $key)->value('value');
            $this->layout[$key] = $value ?? 'all';
        }

        // 2.2. LOAD DATA IDs (JSON -> Array)
        $catIds = Setting::where('key', 'home_category_ids')->value('value');
        $this->data['category_ids'] = $catIds ? json_decode($catIds, true) : [];

        $featIds = Setting::where('key', 'home_featured_ids')->value('value');
        $this->data['featured_ids'] = $featIds ? json_decode($featIds, true) : [];

        // 2.3. LOAD COUNTS
        $this->newArrivalsCount = (int)(Setting::where('key', 'home_new_arrivals_count')->value('value') ?? 10);
        $this->bestSellersCount = (int)(Setting::where('key', 'home_best_sellers_count')->value('value') ?? 8);
        $this->blogCount = (int)(Setting::where('key', 'home_blog_count')->value('value') ?? 3);

        // 2.4. LOAD PROMO BANNER CONFIG
        $promoSettings = Setting::where('key', 'home_promo_banner')->value('value');
        if ($promoSettings) {
            $this->promoBanner = array_merge($this->promoBanner, json_decode($promoSettings, true));
        }

        // 2.5. LOAD NEWSLETTER CONFIG
        $newsletterSettings = Setting::where('key', 'home_newsletter')->value('value');
        if ($newsletterSettings) {
            $this->newsletter = array_merge($this->newsletter, json_decode($newsletterSettings, true));
        }

        // 2.6. LOAD TRUST BADGES
        $badgesJson = Setting::where('key', 'home_trust_badges')->value('value');
        $this->data['trust_badges'] = $badgesJson ? json_decode($badgesJson, true) : [];
    }

    public function render()
    {
        // 1. Lấy danh mục
        $allCategories = DB::table('categories')->select('id', 'name')->get();

        // 2. Lấy danh sách sản phẩm tìm kiếm (cho Modal Picker)
        $searchProducts = [];
        if ($this->showProductPicker) {
            $query = DB::table('wp_products')->select('id', 'title', 'image', 'regular_price');
            if (!empty($this->productSearchQuery)) {
                $query->where('title', 'like', '%' . $this->productSearchQuery . '%');
            }
            $searchProducts = $query->limit(10)->get();
        }

        // 3. Lấy danh sách sản phẩm ĐÃ CHỌN (để hiển thị preview)
        $selectedProducts = [];
        if (!empty($this->data['featured_ids'])) {
            $idsStr = implode(',', $this->data['featured_ids']);
            if($idsStr) {
                $selectedProducts = DB::table('wp_products')
                    ->whereIn('id', $this->data['featured_ids'])
                    ->orderByRaw("FIELD(id, $idsStr)")
                    ->select('id', 'title', 'image')
                    ->get();
            }
        }

        return view('Admin::livewire.home.home-settings', [
            'allCategories'    => $allCategories,
            'searchProducts'   => $searchProducts,
            'selectedProducts' => $selectedProducts
        ]);
    }

    // 3. ACTION METHODS

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    // --- TRUST BADGES REPEATER ---

    public function addBadge()
    {
        $this->data['trust_badges'][] = [
            'icon'      => 'fa-solid fa-check',
            'title'     => '',
            'sub_title' => ''
        ];
    }

    public function removeBadge($index)
    {
        unset($this->data['trust_badges'][$index]);
        // Re-index mảng để tránh lỗi khi encode JSON
        $this->data['trust_badges'] = array_values($this->data['trust_badges']);
    }

    // --- PRODUCT PICKER ---

    public function openProductPicker()
    {
        $this->showProductPicker = true;
        $this->productSearchQuery = '';
    }

    public function toggleProduct($id)
    {
        if (in_array($id, $this->data['featured_ids'])) {
            // Remove
            $this->data['featured_ids'] = array_diff($this->data['featured_ids'], [$id]);
        } else {
            // Add
            $this->data['featured_ids'][] = $id;
        }
        // Re-index
        $this->data['featured_ids'] = array_values($this->data['featured_ids']);
    }

    // --- SAVE DATA (CORE LOGIC) ---

    public function save()
    {
        // --- XỬ LÝ UPLOAD ẢNH PROMO BANNER ---
        if ($this->newPromoImage) {
            $this->validate([
                'newPromoImage' => 'image|max:3072', // Max 3MB
            ]);

            $path = $this->newPromoImage->store('banners', 'public');
            $this->promoBanner['image'] = $path;
            $this->newPromoImage = null;
        }

        // 1. Lưu cấu hình Layout (Show/Hide) - Lưu từng key lẻ
        foreach ($this->layout as $key => $value) {
            // Chuyển đổi Boolean sang String nếu View Admin dùng Checkbox (True/False)
            if ($value === true || $value === '1') $value = 'all';
            if ($value === false || $value === '0') $value = 'hidden';

            Setting::updateOrCreate(
                ['key' => 'home_' . $key],
                ['value' => $value, 'group_name' => 'homepage']
            );
        }

        // 2. Lưu Data IDs (Encode Array -> JSON String)
        Setting::updateOrCreate(
            ['key' => 'home_category_ids'],
            ['value' => json_encode($this->data['category_ids'])]
        );

        Setting::updateOrCreate(
            ['key' => 'home_featured_ids'],
            ['value' => json_encode($this->data['featured_ids'])]
        );

        // 3. LƯU CÁC CONFIG KHÁC
        Setting::updateOrCreate(['key' => 'home_new_arrivals_count'], ['value' => $this->newArrivalsCount]);
        Setting::updateOrCreate(['key' => 'home_best_sellers_count'], ['value' => $this->bestSellersCount]);
        Setting::updateOrCreate(['key' => 'home_blog_count'], ['value' => $this->blogCount]);

        Setting::updateOrCreate(['key' => 'home_promo_banner'], ['value' => json_encode($this->promoBanner)]);
        Setting::updateOrCreate(['key' => 'home_newsletter'], ['value' => json_encode($this->newsletter)]);

        // 4. Lưu Trust Badges
        if (isset($this->data['trust_badges']) && is_array($this->data['trust_badges'])) {
            $cleanBadges = array_filter($this->data['trust_badges'], function($item) {
                return !empty($item['title']);
            });

            Setting::updateOrCreate(
                ['key' => 'home_trust_badges'],
                ['value' => json_encode(array_values($cleanBadges))]
            );
        }

        // 5. Thông báo
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Đã lưu cấu hình thành công!'
        ]);
    }
}
